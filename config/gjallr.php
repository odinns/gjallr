<?php

declare(strict_types=1);

return [
    'analysis' => [
        'artifact_directory' => 'gjallr/analysis',
        'compatibility_band' => '4.x-6.x',
        'version_fallback_band' => 'unknown',
        'wordpress_core_tables' => [
            'commentmeta',
            'comments',
            'links',
            'options',
            'postmeta',
            'posts',
            'term_relationships',
            'term_taxonomy',
            'termmeta',
            'terms',
            'usermeta',
            'users',
        ],
        'option_keys' => [
            'active_plugins',
            'blogname',
            'db_version',
            'home',
            'permalink_structure',
            'siteurl',
            'stylesheet',
            'template',
        ],
        'seo_plugin_slugs' => [
            'all-in-one-seo-pack',
            'easy-wp-meta-description',
            'wordpress-seo',
            'seo-by-rank-math',
            'seopress',
        ],
        'suspicious_markers' => [
            'rexuiz',
            'wphasslefree.club',
            'wpmaint',
            'seo spam',
            'viagra',
            'cialis',
            'free2play',
        ],
    ],
    'samples' => [
        'default' => [
            'sql_dump' => env('GJALLR_SAMPLE_SQL_DUMP', '/Users/odinn/Projects/old/odinns/old-2/odinns_dk_db_tantraviking.sql'),
            'site_path' => env('GJALLR_SAMPLE_SITE_PATH', '/Users/odinn/Projects/old/odinns/old-2/tantraviking-old'),
        ],
    ],
    'wayback' => [
        'analysis_limit' => (int) env('GJALLR_WAYBACK_ANALYSIS_LIMIT', 100),
        'analysis_page_limit' => (int) env('GJALLR_WAYBACK_ANALYSIS_PAGE_LIMIT', 1),
        'media_page_limit' => (int) env('GJALLR_WAYBACK_MEDIA_PAGE_LIMIT', 1),
        'discovery_page_limit' => (int) env('GJALLR_WAYBACK_DISCOVERY_PAGE_LIMIT', 2),
        'recovered_media_directory' => env('GJALLR_WAYBACK_RECOVERED_MEDIA_DIRECTORY', 'gjallr/recovered-media'),
        'url_suggestions_directory' => env('GJALLR_WAYBACK_URL_SUGGESTIONS_DIRECTORY', 'gjallr/wayback-url-suggestions'),
        'request_delay_ms' => (int) env('GJALLR_WAYBACK_REQUEST_DELAY_MS', 0),
    ],
    'source_database' => [
        'connection' => env('WP_SOURCE_DB_CONNECTION', 'wordpress'),
        'host' => env('WP_SOURCE_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('WP_SOURCE_DB_PORT', env('DB_PORT', '3306')),
        'database' => env('WP_SOURCE_DB_DATABASE', 'gjallr_wp'),
        'username' => env('WP_SOURCE_DB_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('WP_SOURCE_DB_PASSWORD', env('DB_PASSWORD', '')),
        'unix_socket' => env('WP_SOURCE_DB_SOCKET', env('DB_SOCKET', '')),
        'charset' => env('WP_SOURCE_DB_CHARSET', 'utf8mb4'),
        'collation' => env('WP_SOURCE_DB_COLLATION', 'utf8mb4_unicode_ci'),
    ],
];
