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
 * Renders the baked `ai-chat` screen template with its sample data (or custom data).
 */
class AiChatScreenTest extends TestCase
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
        $files = (new BakeScreenCommand())->renderFiles('ai-chat', 'Assistant');
        $file = TMP . 'ai_chat_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Assistant/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/assistant',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Assistant', 'action' => 'index', 'pass' => []],
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
        $vars ??= ScreenRegistry::data('ai-chat');

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
        return $override + ScreenRegistry::data('ai-chat');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('ai-chat');

        $this->assertSame(['assistant', 'suggestions', 'messages', 'models', 'model'], array_keys($data));
        $this->assertGreaterThanOrEqual(3, count($data['suggestions']));
        $this->assertArrayHasKey($data['model'], $data['models']);
        $roles = array_column($data['messages'], 'from');
        $this->assertContains('user', $roles);
        $this->assertContains('assistant', $roles);
        $this->assertNotEmpty(array_filter(array_column($data['messages'], 'code')), 'One answer has a code block');
    }

    public function testHeroAndSuggestionsAreButtons(): void
    {
        $data = ScreenRegistry::data('ai-chat');
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<h2[^>]*>[^<]*' . preg_quote($data['assistant']['name'], '/') . '/', $html);
        $this->assertSame(count($data['suggestions']), substr_count($html, 'data-suggestion'));
        foreach ($data['suggestions'] as $text) {
            $this->assertMatchesRegularExpression('/<button [^>]*data-suggestion[^>]*type="button"[^>]*>[^<]*' . preg_quote(htmlspecialchars($text, ENT_QUOTES), '/') . '/', $html);
        }
    }

    public function testThreadRendersAllMessages(): void
    {
        $data = ScreenRegistry::data('ai-chat');
        $html = $this->live($this->render());

        $this->assertMatchesRegularExpression('/<div[^>]*role="log"[^>]*aria-live="polite"/', $html);
        $this->assertSame(count($data['messages']), substr_count($html, 'data-message-from='));
        foreach ($data['messages'] as $message) {
            $this->assertStringContainsString('data-message-from="' . $message['from'] . '"', $html);
            foreach ($message['paragraphs'] as $paragraph) {
                $this->assertStringContainsString(htmlspecialchars($paragraph, ENT_QUOTES), $html);
            }
        }
    }

    public function testCodeBlockIsEscapedPreCode(): void
    {
        $html = $this->render($this->data(['messages' => [[
            'id' => 1,
            'from' => 'assistant',
            'time' => '10:02',
            'paragraphs' => ['Try this.'],
            'code' => ['language' => 'php', 'content' => "echo '<b>x</b>' . \$a & \$b;"],
        ]]]));

        $this->assertMatchesRegularExpression('/<pre[^>]*><code class="language-php">echo &#039;&lt;b&gt;x&lt;\/b&gt;&#039; \. \$a &amp; \$b;<\/code><\/pre>/', $html);
        $this->assertStringNotContainsString('<b>x</b>', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*<span class="sr-only">Copy code<\/span>\s*<\/button>/s', $html);
    }

    public function testComposer(): void
    {
        $data = ScreenRegistry::data('ai-chat');
        $html = $this->render();

        // A plain GET form without the script (nothing is posted until you wire an action); the script
        // turns it into the composer (data-composer) that appends the message and a canned reply.
        $this->assertStringNotContainsString('<form method="post"', $html);
        $this->assertMatchesRegularExpression('/<form method="get"[^>]*id="ai-chat-composer"[^>]*data-composer="#ai-chat-log" data-composer-template="ai-chat-user-message" data-composer-reply="ai-chat-assistant-reply"/', $html);
        $this->assertMatchesRegularExpression('/<textarea[^>]*name="message"[^>]*>/', $html);
        $this->assertMatchesRegularExpression('/<label[^>]*for="ai-chat-message"[^>]*>\s*Message\s*<\/label>/', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*name="model"[^>]*>/', $html);
        foreach ($data['models'] as $value => $label) {
            $selected = $value === $data['model'] ? ' selected="selected"' : '';
            $this->assertStringContainsString('<option value="' . $value . '"' . $selected . '>' . $label . '</option>', $html);
        }
        $this->assertMatchesRegularExpression('/<button[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*<span class="sr-only">Attach a file<\/span>\s*<\/button>/s', $html);
        $this->assertMatchesRegularExpression('/<button type="submit"[^>]*>\s*<svg.*?<\/svg>\s*Send\s*<\/button>/s', $html);
    }

    public function testNoMotionOrOrb(): void
    {
        $html = $this->render();

        $this->assertDoesNotMatchRegularExpression('/animate-|<animate|<style|transition-|orb/i', $html);
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render($this->data(['messages' => [], 'suggestions' => []]));

        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringNotContainsString('data-message-from', $this->live($html));
        $this->assertStringNotContainsString('data-suggestion', $html);
        $this->assertStringContainsString('role="log"', $html);
    }

    public function testEscapes(): void
    {
        $html = $this->render($this->data([
            'assistant' => ['name' => '<i>Bot</i>', 'tagline' => '<u>tag</u>'],
            'suggestions' => ['<script>alert(1)</script>'],
            'models' => ['x' => '<em>Model</em>'],
            'model' => 'x',
            'messages' => [[
                'id' => 1,
                'from' => 'user',
                'author' => '<b>Eve</b>',
                'time' => '<s>now</s>',
                'paragraphs' => ['<img src=x onerror=alert(2)>'],
            ]],
        ]));

        foreach (['<script>alert', '<img src=x', '<i>Bot', '<u>tag', '<em>Model', '<b>Eve', '<s>now'] as $raw) {
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

    public function testNoLinksToMissingActions(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<a ', $html);
        $this->assertDoesNotMatchRegularExpression('/\saction="(?!\/assistant")/', $html);
    }

    public function testNoInlineJs(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('style=', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function testStringsAreTranslatable(): void
    {
        $template = (string)file_get_contents(ROOT . '/templates/bake/Screen/ai-chat/template.twig');

        $this->assertStringContainsString("{{ t|raw }}'Send')", $template);
        $this->assertStringContainsString("{{ t|raw }}'Attach a file')", $template);
        $this->assertStringContainsString("{{ t|raw }}'Copy code')", $template);
        $this->assertDoesNotMatchRegularExpression('/>\s*[A-Z][a-z]+ [a-z ]+<\//', preg_replace('/<\?.*?\?>/s', '', $template));
    }

    public function testInventedNamesOnly(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/ai-chat/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/ai-chat/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe|ChatGPT|GPT|Claude|Anthropic|OpenAI|Gemini|Copilot|Llama|Mistral|Bard/i', $source);
    }

    public function testSuggestionsFillTheComposer(): void
    {
        $data = ScreenRegistry::data('ai-chat');
        $html = $this->render();

        foreach ($data['suggestions'] as $text) {
            $this->assertMatchesRegularExpression('/<button [^>]*data-composer-fill="' . preg_quote(htmlspecialchars($text, ENT_QUOTES), '/') . '"[^>]*data-composer-target="#ai-chat-composer"/', $html);
        }
        $this->assertStringContainsString('id="ai-chat-log"', $html);
    }

    public function testMessagesComeFromTemplatesAndScriptsLoad(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('#<template id="ai-chat-user-message">.*data-composer-time.*data-composer-text.*</template>#s', $html);
        $this->assertMatchesRegularExpression('#<template id="ai-chat-assistant-reply">.*This is a sample answer\..*</template>#s', $html);
        $this->assertStringContainsString('basecoat-screens.js', $this->view->fetch('script'));
        $this->assertStringContainsString('interactivity.css', $this->view->fetch('css'));
        $this->assertStringNotContainsString('data-endpoint', $html);
    }
}
