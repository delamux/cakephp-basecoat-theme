<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\ConversationHelper;

class ConversationHelperTest extends HelperTestCase
{
    public function testRender(): void
    {
        $helper = new ConversationHelper($this->View);
        $html = $helper->render([
            ['text' => 'Hello there', 'from' => 'them', 'time' => '10:02', 'author' => 'Marta'],
            ['text' => 'Hi!', 'from' => 'me'],
        ], ['contact' => ['name' => 'Marta Vidal', 'subtitle' => 'Support']]);

        $this->assertStringContainsString('role="log"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('Marta Vidal', $html);
        $this->assertStringContainsString('Support', $html);
        $this->assertSame(1, substr_count($html, 'ml-auto bg-primary text-primary-foreground'));
        $this->assertSame(1, substr_count($html, 'bg-muted'));
        $this->assertStringContainsString('10:02', $html);
        $this->assertStringContainsString('Hello there', $html);
    }

    public function testComposerDefault(): void
    {
        $html = (new ConversationHelper($this->View))->render([['text' => 'a', 'from' => 'me']]);
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertStringNotContainsString('action=', $html);
        $this->assertStringContainsString('name="message"', $html);
        $this->assertStringContainsString('placeholder="Type a message…"', $html);
        $this->assertStringContainsString('>Send</button>', $html);
    }

    public function testComposerUrlAndOptions(): void
    {
        $html = (new ConversationHelper($this->View))->render([], [
            'composer' => ['url' => '/chat/send', 'name' => 'body', 'placeholder' => 'Write'],
        ]);
        $this->assertStringContainsString('action="/chat/send"', $html);
        $this->assertStringContainsString('name="body"', $html);
        $this->assertStringContainsString('placeholder="Write"', $html);
    }

    public function testComposerDisabled(): void
    {
        $html = (new ConversationHelper($this->View))->render([['text' => 'a', 'from' => 'me']], ['composer' => false]);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('name="message"', $html);
    }

    public function testEscapes(): void
    {
        $html = (new ConversationHelper($this->View))->render(
            [['text' => '<script>x</script>', 'from' => 'them', 'author' => '<b>a</b>']],
            ['contact' => ['name' => '<i>n</i>']],
        );
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>a', $html);
        $this->assertStringNotContainsString('<i>n', $html);
        $this->assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $html);
    }

    public function testEmpty(): void
    {
        $html = (new ConversationHelper($this->View))->render([], ['composer' => false]);
        $this->assertStringContainsString('role="log"', $html);
        $this->assertStringNotContainsString('bg-muted', $html);
        $this->assertStringNotContainsString('bg-primary', $html);
    }
}
