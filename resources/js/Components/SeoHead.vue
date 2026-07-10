<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();
const seo = computed(() => page.props.seo ?? {});
</script>

<template>
    <Head :title="seo.title">
        <meta v-if="seo.description" head-key="description" name="description" :content="seo.description" />
        <meta v-if="seo.robots" head-key="robots" name="robots" :content="seo.robots" />
        <link v-if="seo.canonical" head-key="canonical" rel="canonical" :href="seo.canonical" />
        <meta v-if="seo.title" head-key="og:title" property="og:title" :content="seo.title" />
        <meta v-if="seo.description" head-key="og:description" property="og:description" :content="seo.description" />
        <meta v-if="seo.canonical" head-key="og:url" property="og:url" :content="seo.canonical" />
        <meta v-if="seo.og_image" head-key="og:image" property="og:image" :content="seo.og_image" />
        <meta v-if="seo.type" head-key="og:type" property="og:type" :content="seo.type" />
        <meta v-if="seo.title" head-key="twitter:title" name="twitter:title" :content="seo.title" />
        <meta v-if="seo.description" head-key="twitter:description" name="twitter:description" :content="seo.description" />
        <meta v-if="seo.og_image" head-key="twitter:image" name="twitter:image" :content="seo.og_image" />
    </Head>
</template>
