<?php

use App\Models\Participant;
use App\Models\User;
use App\Support\Transliterate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('display_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Backfill readable slugs for existing participants.
        $used = [];
        Participant::query()->orderBy('id')->get()->each(function (Participant $participant) use (&$used) {
            $base = Transliterate::slug($participant->display_name ?: (string) $participant->id);
            $slug = $base;
            $suffix = 2;
            while (in_array($slug, $used, true)) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }
            $used[] = $slug;
            $participant->newQuery()->whereKey($participant->getKey())->update(['slug' => $slug]);
        });

        // Give the seeded admin a username so it can sign in with "admin" too.
        User::query()->whereNull('username')->orderBy('id')->get()->each(function (User $user, int $i) {
            $user->newQuery()->whereKey($user->getKey())->update([
                'username' => $i === 0 ? 'admin' : 'user'.$user->getKey(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
