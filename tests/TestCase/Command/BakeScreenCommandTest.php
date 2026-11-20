<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Command;

use BasecoatTheme\Bake\PhpLiteral;
use BasecoatTheme\Bake\ScreenRegistry;
use BasecoatTheme\Command\BakeScreenCommand;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use TestApp\Command\ScratchBakeScreenCommand;
use UnexpectedValueException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * `bin/cake bake screen` with the `demo` fixture screen (tests/test_app/templates/bake/Screen/demo).
 *
 * Everything is written to a per-test scratch dir under TMP: `templates/` via the first
 * `App.paths.templates` entry, `--plugin` output via `App.paths.plugins`, and app `src/` via
 * TestApp\Command\ScratchBakeScreenCommand (registered as `bake screen` by the test app), since
 * the real command writes to the `APP` constant (checked in testAppSourcesGoToApp).
 */
class BakeScreenCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    private const DEMO = ROOT . '/tests/test_app/templates/bake/Screen/demo';

    private string $scratch;

    private string $src;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scratch = TMP . 'bake_screen_' . bin2hex(random_bytes(4)) . DS;
        $templates = (array)Configure::read('App.paths.templates');
        Configure::write('App.paths.templates', array_merge([$this->scratch . 'templates' . DS], $templates));
        Configure::write('App.paths.plugins', [$this->scratch . 'plugins' . DS]);
        ScreenRegistry::register('demo', [
            'title' => 'Demo',
            'description' => 'Fixture screen.',
            'controller' => 'Demo',
            'path' => self::DEMO,
        ]);
        $this->src = $this->scratch . 'src' . DS;
        ScratchBakeScreenCommand::$srcRoot = $this->src;
    }

    protected function tearDown(): void
    {
        ScreenRegistry::reset();
        ScratchBakeScreenCommand::$srcRoot = null;
        $this->removeDir($this->scratch);
        parent::tearDown();
    }

    public function testBakesControllerAndTemplate(): void
    {
        $this->exec('bake screen demo Inbox');

        $this->assertExitSuccess();
        $controller = $this->src . 'Controller' . DS . 'InboxController.php';
        $template = $this->scratch . 'templates' . DS . 'Inbox' . DS . 'index.php';
        $this->assertFileExists($controller);
        $this->assertFileExists($template);
        $code = (string)file_get_contents($controller);
        $this->assertStringContainsString('namespace TestApp\Controller;', $code);
        $this->assertStringContainsString('class InboxController extends AppController', $code);
        $this->assertStringContainsString('public function index(): void', $code);
        $this->assertStringContainsString("\$this->set('items', [", $code);
        $this->assertStringNotContainsString('setTheme', $code);
        $view = (string)file_get_contents($template);
        $this->assertStringContainsString('<h1 data-screen="demo">Inbox</h1>', $view);
        $this->assertStringContainsString('@var \TestApp\View\AppView $this', $view);
        $this->assertStringContainsString('<?php foreach ($items as $item): ?>', $view);
        $this->assertStringNotContainsString('Café', $view, 'The view reads view variables, no data is inlined');
        $this->assertSame(0, $this->lintExitCode($controller));
        $this->assertSame(0, $this->lintExitCode($template));
    }

    public function testDefaultControllerComesFromTheRegistry(): void
    {
        $this->exec('bake screen demo');

        $this->assertExitSuccess();
        $this->assertFileExists($this->src . 'Controller' . DS . 'DemoController.php');
        $this->assertFileExists($this->scratch . 'templates' . DS . 'Demo' . DS . 'index.php');
    }

    public function testControllerLiteralRoundTrips(): void
    {
        $data = require self::DEMO . '/data.php';
        $literal = PhpLiteral::export($data['items']);
        $this->assertSame($data['items'], eval('return ' . $literal . ';'));

        $this->exec('bake screen demo Inbox');

        $this->assertExitSuccess();
        $code = (string)file_get_contents($this->src . 'Controller' . DS . 'InboxController.php');
        $this->assertStringContainsString(
            "\$this->set('items', " . PhpLiteral::export($data['items'], 2) . ');',
            $code,
        );
    }

    public function testRenderFilesHasNoSideEffects(): void
    {
        $command = new BakeScreenCommand();
        $this->loadPlugins(['BasecoatTheme']);
        $files = $command->renderFiles('demo', 'Inbox');

        $this->assertSame(['src/Controller/InboxController.php', 'templates/Inbox/index.php'], array_keys($files));
        $this->assertStringContainsString('class InboxController extends AppController', $files['src/Controller/InboxController.php']);
        $this->assertStringContainsString('<h1 data-screen="demo">Inbox</h1>', $files['templates/Inbox/index.php']);
        $this->assertDirectoryDoesNotExist($this->src . 'Controller');
        $this->assertDirectoryDoesNotExist($this->scratch);

        $plugin = $command->renderFiles('demo', 'Inbox', 'Fernway/Desk');
        $this->assertStringContainsString('namespace Fernway\Desk\Controller;', $plugin['src/Controller/InboxController.php']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function controllerNames(): array
    {
        return ['lowercase' => ['inbox'], 'camel' => ['Inbox']];
    }

    #[DataProvider('controllerNames')]
    public function testControllerNameIsCamelized(string $name): void
    {
        $this->exec('bake screen demo ' . $name);

        $this->assertExitSuccess();
        $this->assertFileExists($this->src . 'Controller' . DS . 'InboxController.php');
        $this->assertFileExists($this->scratch . 'templates' . DS . 'Inbox' . DS . 'index.php');
    }

    public function testMultiWordControllerName(): void
    {
        $this->exec('bake screen demo team_inbox');

        $this->assertExitSuccess();
        $this->assertFileExists($this->src . 'Controller' . DS . 'TeamInboxController.php');
        $this->assertFileExists($this->scratch . 'templates' . DS . 'TeamInbox' . DS . 'index.php');
    }

    public function testInvalidControllerName(): void
    {
        $this->exec('bake screen demo 9lives');

        $this->assertExitError();
        $this->assertErrorContains('Invalid controller name');
        $this->assertDirectoryDoesNotExist($this->src . 'Controller');
    }

    public function testUnknownScreen(): void
    {
        $this->exec('bake screen nope');

        $this->assertExitError();
        $this->assertErrorContains('Unknown screen `nope`');
        $this->assertErrorContains('users');
        $this->assertErrorContains('notifications');
        $this->assertDirectoryDoesNotExist($this->src . 'Controller');
        $this->assertDirectoryDoesNotExist($this->scratch);
    }

    public function testMissingScreenArgument(): void
    {
        $this->exec('bake screen');

        $this->assertExitError();
        $this->assertErrorContains('--list');
        $this->assertDirectoryDoesNotExist($this->src . 'Controller');
    }

    public function testList(): void
    {
        $this->exec('bake screen --list');

        $this->assertExitSuccess();
        foreach (ScreenRegistry::all() as $slug => $screen) {
            $this->assertOutputContains($slug);
            $this->assertOutputContains($screen['title']);
        }
        $this->assertOutputContains('demo');
        $this->assertDirectoryDoesNotExist($this->src . 'Controller');
    }

    public function testDoesNotOverwriteWithoutForce(): void
    {
        $this->exec('bake screen demo Inbox');
        $controller = $this->src . 'Controller' . DS . 'InboxController.php';
        file_put_contents($controller, '<?php // edited');

        $this->exec('bake screen demo Inbox', ['n']);

        $this->assertExitSuccess();
        $this->assertOutputContains('Skip');
        $this->assertSame('<?php // edited', file_get_contents($controller));
    }

    public function testForceOverwrites(): void
    {
        $this->exec('bake screen demo Inbox');
        $controller = $this->src . 'Controller' . DS . 'InboxController.php';
        file_put_contents($controller, '<?php // edited');

        $this->exec('bake screen demo Inbox --force');

        $this->assertExitSuccess();
        $this->assertStringContainsString('class InboxController', (string)file_get_contents($controller));
    }

    public function testPluginOption(): void
    {
        $this->exec('bake screen demo Inbox --plugin Fernway');

        $this->assertExitSuccess();
        $root = $this->scratch . 'plugins' . DS . 'Fernway' . DS;
        $controller = $root . 'src' . DS . 'Controller' . DS . 'InboxController.php';
        $this->assertFileExists($controller);
        $this->assertFileExists($root . 'templates' . DS . 'Inbox' . DS . 'index.php');
        $code = (string)file_get_contents($controller);
        $this->assertStringContainsString('namespace Fernway\Controller;', $code);
        $this->assertStringContainsString('class InboxController extends AppController', $code);
        $this->assertStringContainsString(
            '@var \Fernway\View\AppView $this',
            (string)file_get_contents($root . 'templates' . DS . 'Inbox' . DS . 'index.php'),
        );
        $this->assertDirectoryDoesNotExist($this->src . 'Controller');
    }

    public function testAppSourcesGoToApp(): void
    {
        $outputPath = new ReflectionMethod(BakeScreenCommand::class, 'outputPath');
        $command = new BakeScreenCommand();

        $this->assertSame(
            str_replace('/', DS, APP . 'Controller/InboxController.php'),
            $outputPath->invoke($command, 'src/Controller/InboxController.php', null),
        );
        $this->assertSame(
            str_replace('/', DS, rtrim($this->scratch . 'templates', DS) . '/Inbox/index.php'),
            $outputPath->invoke($command, 'templates/Inbox/index.php', null),
        );
        $this->assertSame(
            str_replace('/', DS, '/plugins/Fernway/src/Controller/InboxController.php'),
            $outputPath->invoke($command, 'src/Controller/InboxController.php', '/plugins/Fernway/'),
        );
    }

    public function testDataThatIsNotAnArray(): void
    {
        $dir = $this->scratch . 'screens' . DS . 'broken';
        mkdir($dir, 0777, true);
        file_put_contents($dir . DS . 'template.twig', '<p>{{ name }}</p>');
        file_put_contents($dir . DS . 'data.php', "<?php\nreturn 'not an array';\n");
        ScreenRegistry::register('broken', [
            'title' => 'Broken', 'description' => 'Bad data.', 'controller' => 'Broken', 'path' => $dir,
        ]);

        $this->exec('bake screen broken');

        $this->assertExitError();
        $this->assertErrorContains('must return an array');
        $this->assertDirectoryDoesNotExist($this->src);
    }

    public function testMissingData(): void
    {
        $dir = $this->scratch . 'screens' . DS . 'nodata';
        mkdir($dir, 0777, true);
        file_put_contents($dir . DS . 'template.twig', '<p>{{ name }}</p>');
        ScreenRegistry::register('nodata', [
            'title' => 'No data', 'description' => 'No data.php.', 'controller' => 'NoData', 'path' => $dir,
        ]);

        $this->exec('bake screen nodata');

        $this->assertExitError();
        $this->assertErrorContains('has no');
        $this->assertDirectoryDoesNotExist($this->src);
        $this->expectException(UnexpectedValueException::class);
        (new BakeScreenCommand())->renderFiles('nodata', 'NoData');
    }

    private function lintExitCode(string $file): int
    {
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);

        return $code;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
