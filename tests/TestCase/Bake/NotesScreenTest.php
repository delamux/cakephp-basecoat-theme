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

use function Cake\Core\h;

/**
 * Renders the baked `notes` screen template with its sample data (or custom data).
 */
class NotesScreenTest extends TestCase
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
        $files = (new BakeScreenCommand())->renderFiles('notes', 'Notes');
        $file = TMP . 'notes_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Notes/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/notes',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Notes', 'action' => 'index', 'pass' => []],
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
        $vars ??= ScreenRegistry::data('notes');

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
     * @return array<string, mixed>
     */
    private function data(array $override = []): array
    {
        return $override + ScreenRegistry::data('notes');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('notes');

        $this->assertSame(['notes', 'labels', 'total', 'archivedCount'], array_keys($data));
        $this->assertGreaterThanOrEqual(7, count($data['notes']));
        $types = array_unique(array_column($data['notes'], 'type'));
        sort($types);
        $this->assertSame(['checklist', 'image', 'text'], $types);
        $labelIds = array_column($data['labels'], 'id');
        foreach ($data['notes'] as $note) {
            foreach ($note['labels'] ?? [] as $id) {
                $this->assertContains($id, $labelIds);
            }
        }
        $this->assertSame($data['total'], count($data['notes']));
    }

    public function testRendersAllNotes(): void
    {
        $data = ScreenRegistry::data('notes');
        $html = $this->render();

        $this->assertSame(count($data['notes']), substr_count($html, '<article '));
        foreach ($data['notes'] as $note) {
            $this->assertStringContainsString(h($note['title']), $html);
        }
        $this->assertMatchesRegularExpression('/<article [^>]*aria-labelledby="note-1-title"/', $html);
        $this->assertStringContainsString('id="note-1-title"', $html);
    }

    public function testSidebarNavAndLabelsAlwaysHaveText(): void
    {
        $data = ScreenRegistry::data('notes');
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<button[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*New note\s*<\/button>/s', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('Archive', $html);
        $this->assertStringContainsString('Edit labels', $html);
        foreach ($data['labels'] as $label) {
            // The colour dot is decorative; the name travels as text next to it.
            $this->assertMatchesRegularExpression(
                '/<span[^>]*data-label-dot[^>]*aria-hidden="true"[^>]*><\/span>\s*<span>' . preg_quote(h($label['name']), '/') . '<\/span>/',
                $html,
            );
        }
        $this->assertSame(count($data['labels']), substr_count($html, 'data-label-dot="sidebar"'));
    }

    public function testNoteLabelBadgesPairDotWithText(): void
    {
        $html = $this->render($this->data([
            'notes' => [['id' => 1, 'type' => 'text', 'title' => 'T', 'body' => 'B', 'updated' => 'Today', 'labels' => [2]]],
            'total' => 1,
        ]));

        $name = h(ScreenRegistry::data('notes')['labels'][1]['name']);
        $this->assertMatchesRegularExpression('/<span[^>]*data-note-label[^>]*>\s*<span[^>]*data-label-dot="note"[^>]*aria-hidden="true"[^>]*><\/span>\s*' . preg_quote($name, '/') . '\s*<\/span>/', $html);
    }

    public function testChecklistItemsAreLabelledCheckboxes(): void
    {
        $html = $this->render();

        preg_match_all('/<input type="checkbox"[^>]*id="([^"]+)"[^>]*>/', $html, $boxes);
        $this->assertNotEmpty($boxes[1]);
        foreach ($boxes[1] as $id) {
            $this->assertStringContainsString('<label for="' . $id . '"', $html);
        }
        $done = substr_count($html, ' checked');
        $this->assertGreaterThan(0, $done);
        $this->assertSame($done, substr_count($html, 'line-through'));
        $this->assertSame($done, substr_count($html, '<span class="sr-only">completed</span>'));
    }

    public function testImageNotes(): void
    {
        $html = $this->render();

        $this->assertStringContainsString('<svg', $html);
        $this->assertDoesNotMatchRegularExpression('/<img\b/', $html);
        $this->assertDoesNotMatchRegularExpression('/(src|href)="https?:/', $html);
        // Meaningful image: role=img + name; decorative: hidden from the accessibility tree.
        $this->assertMatchesRegularExpression('/<figure[^>]*>\s*<svg[^>]*role="img"[^>]*aria-label="[^"]+"/', $html);

        $decorative = $this->render($this->data([
            'notes' => [['id' => 1, 'type' => 'image', 'title' => 'Deco', 'body' => '', 'updated' => 'Today', 'image' => ['art' => 'hills', 'alt' => '']]],
        ]));
        $this->assertMatchesRegularExpression('/<figure[^>]*>\s*<svg[^>]*aria-hidden="true"/', $decorative);
        $this->assertStringNotContainsString('role="img"', $decorative);
    }

    public function testViewToggleIsLabelledSegmentedControlGet(): void
    {
        $html = $this->render($this->data(), ['q' => 'plan']);

        $this->assertMatchesRegularExpression('/role="group"[^>]*aria-label="Layout"|aria-label="Layout"[^>]*role="group"/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*name="view"[^>]*value="grid"[^>]*>/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*name="view"[^>]*value="list"[^>]*>/', $html);
        $this->assertStringContainsString('Grid view', $html);
        $this->assertStringContainsString('List view', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*value="grid"[^>]*aria-pressed="true"/', $html, 'Grid is the default');
        $this->assertMatchesRegularExpression('/<button[^>]*value="list"[^>]*aria-pressed="false"/', $html);
        $this->assertStringContainsString('action="/notes"', $html);
        $this->assertMatchesRegularExpression('/<input type="hidden" name="q" value="plan"/', $html, 'The toggle keeps the search');
    }

    public function testListViewViaQuery(): void
    {
        $html = $this->render($this->data(), ['view' => 'list']);

        $this->assertMatchesRegularExpression('/<button[^>]*value="list"[^>]*aria-pressed="true"/', $html);
        $this->assertStringContainsString('data-view="list"', $html);
        $this->assertStringNotContainsString('data-view="grid"', $html);

        $unknown = $this->render($this->data(), ['view' => ['x'], ]);
        $this->assertStringContainsString('data-view="grid"', $unknown);
    }

    public function testSearchIsGetToIndex(): void
    {
        $html = $this->render($this->data(), ['view' => 'list', 'q' => 'trip']);

        $this->assertStringContainsString('method="get"', $html);
        $this->assertStringContainsString('name="q"', $html);
        $this->assertStringContainsString('value="trip"', $html);
        $this->assertMatchesRegularExpression('/<input type="hidden" name="view" value="list"/', $html);
        $this->assertStringContainsString('Search notes', $html);
    }

    public function testIconOnlyControlsAreNamed(): void
    {
        $html = $this->render();

        // One distinct name per note menu.
        foreach (ScreenRegistry::data('notes')['notes'] as $note) {
            $this->assertMatchesRegularExpression('/<button[^>]*>\s*<svg[^>]*>.*?<\/svg>\s*<span class="sr-only">' . preg_quote(h('Options for ' . $note['title']), '/') . '<\/span>\s*<\/button>/s', $html);
        }
        $this->assertStringNotContainsString('Note options', $html);
        $this->assertMatchesRegularExpression('/<svg[^>]*aria-hidden="true"/', $html);
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->render($this->data(['notes' => [], 'total' => 0]));

        $this->assertStringContainsString('No notes yet', $html);
        $this->assertStringNotContainsString('<article', $html);
        $this->assertStringContainsString('New note', $html);
    }

    public function testEmptyWithSearchExplainsIt(): void
    {
        $html = $this->render($this->data(['notes' => [], 'total' => 0]), ['q' => 'zzz']);

        $this->assertStringContainsString('Nothing matches your search.', $html);
        $this->assertStringContainsString('No notes found', $html);
        $this->assertStringNotContainsString('No notes yet', $html);
    }

    public function testSidebarIsAHorizontalStripOnPhones(): void
    {
        $html = $this->render();

        $this->assertSame(2, substr_count($html, '<ul class="flex gap-1 overflow-x-auto text-sm lg:flex-col lg:overflow-visible">'));
        $this->assertStringContainsString('<aside class="flex min-w-0 flex-col gap-4"', $html);
    }

    public function testImageNotesAreFlushWithTheCardTop(): void
    {
        $html = $this->render();

        $this->assertMatchesRegularExpression('/<article [^>]*data-note-type="image" class="card pt-0 /', $html);
        $this->assertDoesNotMatchRegularExpression('/<article [^>]*data-note-type="(text|checklist)" class="card pt-0/', $html);
    }

    public function testRendersWithoutLabelsAndUnknownLabelIds(): void
    {
        $html = $this->render($this->data([
            'labels' => [],
            'notes' => [['id' => 1, 'type' => 'text', 'title' => 'Plain', 'body' => 'x', 'updated' => 'Today', 'labels' => [99]]],
        ]));

        $this->assertStringContainsString('Plain', $html);
        $this->assertStringNotContainsString('data-note-label', $html);
    }

    public function testEscapes(): void
    {
        $html = $this->render($this->data([
            'notes' => [
                ['id' => 1, 'type' => 'text', 'title' => '<script>alert(1)</script>', 'body' => '<img src=x onerror=alert(2)>', 'updated' => '<b>now</b>', 'labels' => [1]],
                ['id' => 2, 'type' => 'checklist', 'title' => 'C', 'updated' => 'x', 'items' => [['label' => '<i>Eve</i>', 'done' => true]]],
                ['id' => 3, 'type' => 'image', 'title' => 'I', 'updated' => 'x', 'image' => ['art' => 'waves', 'alt' => '"><u>Alt</u>']],
            ],
            'labels' => [['id' => 1, 'name' => '<u>Work</u>', 'color' => 'blue', 'count' => 1]],
        ]), ['q' => '"><b>q</b>']);

        foreach (['<script>alert', '<img src=x', '<b>now', '<i>Eve', '<u>Work', '<u>Alt', '<b>q'] as $raw) {
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
            $this->assertSame('#', $href, 'Unexpected link ' . $href);
        }
        $this->assertStringNotContainsString('<form method="post"', $html);
        $this->assertDoesNotMatchRegularExpression('/\saction="(?!\/notes")/', $html);
    }

    public function testTranslatableStrings(): void
    {
        $files = (new BakeScreenCommand())->renderFiles('notes', 'Notes', 'Admin');
        $template = $files['templates/Notes/index.php'];

        $this->assertStringContainsString("__d('Admin', 'New note')", $template);
        $this->assertStringContainsString("__d('Admin', 'Grid view')", $template);
    }

    public function testNoInlineJsOrStyles(): void
    {
        $html = $this->render();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<style', $html);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function testDoesNotUseThirdPartyOrPlaceholderNames(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/notes/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/notes/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source);
    }
}
