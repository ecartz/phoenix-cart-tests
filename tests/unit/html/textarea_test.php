<?php

declare(strict_types=1);

namespace PhoenixCart\Tests\unit\html;

use Textarea;

final class textarea_test extends html_test_case {

    public function test_to_string_wraps_content_and_defaults(): void {
        $textarea = new Textarea('comment');
        $textarea->set_text('Hello &amp; welcome');

        $this->assertSame(
            '<textarea name="comment" class="form-control" >Hello &amp;amp; welcome</textarea>',
            "$textarea"
        );
    }

    public function test_retain_text_uses_request_value(): void {
        $_POST['notes'] = 'Posted text';

        $textarea = new Textarea('notes');
        $textarea->retain_text();

        $this->assertStringContainsString('Posted text', "$textarea");
    }
}
