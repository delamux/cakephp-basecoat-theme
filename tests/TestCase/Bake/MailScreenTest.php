<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Bake;

use BasecoatTheme\Bake\ScreenRegistry;
use BasecoatTheme\Command\BakeScreenCommand;
use BasecoatTheme\View\BasecoatHelpers;
use Cake\Http\ServerRequest;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * Renders the baked `mail` screen template with its sample data (or custom data).
 */
class MailScreenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadPlugins(['BasecoatTheme']);
        Router::reload();
        Router::createRouteBuilder('/')->scope('/', function (RouteBuilder $routes): void {
            $routes->setRouteClass(DashedRoute::class);
            $routes->fallbacks();
        });
    }

    protected function tearDown(): void
    {
        $this->clearPlugins();
        parent::tearDown();
    }

    /**
     * Bakes the template of the screen and renders it with `$vars` (default: the sample data).
     *
     * @param array<string, mixed>|null $vars View variables.
     * @param array<string, mixed> $query Query string of the request.
     */
    private function render(?array $vars = null, array $query = []): string
    {
        $files = (new BakeScreenCommand())->renderFiles('mail', 'Mail');
        $file = TMP . 'mail_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Mail/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/mail',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Mail', 'action' => 'index', 'pass' => []],
            'webroot' => '/',
            'base' => '',
        ]);
        Router::setRequest($request);
        $view = new View($request);
        foreach (BasecoatHelpers::MAP as $alias => $class) {
            if ($alias !== 'Form') {
                $view->loadHelper($alias, ['className' => $class]);
            }
        }
        $view->loadHelper('Form', ['className' => 'BasecoatTheme.Form']);
        $vars ??= ScreenRegistry::data('mail');

        try {
            return (function () use ($file, $vars): string {
                extract($vars);
                ob_start();
                include $file;

                return (string)ob_get_clean();
            })->call($view);
        } finally {
            unlink($file);
        }
    }

    private function lint(string $file): int
    {
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);

        return $code;
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function data(array $override = []): array
    {
        return $override + ScreenRegistry::data('mail');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('mail');

        $this->assertSame(['folders', 'labels', 'currentFolder', 'filter', 'selected', 'messages'], array_keys($data));
        $this->assertSame(
            ['inbox', 'starred', 'sent', 'drafts', 'archive', 'spam', 'trash'],
            array_column($data['folders'], 'key'),
        );
        $ids = array_column($data['messages'], 'id');
        $this->assertContains($data['selected'], $ids);
        $this->assertSame($ids, array_values(array_unique($ids)));
        foreach ($data['messages'] as $message) {
            foreach ($message['labels'] as $key) {
                $this->assertArrayHasKey($key, $data['labels']);
            }
            $this->assertIsBool($message['unread']);
            $this->assertNotEmpty($message['body']);
        }
        $this->assertSame(
            $data['folders'][0]['count'],
            count(array_filter(array_column($data['messages'], 'unread'))),
            'Inbox count is the number of unread messages',
        );
    }

    public function testThreePanesWithLandmarks(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<aside class="flex min-w-0 flex-col gap-4 md:row-span-2 lg:row-span-1" aria-label="Mailbox">', $html);
        $this->assertSame(1, substr_count($html, 'aria-label="Folders"'));
        $this->assertStringContainsString('aria-label="Messages"', $html);
        // Heading outline: one h2 per pane, the subject of the open message below it.
        $this->assertStringContainsString('<h2 class="sr-only">Messages</h2>', $html);
        $this->assertStringContainsString('<h2 class="sr-only">Reading pane</h2>', $html);
        $this->assertStringContainsString('aria-label="Reading pane"', $html);
        $this->assertMatchesRegularExpression('/class="[^"]*\bgrid\b[^"]*\blg:grid-cols-/', $html, 'Stacks on small screens, three columns on large ones');
    }

    public function testFoldersWithCountsAndCurrent(): void
    {
        $html = $this->render();

        foreach (['Inbox', 'Starred', 'Sent', 'Drafts', 'Archive', 'Spam', 'Trash'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertMatchesRegularExpression('/<a [^>]*aria-current="page"[^>]*>.*?Inbox.*?<\/a>/s', $html);
        $this->assertStringContainsString('<span class="sr-only">4 unread</span>', $html);
        $this->assertStringContainsString('<span class="sr-only">3 messages</span>', $html);
        $this->assertStringContainsString('Compose', $html);
        // Real button styles (class="btn" + data-variant), the current folder visibly highlighted.
        $this->assertStringNotContainsString('btn-ghost', $html);
        $this->assertStringNotContainsString('btn-secondary', $html);
        $this->assertMatchesRegularExpression('/<a href="#" class="btn [^"]*bg-accent[^"]*" data-variant="ghost" aria-current="page" data-active="true">/', $html);
        $this->assertSame(1, substr_count($html, 'bg-accent text-accent-foreground'));
    }

    public function testFolderIconsHaveOneMeaning(): void
    {
        $folders = array_column(ScreenRegistry::data('mail')['folders'], 'icon', 'key');

        $this->assertSame('file-pen', $folders['drafts'], 'file-text means "View" elsewhere');
        $this->assertSame('shield-alert', $folders['spam'], 'circle-alert is the alert icon');
    }

    public function testFoldersAndLabelsAreAHorizontalStripOnPhones(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<nav class="flex gap-1 overflow-x-auto md:flex-col md:overflow-visible" aria-label="Folders">/', $html);
        $this->assertMatchesRegularExpression('/<nav class="flex gap-1 overflow-x-auto md:flex-col md:overflow-visible" aria-labelledby="mail-labels-title">/', $html);
    }

    public function testLabelsInNavAndOnMessages(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('aria-labelledby="mail-labels-title"', $html);
        $this->assertGreaterThanOrEqual(2, substr_count($html, 'data-label="billing"'));
        $this->assertMatchesRegularExpression('/<span[^>]*data-label="planning"[^>]*>Planning<\/span>/', $html);
    }

    public function testUnreadStateUsesTextNotOnlyColour(): void
    {
        $data = ScreenRegistry::data('mail');
        $html = $this->render();

        $unread = count(array_filter(array_column($data['messages'], 'unread')));
        $this->assertSame($unread, substr_count($html, 'data-unread="true"'));
        $this->assertSame($unread, substr_count($html, '<span class="sr-only">Unread</span>'));
        $this->assertSame(count($data['messages']), substr_count($html, 'data-message-id='));
    }

    public function testSelectedMessageHighlightedAndOpened(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<a [^>]*data-message-id="1"[^>]*aria-current="true"/', $html);
        $this->assertSame(1, preg_match_all('/<a [^>]*data-message-id[^>]*aria-current="true"/', $html));
        $this->assertMatchesRegularExpression('/<h3[^>]*>Harbor &amp; Pine onboarding: field mapping<\/h3>/', $html);
        $this->assertStringContainsString('kofi.mensah@example.com', $html);
        $this->assertStringContainsString('Fri, 20 Nov 2026, 09:42', $html);
        $this->assertStringContainsString('Tell me which one you prefer', $html);
    }

    public function testReadingPaneActionsAreNamedIconButtons(): void
    {
        $html = $this->render();

        foreach (['Reply', 'Forward', 'Archive', 'Delete'] as $name) {
            $this->assertMatchesRegularExpression(
                '/<button[^>]*>\s*<svg[^>]*aria-hidden="true"[^>]*>.*?<\/svg>\s*<span class="sr-only">' . $name . '<\/span>\s*<\/button>/s',
                $html,
                $name,
            );
        }
    }

    public function testReplyBox(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<label[^>]*for="mail-reply"[^>]*>[^<]*Reply to Kofi Mensah/', $html);
        $this->assertMatchesRegularExpression('/<textarea[^>]*id="mail-reply"/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*>\s*(<svg.*?<\/svg>\s*)?Send\s*<\/button>/s', $html);
    }

    public function testTabsAndSearchGetToIndex(): void
    {
        $html = $this->render($this->data(['filter' => 'unread']), ['q' => 'invoice']);

        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringContainsString('action="/mail"', $html);
        $this->assertStringContainsString('name="q"', $html);
        $this->assertStringContainsString('value="invoice"', $html);
        // The tabs keep the search term.
        $this->assertMatchesRegularExpression('/<a href="\/mail\?filter=unread&amp;q=invoice" aria-current="true" class="btn" data-variant="secondary"[^>]*>\s*Unread/s', $html);
        $this->assertMatchesRegularExpression('/<a href="\/mail\?filter=all&amp;q=invoice" class="btn" data-variant="ghost"[^>]*>\s*All/s', $html);
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render($this->data(['messages' => [], 'selected' => null]));

        $this->assertStringContainsString('No messages', $html);
        $this->assertStringContainsString('Select a message to read it.', $html);
        $this->assertStringNotContainsString('data-message-id', $html);
        $this->assertStringNotContainsString('<textarea', $html);
    }

    public function testNoSelectionShowsPlaceholderOnly(): void
    {
        $html = $this->render($this->data(['selected' => null]));

        $this->assertStringContainsString('Select a message to read it.', $html);
        $this->assertStringNotContainsString('<textarea', $html);
        $this->assertDoesNotMatchRegularExpression('/<a [^>]*data-message-id[^>]*aria-current/', $html);
    }

    public function testUnknownSelectionIsIgnored(): void
    {
        $html = $this->render($this->data(['selected' => 999]));

        $this->assertStringContainsString('Select a message to read it.', $html);
    }

    public function testEscapes(): void
    {
        $html = $this->render([
            'folders' => [['key' => 'inbox', 'label' => '<u>Inbox</u>', 'icon' => 'inbox', 'count' => 1]],
            'labels' => ['x' => '<i>Label</i>'],
            'currentFolder' => 'inbox',
            'filter' => 'all',
            'selected' => 1,
            'messages' => [[
                'id' => 1,
                'from' => ['name' => '<b>Eve</b>', 'email' => 'e"<s>@example.com'],
                'to' => '<em>You</em>',
                'subject' => '<script>alert(1)</script>',
                'snippet' => '<img src=x onerror=alert(2)>',
                'time' => '<p>now</p>',
                'date' => '<q>today</q>',
                'unread' => true,
                'starred' => false,
                'labels' => ['x'],
                'body' => ['<marquee>hi</marquee>'],
            ]],
        ]);

        foreach (['<script>alert', '<img src=x', '<b>Eve', '<s>@', '<em>You', '<p>now', '<q>today', '<marquee', '<i>Label', '<u>Inbox'] as $raw) {
            $this->assertStringNotContainsString($raw, $html);
        }
        $this->assertStringContainsString('&lt;script&gt;alert(1)', $html);
    }

    public function testUniqueIds(): void
    {
        $html = $this->render();

        preg_match_all('/\bid="([^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }

    public function testNoLinksToMissingActionsAndNoStateChangingForms(): void
    {
        $html = $this->render();

        preg_match_all('/href="([^"]*)"/', $html, $matches);
        foreach ($matches[1] as $href) {
            $this->assertMatchesRegularExpression('/^(#|\/mail\?filter=(all|unread))$/', $href, 'Unexpected link ' . $href);
        }
        $this->assertStringNotContainsString('<form method="post"', $html);
        $this->assertDoesNotMatchRegularExpression('/\saction="(?!\/mail")/', $html);
    }

    public function testNoInlineJs(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<style', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('src="http', $html);
    }

    public function testDoesNotUseThirdPartyOrPlaceholderNames(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/mail/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/mail/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe|4242/', $source);
    }

    public function testSenderAvatarIsDecorative(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<span aria-hidden="true">KM</span>', $html);
        $this->assertDoesNotMatchRegularExpression('/<span>[A-Z]{1,2}<\/span>/', $html);
    }
}
