<?php

namespace Tests\Unit;

use App\Content\Blocks\YouTube;
use PHPUnit\Framework\TestCase;

class YouTubeTest extends TestCase
{
    public function test_recognises_common_url_forms(): void
    {
        foreach ([
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtube.com/watch?feature=share&v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ?si=abc',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/live/dQw4w9WgXcQ',
            'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
        ] as $url) {
            $this->assertSame('dQw4w9WgXcQ', YouTube::videoId($url), $url);
        }
    }

    public function test_rejects_non_youtube(): void
    {
        $this->assertNull(YouTube::videoId('https://vimeo.com/123'));
        $this->assertNull(YouTube::videoId('https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ'));
        $this->assertNull(YouTube::videoId('not a url'));
    }
}
