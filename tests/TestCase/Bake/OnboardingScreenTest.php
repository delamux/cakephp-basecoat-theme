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
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Renders the baked `onboarding` screen template with its sample data (or custom data).
 */
class OnboardingScreenTest extends TestCase
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
        $files = (new BakeScreenCommand())->renderFiles('onboarding', 'Onboarding');
        $file = TMP . 'onboarding_screen_' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, $files['templates/Onboarding/index.php']);
        $this->assertSame(0, $this->lint($file), 'The baked template must be valid PHP');

        $request = new ServerRequest([
            'url' => '/onboarding',
            'query' => $query,
            'params' => ['plugin' => null, 'controller' => 'Onboarding', 'action' => 'index', 'pass' => []],
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
        $vars ??= ScreenRegistry::data('onboarding');

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
        return $override + ScreenRegistry::data('onboarding');
    }

    public function testSampleDataMatchesContract(): void
    {
        $data = ScreenRegistry::data('onboarding');

        $this->assertSame(['steps', 'workspace', 'invites', 'plans', 'plan', 'timezones', 'digests', 'preferences'], array_keys($data));
        $this->assertCount(5, $data['steps']);
        $this->assertArrayHasKey($data['plan'], $data['plans']);
        $this->assertArrayHasKey($data['preferences']['timezone'], $data['timezones']);
        $this->assertArrayHasKey($data['preferences']['digest'], $data['digests']);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function steps(): array
    {
        return ['1' => [1], '2' => [2], '3' => [3], '4' => [4], '5' => [5]];
    }

    #[DataProvider('steps')]
    public function testStepIndicator(int $step): void
    {
        $html = $this->render(null, ['step' => (string)$step]);

        $this->assertStringContainsString('<nav aria-label="Progress">', $html);
        $this->assertSame(1, substr_count($html, 'aria-current="step"'));
        preg_match_all('/<li [^>]*>.*?<\/li>/s', $html, $items);
        $this->assertCount(5, $items[0]);
        foreach ($items[0] as $i => $item) {
            $n = $i + 1;
            $this->assertSame($n === $step, str_contains($item, 'aria-current="step"'), 'Item ' . $n);
            $this->assertSame($n === $step, str_contains($item, '>Current<'), 'Current text ' . $n);
            $this->assertSame($n < $step, str_contains($item, '>Completed<'), 'Completed text ' . $n);
        }
        $this->assertStringContainsString('Step ' . $step . ' of 5', $html);
    }

    public function testStepIsClamped(): void
    {
        $this->assertStringContainsString('Step 1 of 5', $this->render());
        $this->assertStringContainsString('Step 1 of 5', $this->render(null, ['step' => '0']));
        $this->assertStringContainsString('Step 1 of 5', $this->render(null, ['step' => '-3']));
        $this->assertStringContainsString('Step 1 of 5', $this->render(null, ['step' => 'abc']));
        $this->assertStringContainsString('Step 1 of 5', $this->render(null, ['step' => ['x']]));
        $this->assertStringContainsString('Step 5 of 5', $this->render(null, ['step' => '99']));
    }

    public function testWorkspaceStep(): void
    {
        $html = $this->render(null, ['step' => '1']);

        // The page header is the layout's h1: the step title is an h2.
        $this->assertMatchesRegularExpression('/<h2[^>]*>\s*Name your workspace\s*<\/h2>/', $html);
        $this->assertStringNotContainsString('<h1', $html);
        // The slug hint is tied to its field.
        $this->assertMatchesRegularExpression('/<input[^>]*id="workspace-slug"[^>]*aria-describedby="onboarding-slug-hint"|<input[^>]*aria-describedby="onboarding-slug-hint"[^>]*id="workspace-slug"/', $html);
        $this->assertMatchesRegularExpression('/<p [^>]*id="onboarding-slug-hint"[^>]*>Lowercase letters, numbers and hyphens only.<\/p>/', $html);
        $this->assertMatchesRegularExpression('/<label[^>]*for="workspace-name"[^>]*>Workspace name<\/label>/', $html);
        $this->assertMatchesRegularExpression('/<label[^>]*for="workspace-slug"[^>]*>Workspace URL<\/label>/', $html);
        $this->assertStringContainsString('value="Harbor &amp; Pine"', $html);
        $this->assertStringContainsString('value="harbor-pine"', $html);
        $this->assertStringNotContainsString('>Back<', $html, 'No Back on the first step');
    }

    public function testFormsGetToIndexWithNextStep(): void
    {
        foreach ([1, 2, 3, 4] as $step) {
            $html = $this->render(null, ['step' => (string)$step]);

            $this->assertStringContainsString('method="get"', $html);
            $this->assertStringContainsString('action="/onboarding"', $html);
            $this->assertMatchesRegularExpression('/<input type="hidden" name="step"[^>]*value="' . ($step + 1) . '"/', $html, 'Step ' . $step);
            $this->assertMatchesRegularExpression('/<button type="submit"[^>]*>\s*Continue/', $html);
        }
    }

    public function testBackLinksToThePreviousStep(): void
    {
        $this->assertMatchesRegularExpression('/<a href="\/onboarding\?step=1"[^>]*>\s*<svg.*?<\/svg>\s*Back/s', $this->render(null, ['step' => '2']));
        $this->assertMatchesRegularExpression('/<a href="\/onboarding\?step=4"[^>]*>\s*<svg.*?<\/svg>\s*Back/s', $this->render(null, ['step' => '5']));
    }

    public function testTeamStepListsInvitesAndEmptyRows(): void
    {
        $html = $this->render(null, ['step' => '2']);

        $this->assertSame(3, substr_count($html, '<input type="email"'));
        $this->assertStringContainsString('value="elena.sarto@example.com"', $html);
        $this->assertStringContainsString('value="kofi.mensah@example.com"', $html);
        $this->assertStringContainsString('>Teammate 3</label>', $html);

        $many = $this->render($this->data(['invites' => ['a@example.com', 'b@example.com', 'c@example.com', 'd@example.com']]), ['step' => '2']);
        $this->assertSame(4, substr_count($many, '<input type="email"'));

        $none = $this->render($this->data(['invites' => []]), ['step' => '2']);
        $this->assertSame(3, substr_count($none, '<input type="email"'));
    }

    public function testPlanStepUsesChoiceCards(): void
    {
        $html = $this->render(null, ['step' => '3']);

        $this->assertMatchesRegularExpression('/<fieldset[^>]*>\s*<legend[^>]*>Plan<\/legend>/', $html);
        $this->assertSame(3, substr_count($html, 'type="radio"'));
        $this->assertMatchesRegularExpression('/<input[^>]*name="plan"[^>]*value="team"[^>]*checked/', $html);
        $this->assertStringContainsString('Starter', $html);
        $this->assertStringContainsString('Scale', $html);
    }

    public function testPreferencesStep(): void
    {
        $html = $this->render(null, ['step' => '4']);

        $this->assertMatchesRegularExpression('/<label[^>]*for="timezone"[^>]*>Timezone<\/label>/', $html);
        $this->assertMatchesRegularExpression('/<option value="Europe\/Madrid" selected="selected">Madrid/', $html);
        $this->assertMatchesRegularExpression('/<option value="weekly" selected="selected">Weekly<\/option>/', $html);
        $this->assertSame(2, substr_count($html, 'role="switch"'));
        $this->assertMatchesRegularExpression('/<input[^>]*name="mentions"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="product_updates"[^>]*checked/', $html);
        $this->assertMatchesRegularExpression('/<label[^>]*for="mentions"[^>]*>Email me when someone mentions me<\/label>/', $html);
    }

    public function testDoneStepSummaryAndInertFinish(): void
    {
        $html = $this->render(null, ['step' => '5']);

        $this->assertStringContainsString('Review and finish', $html);
        foreach (['Harbor &amp; Pine', 'harbor-pine', '2 people', 'Team', 'Madrid (UTC+1)', 'Weekly'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertMatchesRegularExpression('/<button type="button"[^>]*>\s*Finish\s*<svg.*?<\/svg>\s*<\/button>/s', $html);
        // `check` means done/read in the plugin: Finish moves forward with arrow-right.
        preg_match('/<button type="button"[^>]*>.*?<\/button>/s', $html, $finish);
        $this->assertStringContainsString('m12 5 7 7-7 7', $finish[0]);
        $this->assertStringNotContainsString('M20 6 9 17l-5-5', $finish[0]);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('>Continue<', $html);
    }

    public function testSingleInviteIsSingular(): void
    {
        $html = $this->render($this->data(['invites' => ['a@example.com']]), ['step' => '5']);

        $this->assertStringContainsString('1 person', $html);
    }

    public function testRendersWithEmptyData(): void
    {
        $empty = $this->data([
            'invites' => [],
            'plans' => [],
            'plan' => '',
            'timezones' => [],
            'digests' => [],
            'preferences' => [],
        ]);
        foreach ([1, 2, 3, 4, 5] as $step) {
            $html = $this->render($empty, ['step' => (string)$step]);
            $this->assertStringContainsString('Step ' . $step . ' of 5', $html);
        }
        $this->assertStringContainsString('0 people', $this->render($empty, ['step' => '5']));
    }

    public function testRendersWithoutStepsOrWorkspaceWithoutWarnings(): void
    {
        set_error_handler(function (int $level, string $message): bool {
            throw new \ErrorException($message, 0, $level);
        });
        try {
            $html = $this->render($this->data(['steps' => []]));
            $this->assertStringContainsString('Nothing to set up', $html);
            $this->assertStringNotContainsString('<form', $html);

            foreach ([1, 5] as $step) {
                $html = $this->render($this->data(['workspace' => []]), ['step' => (string)$step]);
                $this->assertStringContainsString('Step ' . $step . ' of 5', $html);
            }
        } finally {
            restore_error_handler();
        }
    }

    public function testEscapes(): void
    {
        $evil = '<script>alert(1)</script>';
        $data = $this->data([
            'steps' => ['workspace' => '<b>One</b>', 'team' => 'Two', 'plan' => 'Three', 'preferences' => 'Four', 'done' => 'Five'],
            'workspace' => ['name' => $evil, 'slug' => '"><img src=x>'],
            'invites' => ['<i>x</i>@example.com'],
            'plans' => ['team' => ['title' => '<u>Team</u>', 'description' => '<s>cheap</s>']],
            'plan' => 'team',
        ]);

        foreach ([1, 2, 3, 5] as $step) {
            $html = $this->render($data, ['step' => (string)$step]);
            foreach (['<script>alert', '<img src=x', '<b>One', '<i>x', '<u>Team', '<s>cheap'] as $raw) {
                $this->assertStringNotContainsString($raw, $html, 'Step ' . $step . ' ' . $raw);
            }
        }
        $this->assertStringContainsString('&lt;script&gt;alert(1)', $this->render($data, ['step' => '1']));
    }

    public function testUniqueIds(): void
    {
        foreach ([1, 2, 3, 4, 5] as $step) {
            $html = $this->render(null, ['step' => (string)$step]);

            preg_match_all('/\bid="([^"]+)"/', $html, $matches);
            $this->assertSame($matches[1], array_values(array_unique($matches[1])), 'Step ' . $step);
        }
    }

    public function testNoLinksToMissingActions(): void
    {
        foreach ([1, 2, 3, 4, 5] as $step) {
            $html = $this->render(null, ['step' => (string)$step]);

            preg_match_all('/href="([^"]*)"/', $html, $matches);
            foreach ($matches[1] as $href) {
                $this->assertMatchesRegularExpression('/^(#|\/onboarding\?step=[1-5])$/', $href, 'Unexpected link ' . $href);
            }
            $this->assertStringNotContainsString('method="post"', $html);
            $this->assertDoesNotMatchRegularExpression('/\saction="(?!\/onboarding")/', $html);
        }
    }

    public function testNoInlineJs(): void
    {
        $html = $this->render(null, ['step' => '3']);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString(' style=', $html);
    }

    public function testDoesNotUseThirdPartyOrPlaceholderNames(): void
    {
        $source = (string)file_get_contents(ROOT . '/templates/bake/Screen/onboarding/data.php')
            . (string)file_get_contents(ROOT . '/templates/bake/Screen/onboarding/template.twig');

        $this->assertDoesNotMatchRegularExpression('/Acme|Jane Doe|John Doe/', $source);
    }
}
