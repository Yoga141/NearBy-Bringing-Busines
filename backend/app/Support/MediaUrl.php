<?php

namespace App\Support;

/**
 * Links to uploaded media (UMKM/product/profile photos) as the API sends them.
 *
 * In production the SPA (www.nearbybalikpapan.com) and this API
 * (api.nearbybalikpapan.com) are different hosts, so a relative "/storage/..."
 * in an <img> resolves against the SPA's domain and gets its index.html back.
 * Links therefore go out absolute, built from APP_URL - not from the request
 * host (url()/asset()), since the SPA may reach the API through either domain.
 *
 * The database keeps the relative form ("/api/product-photos/x.jpg"); only
 * the response is absolute, and relative() turns a link the SPA sends back
 * (a product's `img` on save) into the stored form again.
 */
final class MediaUrl
{
    /** "/storage/a.jpg" => "https://api.example.com/storage/a.jpg"; absolute links, icon keys and null unchanged. */
    public static function absolute(?string $url): ?string
    {
        if ($url === null || $url === '' || ! self::isLocalPath($url)) {
            return $url;
        }

        return self::base().'/'.ltrim($url, '/');
    }

    /** The inverse for links on this API's own APP_URL; anything else unchanged. */
    public static function relative(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        $base = self::base();
        if ($base !== '' && stripos($url, $base.'/') === 0) {
            return '/'.ltrim(substr($url, strlen($base)), '/');
        }

        return $url;
    }

    private static function base(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /** Paths this API serves itself: "/storage/...", "storage/...", "/api/...". */
    private static function isLocalPath(string $url): bool
    {
        return (bool) preg_match('#^/?(storage|api)/#i', $url);
    }
}
