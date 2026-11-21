<?php
declare(strict_types=1);

namespace BasecoatTheme\Test\TestCase\View\Helper;

use BasecoatTheme\View\Helper\FlowHelper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class FlowHelperTest extends HelperTestCase
{
    /**
     * Trigger → condition (yes → action, no → action), positions out of reading order.
     *
     * @return array<string, mixed>
     */
    private function flow(): array
    {
        return [
            'nodes' => [
                ['id' => 'no-path', 'type' => 'action', 'label' => 'Add to list', 'icon' => 'list-plus', 'position' => ['x' => 160, 'y' => 300]],
                ['id' => 'start', 'type' => 'trigger', 'label' => 'Signed up', 'icon' => 'user-plus', 'position' => ['x' => 0, 'y' => 0], 'properties' => ['event' => 'customer.created']],
                ['id' => 'plan', 'type' => 'condition', 'label' => 'Plan?', 'position' => ['x' => 0, 'y' => 150], 'properties' => ['days' => 3]],
                ['id' => 'yes-path', 'type' => 'action', 'label' => 'Assign manager', 'position' => ['x' => -160, 'y' => 300]],
            ],
            'edges' => [
                ['source' => 'plan', 'output' => 'no', 'target' => 'no-path'],
                ['source' => 'start', 'target' => 'plan'],
                ['source' => 'plan', 'output' => 'yes', 'target' => 'yes-path'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function block(string $html, string $name): array
    {
        $this->assertSame(1, preg_match('#<script type="application/json" data-flow-' . $name . '>(.*?)</script>#s', $html, $m));

        return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
    }

    public function testEditorStructure(): void
    {
        $html = (new FlowHelper($this->View))->editor($this->flow());

        $this->assertStringStartsWith('<div data-flow-editor="1" role="group" aria-label="Flow editor" class="bc-flow-editor">', $html);
        $this->assertStringContainsString('<div data-flow-canvas hidden></div>', $html);
        $this->assertStringContainsString('<div data-flow-fallback>', $html);
        $this->assertStringContainsString('<template data-flow-icons>', $html);
        $this->assertStringNotContainsString('style=', $html, 'CSP: no inline styles');
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*type="application\/json")/', $html, 'CSP: only JSON data blocks');
    }

    public function testDataIsNormalized(): void
    {
        $data = $this->block((new FlowHelper($this->View))->editor($this->flow()), 'data');

        $this->assertSame(['no-path', 'start', 'plan', 'yes-path'], array_column($data['nodes'], 'id'));
        $this->assertSame(
            ['id' => 'start', 'type' => 'trigger', 'label' => 'Signed up', 'icon' => 'user-plus', 'position' => ['x' => 0, 'y' => 0], 'properties' => ['event' => 'customer.created']],
            $data['nodes'][1],
        );
        $this->assertSame(['yes' => 'Yes', 'no' => 'No'], $data['nodes'][2]['outputs'], 'Conditions default to yes / no');
        $this->assertSame(['days' => 3], $data['nodes'][2]['properties'], 'Integers stay integers');
        $this->assertSame(['id' => 'plan:no->no-path', 'source' => 'plan', 'output' => 'no', 'target' => 'no-path'], $data['edges'][0]);
        $this->assertSame(['id' => 'start->plan', 'source' => 'start', 'target' => 'plan'], $data['edges'][1]);
    }

    public function testEmptyPropertiesAreJsonObjects(): void
    {
        $html = (new FlowHelper($this->View))->editor([
            'nodes' => [['id' => 'a', 'type' => 'action', 'label' => 'A', 'position' => ['x' => 0, 'y' => 0]]],
        ], ['palette' => [['label' => 'G', 'items' => [['type' => 'action', 'label' => 'B']]]]]);

        $this->assertStringContainsString('"properties":{}', $html);
        $this->assertStringNotContainsString('"properties":[]', $html);
        $this->assertStringContainsString('"fields":{}', $html);
    }

    public function testHiddenInputMirrorsTheData(): void
    {
        $helper = new FlowHelper($this->View);
        $html = $helper->editor($this->flow());
        $this->assertSame(1, preg_match('/<input type="hidden" name="flow" value="([^"]*)" data-flow-input="1">/', $html, $m));
        $this->assertSame($this->block($html, 'data'), json_decode(html_entity_decode($m[1]), true));

        $this->assertStringContainsString('name="workflow[steps]"', $helper->editor($this->flow(), ['name' => 'workflow[steps]']));
        $this->assertStringNotContainsString('<input', $helper->editor($this->flow(), ['name' => false]));
    }

    public function testFallbackListsStepsInReadingOrder(): void
    {
        $html = $this->squash((new FlowHelper($this->View))->editor($this->flow()));

        $this->assertStringContainsString(
            '<ol class="bc-flow-fallback">'
            . '<li data-step-type="trigger"><span class="bc-flow-fallback-label">Signed up</span><span class="bc-flow-kicker">Trigger</span><ul class="bc-flow-fallback-next"><li>Plan?</li></ul></li>'
            . '<li data-step-type="condition"><span class="bc-flow-fallback-label">Plan?</span><span class="bc-flow-kicker">Condition</span><ul class="bc-flow-fallback-next"><li>Yes: Assign manager</li><li>No: Add to list</li></ul></li>'
            . '<li data-step-type="action"><span class="bc-flow-fallback-label">Assign manager</span><span class="bc-flow-kicker">Action</span></li>'
            . '<li data-step-type="action"><span class="bc-flow-fallback-label">Add to list</span><span class="bc-flow-kicker">Action</span></li>'
            . '</ol>',
            $html,
        );
    }

    public function testUnreachedStepsFollowTopToBottom(): void
    {
        $html = (new FlowHelper($this->View))->editor(['nodes' => [
            ['id' => 'b', 'type' => 'action', 'label' => 'Lower', 'position' => ['x' => 0, 'y' => 200]],
            ['id' => 'a', 'type' => 'action', 'label' => 'Upper', 'position' => ['x' => 0, 'y' => 10]],
        ]]);

        $this->assertLessThan(strpos($html, '>Lower<'), strpos($html, '>Upper<'));
    }

    public function testEmptyFlow(): void
    {
        $html = (new FlowHelper($this->View))->editor([]);

        $this->assertStringContainsString('No steps yet.', $html);
        $this->assertSame(['nodes' => [], 'edges' => []], $this->block($html, 'data'));
    }

    public function testEscaping(): void
    {
        $html = (new FlowHelper($this->View))->editor(['nodes' => [
            ['id' => 'x', 'type' => 'action', 'label' => '</script><b>Bold & co</b>', 'position' => ['x' => 0, 'y' => 0]],
        ]], ['label' => '"quoted"']);

        $this->assertStringNotContainsString('</script><b>', $html);
        $this->assertStringContainsString('\\u003C/script\\u003E\\u003Cb\\u003EBold \\u0026 co', $html, 'JSON is safe inside <script>');
        $this->assertStringContainsString('&lt;/script&gt;&lt;b&gt;Bold &amp; co&lt;/b&gt;', $html, 'Fallback text is escaped');
        $this->assertStringContainsString('aria-label="&quot;quoted&quot;"', $html);
    }

    public function testIconsTemplate(): void
    {
        $html = (new FlowHelper($this->View))->editor($this->flow(), [
            'palette' => [['label' => 'Actions', 'items' => [['type' => 'action', 'label' => 'Email', 'icon' => 'mail']]]],
        ]);
        $this->assertSame(1, preg_match('#<template data-flow-icons>(.*?)</template>#s', $html, $m));
        preg_match_all('/<svg[^>]* data-icon="([^"]+)"/', $m[1], $icons);

        $this->assertSame(['zap', 'circle-check', 'split', 'list-plus', 'user-plus', 'mail'], $icons[1]);
        $this->assertStringNotContainsString('class=', $m[1], 'Sized by basecoat-flow.css, no Tailwind class');
    }

    public function testConfig(): void
    {
        $config = $this->block((new FlowHelper($this->View))->editor($this->flow(), [
            'readonly' => true,
            'fields' => ['event' => 'Event'],
            'i18n' => ['panel.delete' => 'Remove'],
            'palette' => [['label' => 'Logic', 'items' => [['type' => 'condition', 'label' => 'If', 'outputs' => ['a' => 'A', 'b' => 'B']]]]],
        ]), 'config');

        $this->assertTrue($config['readonly']);
        $this->assertSame(['event' => 'Event'], $config['fields']);
        $this->assertSame('Remove', $config['i18n']['panel.delete']);
        $this->assertSame('Add a step', $config['i18n']['palette.title']);
        $this->assertSame(['label' => 'If', 'type' => 'condition', 'properties' => [], 'outputs' => ['a' => 'A', 'b' => 'B']], [
            'label' => $config['palette'][0]['items'][0]['label'],
            'type' => $config['palette'][0]['items'][0]['type'],
            'properties' => $config['palette'][0]['items'][0]['properties'],
            'outputs' => $config['palette'][0]['items'][0]['outputs'],
        ]);
        $this->assertStringContainsString('{direction}', $config['ariaLabels']['node.a11yDescription.ariaLiveMessage']);
    }

    public function testAssetsGoToTheViewBlocksOnce(): void
    {
        $helper = new FlowHelper($this->View);
        $helper->editor($this->flow());
        $helper->editor($this->flow());

        $this->assertSame(1, substr_count($this->View->fetch('css'), 'basecoat-flow.css'));
        $this->assertSame(1, substr_count($this->View->fetch('script'), 'basecoat-flow.js'));
        $this->assertStringContainsString('defer', $this->View->fetch('script'));
    }

    public function testAssetsCanBeSkipped(): void
    {
        (new FlowHelper($this->View))->editor($this->flow(), ['assets' => false]);

        $this->assertSame('', $this->View->fetch('css'));
        $this->assertSame('', $this->View->fetch('script'));
    }

    public function testAttributesOptionIsMergedAndConfigKeysAreNotAttributes(): void
    {
        $html = (new FlowHelper($this->View))->editor($this->flow(), [
            'attributes' => ['data-x' => '1'],
            'templates' => ['editor' => '<section>{{data}}</section>'],
            'classes' => ['fallback' => 'x'],
        ]);

        $this->assertStringStartsWith('<div data-flow-editor="1" role="group" aria-label="Flow editor" data-x="1" class="bc-flow-editor">', $html);
        $this->assertDoesNotMatchRegularExpression('/\s(attributes|templates|classes)=/', $html);
    }

    public function testRootOptions(): void
    {
        $html = (new FlowHelper($this->View))->editor($this->flow(), ['class' => 'mt-4', 'data-turbo' => 'false', 'label' => 'Onboarding']);

        $this->assertStringStartsWith('<div data-flow-editor="1" role="group" aria-label="Onboarding" data-turbo="false" class="bc-flow-editor mt-4">', $html);
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidFlows(): array
    {
        $a = ['id' => 'a', 'type' => 'trigger', 'label' => 'A', 'position' => ['x' => 0, 'y' => 0]];
        $b = ['id' => 'b', 'type' => 'action', 'label' => 'B', 'position' => ['x' => 0, 'y' => 100]];
        $c = ['id' => 'c', 'type' => 'condition', 'label' => 'C', 'position' => ['x' => 0, 'y' => 200]];

        return [
            'unknown type' => [['nodes' => [['type' => 'loop'] + $a]], '`type` must be one of trigger, action, condition'],
            'missing id' => [['nodes' => [['id' => ''] + $a]], 'needs a non-empty `id`'],
            'duplicate id' => [['nodes' => [$a, $a]], '`a` is used twice'],
            'missing position' => [['nodes' => [['position' => null] + $a]], 'needs a `position` with finite numeric `x` and `y`'],
            'unknown icon' => [['nodes' => [['icon' => 'no-such-icon'] + $a]], 'unknown icon `no-such-icon`'],
            'unknown target' => [['nodes' => [$a], 'edges' => [['source' => 'a', 'target' => 'zz']]], 'target `zz` is not a node'],
            'self loop' => [['nodes' => [$b], 'edges' => [['source' => 'b', 'target' => 'b']]], 'connects `b` to itself'],
            'into a trigger' => [['nodes' => [$a, $b], 'edges' => [['source' => 'b', 'target' => 'a']]], 'trigger `a` cannot be a target'],
            'condition without output' => [['nodes' => [$c, $b], 'edges' => [['source' => 'c', 'target' => 'b']]], '`output` must be one of the outputs of `c` (yes, no)'],
            'output on an action' => [['nodes' => [$a, $b], 'edges' => [['source' => 'a', 'output' => 'yes', 'target' => 'b']]], '`a` has no named outputs'],
            'duplicate edge id' => [
                ['nodes' => [$a, $b, ['id' => 'd'] + $b], 'edges' => [['id' => 'e', 'source' => 'a', 'target' => 'b'], ['id' => 'e', 'source' => 'b', 'target' => 'd']]],
                'Flow edge id `e` is used twice',
            ],
            'infinite position' => [['nodes' => [['position' => ['x' => '1e400', 'y' => 0]] + $a]], 'finite numeric `x` and `y`'],
            'array label' => [['nodes' => [['label' => ['x']] + $a]], '`label` must be a string'],
            'array property' => [['nodes' => [['properties' => ['to' => ['x']]] + $a]], 'property `to` must be a string or a number'],
            'two edges from one output' => [
                ['nodes' => [$a, $b, ['id' => 'd'] + $b], 'edges' => [['source' => 'a', 'target' => 'b'], ['source' => 'a', 'target' => 'd']]],
                'the output of `a` already leads to another step',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $flow
     */
    #[DataProvider('invalidFlows')]
    public function testInvalidFlowsAreRejected(array $flow, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        (new FlowHelper($this->View))->editor($flow);
    }

    public function testInvalidPaletteIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Flow palette item #0 of group #0: `type` must be one of');
        (new FlowHelper($this->View))->editor([], ['palette' => [['label' => 'G', 'items' => [['type' => 'nope']]]]]);
    }
}
