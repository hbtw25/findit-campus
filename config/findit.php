<?php

// Pengaturan khusus aplikasi FindIt Campus
return [

    // Titik tengah kampus Telkom University Bandung dan batas area laporan (meter)
    'campus' => [
        'lat' => -6.9733,
        'lng' => 107.6304,
        'zoom' => 17,
        'radius_m' => 1500,
    ],

    // Domain email yang boleh mendaftar (kosongkan di .env untuk mengizinkan semua email)
    'email_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FINDIT_EMAIL_DOMAINS', ''))
    ))),

    'reports' => [
        'max_per_day' => 10,
        'max_age_days' => 60,
        'photo_max_kb' => 4096,
    ],

    'claims' => [
        'max_pending_per_user' => 3,
    ],

    // Aturan auto-match (lihat PRD bagian 8)
    'match' => [
        'max_distance_m' => 1000,
        'max_days' => 30,
        'min_score' => 40,
        'alert_score' => 60,
        'max_candidates' => 5,
    ],

    // Penilaian AI (opsional). Penyedia dicoba berurutan sesuai AI_PROVIDERS;
    // penyedia tanpa API key dilewati. Bila semua gagal, skor aturan tetap dipakai.
    'ai' => [
        'providers' => array_values(array_filter(array_map('trim', explode(',', (string) env('AI_PROVIDERS', 'gemini'))))),
        'timeout' => 45,

        // Google AI Studio, tier gratis
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL') ?: 'gemini-3.5-flash-lite',
            'url' => 'https://generativelanguage.googleapis.com/v1beta/models',
        ],

        // OpenCode Console (format OpenAI chat completions). Model gratisnya hanya bisa dipakai
        // dari aplikasi OpenCode, jadi dari website perlu model berbayar atau langganan OpenCode Go.
        'opencode' => [
            'api_key' => env('OPENCODE_API_KEY'),
            'model' => env('OPENCODE_MODEL') ?: 'deepseek-v4.1-flash',
            'url' => 'https://opencode.ai/inference/openai/v1/chat/completions',
        ],
    ],
];
