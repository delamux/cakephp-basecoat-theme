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
 * Renders the baked `chats` screen template with its sample data (or custom data).
 */
class ChatsScreenTest extends TestCase
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
    private View $view;

    private function render(?array $vars = null, array $query = []): string
    {
        $files = (new BakeScreenCommand())->renderFiles('chats', 'Chats');
        $file = TMP . 'chats_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Chats/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/chats',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Chats', 'action' => 'index', 'pass' => []],
            'webroot' => '/',
            'base' => '',
        ]);
        Router::setRequest($request);
        $view = $this->view = new View($request);
        foreach (BasecoatHelpers::MAP as $alias => $class) {
            if ($alias !== 'Form') {
                $view->loadHelper($alias, ['className' => $class]);
            }
        }
        $view->loadHelper('Form', ['className' => 'BasecoatTheme.Form']);
        $vars ??= ScreenRegistry::data('chats');

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

    /**
     * The page without its `<template>` blocks (what is visible before any script runs).
     */
    private function live(string $html): string
    {
        return (string)preg_replace('#<template\b.*?</template>#s', '', $html);
    }

    private function lint(string $file): int
    {
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);

        return $code;
    }

    /**
     * @return array<string, mixed>
     */
    private function data(array $override = []): array
    {
        return $override + ScreenRegistry::data('chats');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('chats');

        $this->assertSame(['chats', 'activeChat', 'statuses'], array_keys($data));
        $this->assertGreaterThanOrEqual(6, count($data['chats']));
        $ids = array_column($data['chats'], 'id');
        $this->assertContains($data['activeChat']['id'], $ids);
        foreach ($data['chats'] as $chat) {
            $this->assertArrayHasKey($chat['status'], $data['statuses']);
        }
        $this->assertNotEmpty(array_filter($data['chats'], fn(array $c) => $c['group']));
        $this->assertNotEmpty(array_filter($data['chats'], fn(array $c) => $c['unread'] > 0));
    }

    public function testConversationListRowsAndSelection(): void
    {
        $data = ScreenRegistry::data('chats');
        $html = $this->render();

        $this->assertSame(count($data['chats']), substr_count($html, 'data-chat-id='));
        $this->assertSame(1, substr_count($html, 'data-active="true"'));
        $this->assertSame(1, substr_count($html, 'aria-current="true"'));
        $this->assertMatchesRegularExpression('/<li data-chat-id="1" data-active="true">/', $html);
        $this->assertStringContainsString('The data migration can start on Thursday.', $html);
        $this->assertStringContainsString('Yesterday', $html);
    }

    public function testUnreadBadgesCarryText(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/data-unread="2"[^>]*>\s*2 unread\s*<\/span>/', $html);
        $this->assertMatchesRegularExpression('/data-unread="5"[^>]*>\s*5 unread\s*<\/span>/', $html);
        $this->assertSame(2, substr_count($html, 'data-unread='));
    }

    public function testStatusDotsHaveHiddenText(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/data-status="online"[^>]*><span class="sr-only">Online<\/span>/', $html);
        $this->assertMatchesRegularExpression('/data-status="away"[^>]*><span class="sr-only">Away<\/span>/', $html);
        $this->assertMatchesRegularExpression('/data-status="offline"[^>]*><span class="sr-only">Offline<\/span>/', $html);
    }

    public function testGroupChatsShowUsersIconWithHiddenText(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('Group chat, 6 members', $html);
        $this->assertStringContainsString('Group chat, 4 members', $html);
    }

    public function testThreadReusesConversationHelper(): void
    {
        $html = $this->live($this->render());

        $this->assertStringContainsString('role="log"', $html);
        $this->assertSame(6, substr_count($html, 'max-w-[75%]'));
    }

    public function testMessagesShowReadReceiptsAsText(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('10:31 · Read', $html);
        $this->assertStringContainsString('10:43 · Delivered', $html);
        $this->assertStringNotContainsString('10:36 ·', $html, 'Received messages have no receipt');
        $this->assertStringContainsString('bg-primary text-primary-foreground', $html);
        $this->assertStringContainsString('bg-muted', $html);
    }

    public function testHeaderShowsStatusAndNamedActions(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('Online · Customer success, Harbor &amp; Pine', $html);
        foreach (['Start a call', 'Start a video call', 'More options', 'Attach a file', 'Add an emoji', 'Record a voice message', 'New chat'] as $name) {
            $this->assertMatchesRegularExpression('/<(button|a) [^>]*>\s*<svg[^>]*aria-hidden="true"[^>]*>.*?<\/svg>\s*<span class="sr-only">' . $name . '<\/span>\s*<\/(button|a)>/s', $html, $name);
        }
        $this->assertMatchesRegularExpression('/<button[^>]*>\s*<svg.*?<\/svg>\s*Send\s*<\/button>/s', $html);
    }

    public function testSearchIsAGetFormToIndex(): void
    {
        $html = $this->render($this->data(), ['q' => 'kofi']);

        $this->assertMatchesRegularExpression('/<form method="get"[^>]*role="search"[^>]*action="\/chats"|<form method="get"[^>]*action="\/chats"[^>]*role="search"/', $html);
        $this->assertMatchesRegularExpression('/<label class="label sr-only" for="q">Search chats<\/label>/', $html);
        $this->assertStringContainsString('name="q"', $html);
        $this->assertStringContainsString('value="kofi"', $html);
        $this->assertStringContainsString('aria-label="Message"', $html);
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render($this->data(['chats' => [], 'activeChat' => null]));

        $this->assertStringContainsString('No chats', $html);
        $this->assertStringContainsString('Select a conversation', $html);
        $this->assertStringNotContainsString('<li', $html);
        $this->assertStringNotContainsString('role="log"', $html);
    }

    public function testActiveChatWithoutMessages(): void
    {
        $active = ['messages' => []] + ScreenRegistry::data('chats')['activeChat'];
        $html = $this->render($this->data(['activeChat' => $active]));

        $this->assertStringContainsString('role="log"', $html);
    }

    public function testNoPostFormsByDefault(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<form method="post"', $html);
        $this->assertDoesNotMatchRegularExpression('/\saction="(?!\/chats")/', $html);
    }

    public function testEscapes(): void
    {
        $active = [
            'id' => 1, 'name' => '<i>Eve</i>', 'group' => false, 'status' => 'online',
            'subtitle' => '<u>Sub</u>',
            'messages' => [['text' => '<script>alert(1)</script>', 'from' => 'them', 'time' => '<b>now</b>']],
        ];
        $html = $this->render($this->data([
            'chats' => [['id' => 1, 'name' => '<i>Eve</i>', 'group' => true, 'members' => 3, 'status' => 'online', 'preview' => '<img src=x onerror=alert(2)>', 'time' => '<b>1</b>', 'unread' => 1]],
            'activeChat' => $active,
            'statuses' => ['online' => '<s>Online</s>'],
        ]));

        foreach (['<script>alert', '<img src=x', '<b>now', '<b>1', '<i>Eve', '<u>Sub', '<s>Online'] as $raw) {
            $this->assertStringNotContainsString($raw, $html);
        }
        $this->assertStringContainsString('&lt;script&gt;alert(1)', $html);
    }

    public function testUniqueIds(): void
    {
        $html = $this->render();

        preg_match_all('/\bid="([^"]+)"/', $html, $matches);
        $this->assertSame($matches[1], array_values(array_unique($matches[1])));
    }

    public function testNoLinksToMissingActions(): void
    {
        $html = $this->render();

        preg_match_all('/href="([^"]*)"/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $href) {
            $this->assertSame('#', $href);
        }
    }

    public function testNoInlineJs(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    public function testTemplateStringsAreTranslatable(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/chats/template.twig');

        $this->assertStringContainsString("{{ t|raw }}'Start a call')", $source);
        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source . (string)file_get_contents(ROOT . '/templates/bake/Screen/chats/data.php'));
    }

    public function testLayoutFitsPhones(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('class="grid min-h-[32rem] grid-cols-1 gap-4 lg:grid-cols-[22rem_minmax(0,1fr)]"', $html);
        $this->assertSame(2, preg_match_all('/<section class="[^"]*\bmin-w-0\b/', $html));
        $this->assertMatchesRegularExpression('/<input type="text" class="input min-w-0 flex-1"/', $html);
    }

    public function testMessagesNameTheSenderForScreenReaders(): void
    {
        $html = $this->live($this->render());

        $this->assertSame(3, substr_count($html, '<span class="sr-only">You</span><p>'));
        $this->assertSame(3, substr_count($html, '<span class="sr-only">Kofi Mensah</span><p>'));
    }

    public function testAvatarsNextToNamesAreDecorative(): void
    {
        $html = $this->render();

        // Read once as "Kofi Mensah", not "KM Online Kofi Mensah"; the status stays as hidden text.
        $this->assertMatchesRegularExpression('/<span aria-hidden="true">KM<\/span><span class="avatar-badge[^>]*><span class="sr-only">Online<\/span>/', $html);
        $this->assertDoesNotMatchRegularExpression('/<span>[A-Z]{1,2}<\/span>/', $html);
    }

    public function testSampleDataDoesNotChatWithTheSignedInUser(): void
    {
        $this->assertNotContains('Elena Sarto', array_column(ScreenRegistry::data('chats')['chats'], 'name'));
    }

    public function testSelectingAConversationSwapsItsThreadFromATemplate(): void
    {
        $data = ScreenRegistry::data('chats');
        $html = $this->render();

        $this->assertStringContainsString('<ul class="flex max-h-[28rem] flex-1 flex-col overflow-y-auto p-2" data-master="#chat-pane" data-master-focus>', $html);
        $this->assertStringContainsString('id="chat-pane"', $html);
        foreach ($data['chats'] as $chat) {
            $this->assertStringContainsString('data-master-item="chat-thread-' . $chat['id'] . '"', $html);
            $this->assertStringContainsString('<template id="chat-thread-' . $chat['id'] . '">', $html);
        }
        $this->assertSame(count($data['chats']), substr_count($html, '<template id="chat-thread-'));
        // The thread of another conversation lives in its template, not in the open pane.
        $this->assertStringNotContainsString('Kickoff notes are in the shared folder.', $this->live($html));
        $this->assertMatchesRegularExpression('#<template id="chat-thread-2">.*Kickoff notes are in the shared folder\..*</template>#s', $html);
        $this->assertStringContainsString('Group chat · Harbor &amp; Pine, 6 members', $html);
    }

    public function testUnreadBadgesAreClearedByTheScriptWhenOpened(): void
    {
        $html = $this->render();

        $this->assertSame(2, substr_count($html, 'data-master-clear="true"'));
        $this->assertMatchesRegularExpression('/data-unread="5" data-master-clear="true"|data-master-clear="true"[^>]*data-unread="5"/', $html);
    }

    public function testComposerAppendsFromATemplateAndKeepsAFallback(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<script src="/basecoat_theme/js/basecoat-screens.js" defer="defer"></script>', $this->view->fetch('script'));
        $this->assertStringContainsString('basecoat_theme/css/interactivity.css', $this->view->fetch('css'));
        $this->assertMatchesRegularExpression('#<form method="get" data-composer="\#chat-pane \[role=log\]" data-composer-template="chat-message-template"#', $html);
        $this->assertStringContainsString('<template id="chat-message-template">', $html);
        $this->assertStringContainsString('<p data-composer-text></p>', $html);
        $this->assertStringContainsString('data-composer-time', $html);
        // No JS: still a plain GET form with a submit button and a required, named input.
        $this->assertMatchesRegularExpression('/<input type="text"[^>]*name="message"[^>]*required>/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*type="submit"[^>]*>/', $html);
        $this->assertStringNotContainsString('data-endpoint', $html);
    }
}
