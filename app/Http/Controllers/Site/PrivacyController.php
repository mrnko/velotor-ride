<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Privacy', [
            'updatedAt' => '2026-07-10',
            'seo' => Seo::make(
                title: 'Політика конфіденційності',
                description: 'Політика конфіденційності велоклубу «ВелоТОР»: які дані ми збираємо, використання файлів cookie та ваші права.',
            ),
        ]);
    }
}
