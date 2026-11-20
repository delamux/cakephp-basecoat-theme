<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\ChoiceCardsHelper;
use Cake\Http\ServerRequest;
use Cake\View\View;

class ChoiceCardsHelperTest extends HelperTestCase
{
    /**
     * @var array<string, string|array<string, string>>
     */
    private array $plans = [
        'starter' => ['title' => 'Starter', 'description' => 'For side projects'],
        'pro' => ['title' => 'Team', 'description' => 'For small teams'],
        'enterprise' => 'Enterprise',
    ];

    public function testRender(): void
    {
        $helper = new ChoiceCardsHelper($this->View);
        $html = $helper->render('plan', $this->plans, ['legend' => 'Choose a plan']);

        $this->assertStringStartsWith('<fieldset role="radiogroup"', $html);
        $this->assertStringContainsString('<legend', $html);
        $this->assertStringContainsString('Choose a plan', $html);
        $this->assertSame(3, substr_count($html, 'type="radio"'));
        $this->assertSame(3, substr_count($html, 'name="plan"'));
        $this->assertStringContainsString('class="sr-only peer"', $html);
        $this->assertStringContainsString('grid gap-3 sm:grid-cols-3', $html);
        $this->assertStringContainsString('has-[:checked]:border-primary', $html);
        $this->assertStringContainsString('has-[:focus-visible]:ring-2', $html);
        $this->assertStringContainsString('For small teams', $html);
        $this->assertSame(3, substr_count($html, '<label'));
        $this->assertStringContainsString('value="enterprise"', $html);
    }

    public function testColumns(): void
    {
        $helper = new ChoiceCardsHelper($this->View);
        $this->assertStringContainsString('sm:grid-cols-2', $helper->render('plan', $this->plans, ['columns' => 2]));
    }

    public function testSelected(): void
    {
        $helper = new ChoiceCardsHelper($this->View);
        $html = $helper->render('plan', $this->plans, ['value' => 'pro']);
        $this->assertSame(1, substr_count($html, 'checked="checked"'));
        $this->assertMatchesRegularExpression('#value="pro"[^>]*checked="checked"|checked="checked"[^>]*value="pro"#', $html);
    }

    public function testSelectedFromRequestData(): void
    {
        $request = (new ServerRequest(['url' => '/']))->withParsedBody(['plan' => 'starter']);
        $helper = new ChoiceCardsHelper(new View($request));
        $html = $helper->render('plan', $this->plans);
        $this->assertMatchesRegularExpression('#value="starter"[^>]*checked="checked"|checked="checked"[^>]*value="starter"#', $html);
    }

    public function testRequiredAndIcon(): void
    {
        $helper = new ChoiceCardsHelper($this->View);
        $html = $helper->render('pay', ['card' => ['title' => 'Card', 'icon' => 'credit-card']], ['required' => true]);
        $this->assertStringContainsString('required="required"', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    public function testDisabled(): void
    {
        $helper = new ChoiceCardsHelper($this->View);
        $html = $helper->render('plan', ['a' => ['title' => 'A', 'disabled' => true], 'b' => 'B']);
        $this->assertSame(1, substr_count($html, 'disabled="disabled"'));
        $this->assertStringContainsString('cursor-not-allowed', $html);
    }

    public function testUniqueIds(): void
    {
        $helper = new ChoiceCardsHelper($this->View);
        $html = $helper->render('plan', $this->plans) . $helper->render('plan', $this->plans);
        preg_match_all('/ id="([^"]+)"/', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertCount(count(array_unique($matches[1])), $matches[1]);
        $this->assertStringContainsString(' id="my-group"', $helper->render('x', $this->plans, ['id' => 'my-group']));
    }

    public function testEscapes(): void
    {
        $helper = new ChoiceCardsHelper($this->View);
        $html = $helper->render('plan', [
            'a"b' => ['title' => '<script>x</script>', 'description' => '<b>d</b>'],
        ], ['legend' => '<i>L</i>']);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<b>d', $html);
        $this->assertStringNotContainsString('<i>L', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('value="a&quot;b"', $html);
    }

    public function testEmpty(): void
    {
        $helper = new ChoiceCardsHelper($this->View);
        $this->assertSame('', $helper->render('plan', []));
    }
}
