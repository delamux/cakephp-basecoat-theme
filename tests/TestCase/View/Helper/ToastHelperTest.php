<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\ToastHelper;

class ToastHelperTest extends HelperTestCase
{
    public function testToasterEmpty(): void
    {
        $toast = new ToastHelper($this->View);
        $this->assertSame('<div id="toaster" class="toaster"></div>', $toast->toaster());
        $this->assertSame('<div id="toaster" data-align="end" class="toaster"></div>', (new ToastHelper($this->View, ['align' => 'end']))->toaster());
    }

    public function testRenderToast(): void
    {
        $toast = new ToastHelper($this->View);
        $out = $toast->render('Saved <ok>', [
            'description' => 'All good',
            'duration' => -1,
            'action' => ['label' => 'Undo', 'url' => '/undo'],
            'cancel' => ['label' => 'Dismiss'],
        ]);
        $this->assertMatchesRegularExpression(
            '#^<div role="status" aria-atomic="true" aria-hidden="false" data-category="success" data-duration="-1" class="toast"><div class="toast-content"><svg[^>]*aria-hidden="true"><circle#',
            $out,
        );
        $this->assertStringContainsString('<section><h2>Saved &lt;ok&gt;</h2><p>All good</p></section>', $out);
        $this->assertStringContainsString('<footer><a href="/undo" data-size="sm" data-toast-action="true" class="btn">Undo</a><button type="button" data-variant="outline" data-size="sm" data-toast-cancel="true" class="btn">Dismiss</button></footer>', $out);
    }

    public function testErrorUsesAlertRoleAndNoIcon(): void
    {
        $toast = new ToastHelper($this->View);
        $this->assertSame(
            '<div role="alert" aria-atomic="true" aria-hidden="false" data-category="error" class="toast"><div class="toast-content"><section><h2>Failed</h2></section></div></div>',
            $toast->render('Failed', ['category' => 'error', 'icon' => false]),
        );
    }

    public function testToasterWithToasts(): void
    {
        $toast = new ToastHelper($this->View);
        $out = $toast->toaster([['title' => 'A'], ['title' => 'B', 'category' => 'info']]);
        $this->assertSame(2, substr_count($out, 'class="toast"'));
        $this->assertStringContainsString('data-category="info"', $out);
    }
}
