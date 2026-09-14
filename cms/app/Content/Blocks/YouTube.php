<?php

namespace App\Content\Blocks;

final class YouTube
{
    private const HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtube-nocookie.com'];

    public static function videoId(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if (! is_array($parts) || ! in_array(strtolower($parts['host'] ?? ''), self::HOSTS, true)) {
            return null;
        }
        if (! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '';
        $candidate = null;

        if ($host === 'youtu.be') {
            $candidate = ltrim($path, '/');
        } elseif (preg_match('#^/(shorts|embed|live)/([^/?]+)#', $path, $m)) {
            $candidate = $m[2];
        } elseif ($path === '/watch') {
            parse_str($parts['query'] ?? '', $query);
            $candidate = $query['v'] ?? null;
        }

        return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
    }
}
