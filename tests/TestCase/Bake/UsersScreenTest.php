<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\Bake;

use BasecoatTheme\Bake\ScreenRegistry;
use BasecoatTheme\Command\BakeScreenCommand;
use BasecoatTheme\Test\TestCase\View\Helper\HelperTestCase;
use Cake\View\View;

class UsersScreenTest extends HelperTestCase
{
    private string $file = '';

    protected function tearDown(): void
    {
        if ($this->file !== '' && is_file($this->file)) {
            unlink($this->file);
        }
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $override
     */
    private function renderScreen(array $override = []): string
    {
        $files = (new BakeScreenCommand())->renderFiles('users', 'Users');
        $this->file = tempnam(sys_get_temp_dir(), 'users-screen-') . '.php';
        file_put_contents($this->file, $files['templates/Users/index.php']);

        $view = $this->View;
        foreach (['PageHeader', 'TableToolbar', 'Table', 'Avatar', 'Badge', 'DropdownMenu', 'Button', 'Icon', 'EmptyState', 'Form'] as $helper) {
            $view->loadHelper('BasecoatTheme.' . $helper);
        }
        $view->set($override + ScreenRegistry::data('users'));

        return $this->renderFile($view, $this->file);
    }

    private function renderFile(View $view, string $file): string
    {
        $method = new \ReflectionMethod($view, '_evaluate');

        $vars = [];
        foreach ($view->getVars() as $name) {
            $vars[$name] = $view->get($name);
        }

        return (string)$method->invoke($view, $file, $vars);
    }

    public function testRendersAllUsers(): void
    {
        $html = $this->renderScreen();
        foreach (ScreenRegistry::data('users')['users'] as $user) {
            $this->assertStringContainsString(h($user['name']), $html);
            $this->assertStringContainsString($user['email'], $html);
        }
        $this->assertStringContainsString('<table', $html);
    }

    public function testStatusBadges(): void
    {
        $html = $this->renderScreen();
        $labels = ScreenRegistry::data('users')['statuses'];
        $this->assertSame(['active', 'invited', 'suspended'], array_keys($labels));
        foreach ($labels as $label) {
            $this->assertStringContainsString('class="badge', $html);
            $this->assertMatchesRegularExpression('/<span class="badge[^>]*>' . preg_quote($label, '/') . '<\/span>/', $html);
        }
    }

    public function testToolbarSearchGetsToIndex(): void
    {
        $html = $this->renderScreen();
        $this->assertMatchesRegularExpression('/<form[^>]*method="get"/', $html);
        $this->assertStringContainsString('type="search"', $html);
    }

    public function testNoLinksToMissingActions(): void
    {
        $html = $this->renderScreen();
        $this->assertDoesNotMatchRegularExpression('#href="[^"]*/users/(add|edit|delete|view)#', $html);
        $this->assertMatchesRegularExpression('#<a[^>]*href="\#"[^>]*>\s*(<svg.*?</svg>)?\s*Add user#s', $html);
    }

    public function testRendersWithEmptyData(): void
    {
        $html = $this->renderScreen(['users' => [], 'total' => 0, 'page' => 1]);
        $this->assertStringContainsString('No users found', $html);
        $this->assertStringNotContainsString('Warning', $html);
    }

    public function testEscapes(): void
    {
        $html = $this->renderScreen(['users' => [[
            'id' => 1, 'name' => '<b>Ana</b>', 'email' => 'ana@example.com', 'role' => 'Admin',
            'plan' => 'Growth', 'location' => 'Porto', 'status' => 'active',
        ]]]);
        $this->assertStringNotContainsString('<b>Ana</b>', $html);
        $this->assertStringContainsString('&lt;b&gt;Ana&lt;/b&gt;', $html);
    }

    public function testCheckboxesAndMenuTriggersAreNamed(): void
    {
        $html = $this->renderScreen();
        $this->assertStringContainsString('aria-label="Select all users"', $html);
        $this->assertStringContainsString('aria-label="Select Elena Sarto"', $html);
        $this->assertStringContainsString('<span class="sr-only">Actions for Elena Sarto</span>', $html);
        $this->assertStringContainsString('<th class="w-10"><span class="sr-only">Actions</span></th>', $html);
        $this->assertStringContainsString('name="perPage"', $html);
    }

    public function testPagination(): void
    {
        $this->View->setRequest($this->View->getRequest()->withQueryParams(['q' => 'an', 'status' => 'active']));
        $html = $this->renderScreen();
        $this->assertMatchesRegularExpression('/<button disabled="disabled"[^>]*>(?:(?!<\/button>).)*Previous<\/button>/s', $html);
        $this->assertMatchesRegularExpression('/<a href="[^"]*page=2[^"]*"/', $html);
        preg_match('/<a href="([^"]*page=2[^"]*)"/', $html, $m);
        $this->assertStringContainsString('q=an', html_entity_decode($m[1]));
        $this->assertStringContainsString('status=active', html_entity_decode($m[1]));
        $this->assertMatchesRegularExpression('/<a [^>]*page=2[^>]*>\s*Next\s*<svg[^>]*><path d="m9 18 6-6-6-6"/s', $html, 'The Next icon goes after the text');
    }
}
