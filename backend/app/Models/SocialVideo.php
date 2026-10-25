<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['platform', 'title', 'url', 'sort_order', 'active'])]
class SocialVideo extends Model
{
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Player URL for an <iframe>, derived from the pasted share link so admins
     * can paste whatever the platform's "Share" button gives them. Null when no
     * link is set yet, or when the link isn't in a shape we can embed - the card
     * then falls back to the placeholder / a plain outbound link.
     */
    protected function embedUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->url) {
                return null;
            }

            if ($this->platform === 'youtube') {
                $id = self::youtubeId($this->url);

                return $id ? "https://www.youtube-nocookie.com/embed/{$id}" : null;
            }

            $code = self::instagramCode($this->url);

            return $code ? "https://www.instagram.com/p/{$code}/embed" : null;
        });
    }

    /** Poster image for a YouTube link; Instagram has no public thumbnail URL. */
    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->platform !== 'youtube' || ! $this->url) {
                return null;
            }
            $id = self::youtubeId($this->url);

            return $id ? "https://i.ytimg.com/vi/{$id}/hqdefault.jpg" : null;
        });
    }

    /** Pulls the 11-char video id out of watch?v=, youtu.be/, /embed/ and /shorts/ links. */
    public static function youtubeId(string $url): ?string
    {
        $patterns = [
            '~[?&]v=([A-Za-z0-9_-]{11})~',
            '~youtu\.be/([A-Za-z0-9_-]{11})~',
            '~/embed/([A-Za-z0-9_-]{11})~',
            '~/shorts/([A-Za-z0-9_-]{11})~',
            '~/live/([A-Za-z0-9_-]{11})~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /**
     * Read a pasted share link: which platform it is and its clean form
     * (no tracking query, no "m."/missing "www"), or an error message saying
     * why it was refused. Mirrors `parseVideoLink` in the frontend.
     *
     * @return array{platform: string, url: string}|string
     */
    public static function parseLink(string $raw): array|string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 'Tautan video wajib diisi.';
        }
        $parts = parse_url(preg_match('~^https?://~i', $raw) ? $raw : 'https://'.$raw);
        $host = strtolower(preg_replace('~^(www\.|m\.)~i', '', $parts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        if ($host === 'instagram.com') {
            return preg_match('~^/(p|reels?|tv)/([A-Za-z0-9_-]+)/?$~', $path, $m)
                ? ['platform' => 'instagram', 'url' => 'https://www.instagram.com/'.($m[1] === 'reels' ? 'reel' : $m[1]).'/'.$m[2].'/']
                : 'Tautan Instagram harus berupa post, reel, atau IGTV: instagram.com/p/…, /reel/…, atau /tv/….';
        }

        $id = '~^[A-Za-z0-9_-]{11}$~';
        $youtube = 'Tautan YouTube harus berupa video: youtube.com/watch?v=…, youtu.be/…, atau youtube.com/shorts/….';
        if ($host === 'youtu.be') {
            $code = trim($path, '/');

            return preg_match($id, $code) ? ['platform' => 'youtube', 'url' => 'https://youtu.be/'.$code] : $youtube;
        }
        if ($host === 'youtube.com') {
            if ($path === '/watch' && is_string($query['v'] ?? null) && preg_match($id, $query['v'])) {
                return ['platform' => 'youtube', 'url' => 'https://www.youtube.com/watch?v='.$query['v']];
            }
            if (preg_match('~^/shorts/([A-Za-z0-9_-]{11})/?$~', $path, $m)) {
                return ['platform' => 'youtube', 'url' => 'https://www.youtube.com/shorts/'.$m[1]];
            }

            return $youtube;
        }

        return 'Tautan harus dari Instagram (instagram.com) atau YouTube (youtube.com / youtu.be).';
    }

    /** Pulls the shortcode out of an Instagram /p/, /reel/ or /tv/ permalink. */
    public static function instagramCode(string $url): ?string
    {
        return preg_match('~instagram\.com/(?:p|reel|reels|tv)/([A-Za-z0-9_-]+)~', $url, $m) ? $m[1] : null;
    }
}
