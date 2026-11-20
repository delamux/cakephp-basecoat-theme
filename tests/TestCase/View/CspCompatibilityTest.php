<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View;

use BasecoatTheme\Test\TestCase\View\Helper\HelperTestCase;
use BasecoatTheme\View\BasecoatHelpers;
use BasecoatTheme\View\Helper\DialogHelper;
use BasecoatTheme\View\Helper\ProgressHelper;
use BasecoatTheme\View\Helper\SidebarHelper;
use BasecoatTheme\View\Helper\ThemeHelper;
use Cake\View\View;

/**
 * Content-Security-Policy compatibility: nothing the theme renders needs `'unsafe-inline'`.
 * Interactivity uses data attributes handled by webroot/js/basecoat-theme.js (event delegation),
 * and the scripts/styles it prints carry the request's CSP nonce (CspMiddleware) when present.
 */
class CspCompatibilityTest extends HelperTestCase
{
    private function assertNoInlineJs(string $html): void
    {
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html, 'No inline event handlers');
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)(?![^>]*\bnonce=)[^>]*>/i', $html, 'No inline <script> without nonce');
    }

    private function withNonces(): View
    {
        $request = $this->View->getRequest()
            ->withAttribute('cspScriptNonce', 'script-nonce')
            ->withAttribute('cspStyleNonce', 'style-nonce');

        return new View($request);
    }

    public function testSidebarToggleUsesADataAttribute(): void
    {
        $out = (new SidebarHelper($this->View, ['id' => 'main-nav']))->toggle(['label' => 'Menu']);

        $this->assertNoInlineJs($out);
        $this->assertStringContainsString('data-sidebar-toggle="main-nav"', $out);
        $this->assertStringContainsString('aria-controls="main-nav"', $out);
    }

    public function testDialogTriggerCloseAndOverlayUseDataAttributes(): void
    {
        $dialog = new DialogHelper($this->View);
        $out = $dialog->render('profile', 'x', ['title' => 'Edit', 'trigger' => 'Edit']) . $dialog->close('Cancel');

        $this->assertNoInlineJs($out);
        $this->assertStringContainsString('data-dialog-open="profile"', $out);
        $this->assertStringContainsString('data-dialog-close', $out);
        $this->assertStringContainsString('data-dialog-backdrop-close', $out);
    }

    public function testFlashElementsHaveNoInlineJs(): void
    {
        $alert = $this->View->element('BasecoatTheme.flash/success', ['message' => 'Saved', 'params' => ['dismissible' => true]]);
        $toast = $this->View->element('BasecoatTheme.flash/toast', ['message' => 'Saved', 'params' => []]);

        $this->assertNoInlineJs($alert);
        $this->assertStringContainsString('data-alert-dismiss', $alert);
        $this->assertNoInlineJs($toast);
        $this->assertStringContainsString('<template data-basecoat-toast', $toast);
    }

    public function testProgressHasNoStyleAttribute(): void
    {
        $out = (new ProgressHelper($this->View))->render(42);

        $this->assertStringNotContainsString('style=', $out, "Style attributes need 'unsafe-inline'");
        $this->assertStringContainsString('data-progress-value="42"', $out, 'Width set by basecoat-theme.js (CSSOM)');
    }

    public function testErrorLayoutBackLinkIsNotAJavascriptUrl(): void
    {
        $layout = (string)file_get_contents(dirname(__DIR__, 3) . '/templates/layout/error.php');

        $this->assertStringNotContainsString('javascript:', $layout);
        $this->assertStringContainsString('data-history-back', $layout);
    }

    public function testThemeAssetsCarryTheCspNonce(): void
    {
        $theme = new ThemeHelper($this->withNonces());

        $script = $theme->darkModeScript();
        $css = $theme->css();

        $this->assertStringContainsString('nonce="script-nonce"', $script);
        $this->assertMatchesRegularExpression('/<style[^>]*nonce="style-nonce"[^>]*type="text\/tailwindcss"|<style[^>]*type="text\/tailwindcss"[^>]*nonce="style-nonce"/', $css);
        $this->assertStringContainsString('nonce="style-nonce"', $css, 'Stylesheets too');
    }

    public function testWithoutCspNothingChanges(): void
    {
        $script = (new ThemeHelper($this->View))->darkModeScript();

        $this->assertStringStartsWith('<script>', $script);
        $this->assertStringNotContainsString('nonce', $script);
    }

    public function testCommandMenuAndDateRangeUseDataAttributes(): void
    {
        $this->loadBasecoatHelpers();
        $command = $this->View->CommandMenu->trigger('cmd')
            . $this->View->CommandMenu->render(['Go' => [['label' => 'Home', 'url' => '/']]], ['id' => 'cmd']);
        $range = $this->View->DateRangeField->render('created', ['presets' => ['This month' => ['2026-11-01', '2026-11-30']]]);

        $this->assertNoInlineJs($command);
        $this->assertStringContainsString('data-command-open="cmd"', $command);
        $this->assertStringContainsString('data-command-hotkey="k"', $command);
        $this->assertNoInlineJs($range);
        $this->assertStringContainsString('data-range-from="2026-11-01"', $range);
    }

    public function testDashboardHelpersHaveNoInlineJs(): void
    {
        $this->loadBasecoatHelpers();
        $html = $this->View->ChoiceCards->render('plan', ['a' => ['title' => 'A', 'icon' => 'wallet']])
            . $this->View->Conversation->render([['text' => 'Hi', 'from' => 'them']])
            . $this->View->MemberList->render([['name' => 'Ana', 'email' => 'ana@example.com', 'role' => 'owner']], ['roles' => ['owner' => 'Owner']])
            . $this->View->MetricCard->render('Users', '12', ['change' => '+1', 'trend' => 'up'])
            . $this->View->PageHeader->render('Overview')
            . $this->View->TableToolbar->render(['filter' => ['q' => ['type' => 'search', 'label' => 'Search']]])
            . $this->View->WorkspaceSwitcher->render([['name' => 'Acme', 'url' => '#']]);

        $this->assertNoInlineJs($html);
    }

    private function loadBasecoatHelpers(): void
    {
        foreach (BasecoatHelpers::MAP as $alias => $className) {
            $this->View->loadHelper($alias, ['className' => $className]);
        }
    }
}
