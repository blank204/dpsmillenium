<?php

namespace Tests\Unit;

use App\Content\Blocks\TextSanitizer;
use PHPUnit\Framework\TestCase;

class TextSanitizerTest extends TestCase
{
    public function test_keeps_allowed_formatting(): void
    {
        $html = '<p>Hello <strong>parents</strong> and <em>students</em></p><ul><li>One</li></ul><ol><li>Two</li></ol>';
        $clean = (new TextSanitizer())->clean($html);
        foreach (['<strong>parents</strong>', '<em>students</em>', '<ul><li>One</li></ul>', '<ol><li>Two</li></ol>'] as $fragment) {
            $this->assertStringContainsString($fragment, $clean);
        }
    }

    public function test_removes_scripts_handlers_styles_and_bad_links(): void
    {
        $html = '<p onclick="x()" style="color:red">Hi<script>alert(1)</script></p><a href="javascript:alert(1)">bad</a><img src=x onerror=alert(1)><iframe src="https://evil"></iframe>';
        $clean = (new TextSanitizer())->clean($html);
        foreach (['script', 'onclick', 'style=', 'javascript:', '<img', '<iframe', 'onerror'] as $bad) {
            $this->assertStringNotContainsStringIgnoringCase($bad, $clean);
        }
    }

    public function test_links_get_safe_rel(): void
    {
        $clean = (new TextSanitizer())->clean('<p><a href="https://example.com">site</a></p>');
        $this->assertStringContainsString('href="https://example.com"', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }
}
