<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use InvalidArgumentException;
use function Cake\Core\h;

/**
 * Flow editor: steps (nodes) linked by connections (edges) on a React Flow canvas, with a palette to
 * add steps and a side panel to edit them. The editor is the plugin's precompiled bundle
 * (`webroot/js/basecoat-flow.js` + `webroot/css/basecoat-flow.css`): apps write no React and need no npm.
 * Without JavaScript the steps are listed in reading order with where each one leads.
 *
 * ```
 * echo $this->Flow->editor([
 *     'nodes' => [
 *         ['id' => 'signed-up', 'type' => 'trigger', 'label' => 'Customer signed up', 'icon' => 'user-plus',
 *             'position' => ['x' => 0, 'y' => 0], 'properties' => ['event' => 'customer.created']],
 *         ['id' => 'welcome', 'type' => 'action', 'label' => 'Send welcome email', 'icon' => 'mail',
 *             'position' => ['x' => 0, 'y' => 160]],
 *     ],
 *     'edges' => [
 *         ['source' => 'signed-up', 'target' => 'welcome'],
 *     ],
 * ], ['name' => 'flow', 'palette' => [...], 'fields' => ['event' => 'Event']]);
 * ```
 *
 * ### Data
 *
 * - `nodes`: `id` (unique), `type` (`trigger`: no input; `action`; `condition`: one output per `outputs`
 *   entry, default yes / no), `label`, `position` (`x`, `y` in canvas pixels), optional `icon` (IconHelper
 *   name), `properties` (key => string|int, edited as text fields) and, for a condition, `outputs`
 *   (key => label).
 * - `edges`: `source`, `target`, `output` (required for a condition source: one of its `outputs` keys),
 *   optional `id`. One connection per output; a trigger cannot be a target.
 *
 * ### Options
 *
 * - `name`: Name of the hidden input kept in sync with the edited flow (JSON, same format as `$flow`),
 *   default `flow`; `false` for no input.
 * - `palette`: Groups of step templates: `[['label' => 'Actions', 'items' => [['type' => 'action',
 *   'label' => 'Send email', 'icon' => 'mail', 'properties' => ['to' => '']]]]]`.
 * - `fields`: Labels of the properties in the side panel (key => label).
 * - `readonly`: View only (select and inspect; no moving, connecting, adding or editing).
 * - `label`: Accessible name of the editor (default "Flow editor").
 * - `i18n`: Overrides of the editor texts (keys of texts()).
 * - `assets`: Add the bundle to the `css` / `script` view blocks (default true).
 * - `class`, `escape`, and any other key as an HTML attribute of the root element (also given as an
 *   `attributes` array). `templates`, `classes` and `kindIcons` are helper config (`addHelper()` /
 *   `setConfig()`), as for every component.
 *
 * Changes dispatch `basecoat:flow-change` on the root element (`detail.flow`, `detail.action`).
 *
 * @property \BasecoatTheme\View\Helper\IconHelper $Icon
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class FlowHelper extends AbstractComponentHelper
{
    /**
     * Node types.
     *
     * @var array<string>
     */
    public const KINDS = ['trigger', 'action', 'condition'];

    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['BasecoatTheme.Icon', 'Html'];

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'name' => 'flow',
        'assets' => true,
        // Icon of a step without `icon`, per type (also used by the palette)
        'kindIcons' => ['trigger' => 'zap', 'action' => 'circle-check', 'condition' => 'split'],
        'classes' => [
            'fallback' => null,
        ],
        'templates' => [
            'editor' => '<div{{attrs}}>{{data}}{{config}}{{icons}}{{input}}{{canvas}}{{fallback}}</div>',
            'data' => '<script type="application/json" data-flow-data>{{content}}</script>',
            'config' => '<script type="application/json" data-flow-config>{{content}}</script>',
            'icons' => '<template data-flow-icons>{{content}}</template>',
            'input' => '<input type="hidden"{{attrs}}>',
            'canvas' => '<div data-flow-canvas hidden></div>',
            'fallback' => '<div data-flow-fallback>{{content}}</div>',
            'list' => '<ol{{attrs}}>{{content}}</ol>',
            'item' => '<li{{attrs}}><span class="bc-flow-fallback-label">{{label}}</span> <span class="bc-flow-kicker">{{kind}}</span>{{next}}</li>',
            'next' => '<ul class="bc-flow-fallback-next">{{content}}</ul>',
            'nextItem' => '<li>{{content}}</li>',
            'empty' => '<p{{attrs}}>{{content}}</p>',
        ],
    ];

    /**
     * Renders the flow editor.
     *
     * @param array<string, mixed> $flow `nodes` and `edges` (see the class docblock).
     * @param array<string, mixed> $options Options.
     * @return string
     * @throws \InvalidArgumentException For an invalid flow or palette (the message says what is wrong).
     */
    public function editor(array $flow, array $options = []): string
    {
        $escape = (bool)($options['escape'] ?? true);
        $nodes = $this->nodes((array)($flow['nodes'] ?? []));
        $edges = $this->edges((array)($flow['edges'] ?? []), $nodes);
        $palette = $this->palette((array)($options['palette'] ?? []));
        $data = ['nodes' => array_values($nodes), 'edges' => $edges];

        if ($options['assets'] ?? $this->getConfig('assets')) {
            $this->Html->css('BasecoatTheme.basecoat-flow', ['block' => true]);
            $this->Html->script('BasecoatTheme.basecoat-flow', ['block' => true, 'defer' => true]);
        }

        $config = [
            'readonly' => (bool)($options['readonly'] ?? false),
            'palette' => $palette,
            'fields' => (object)array_map('strval', (array)($options['fields'] ?? [])),
            'i18n' => (object)(array_map('strval', (array)($options['i18n'] ?? [])) + $this->texts()),
            'ariaLabels' => (object)$this->ariaLabels(),
        ];

        $name = $options['name'] ?? $this->getConfig('name');
        $input = '';
        if ($name !== false && $name !== null && $name !== '') {
            $input = $this->formatTemplate('input', ['attrs' => $this->formatAttributes([
                'name' => (string)$name,
                'value' => $this->json($data),
                'data-flow-input' => true,
            ])]);
        }

        $attributes = $this->rootAttributes('bc-flow-editor', [
            'data-flow-editor' => true,
            'role' => 'group',
            'aria-label' => (string)($options['label'] ?? __d('basecoat_theme', 'Flow editor')),
        ] + (array)($options['attributes'] ?? []) + $options, [
            'name', 'palette', 'fields', 'readonly', 'label', 'i18n', 'assets', 'attributes', 'templates', 'classes',
        ]);

        return $this->renderTemplate('editor', $attributes, [
            'data' => $this->formatTemplate('data', ['content' => $this->json($data)]),
            'config' => $this->formatTemplate('config', ['content' => $this->json($config)]),
            'icons' => $this->formatTemplate('icons', ['content' => $this->icons($nodes, $palette)]),
            'input' => $input,
            'canvas' => $this->formatTemplate('canvas', []),
            'fallback' => $this->formatTemplate('fallback', ['content' => $this->fallback($nodes, $edges, $escape)]),
        ], $options['templateVars'] ?? []);
    }

    /**
     * Texts of the editor (keys used by basecoat-flow.js), translated.
     *
     * @return array<string, string>
     */
    public function texts(): array
    {
        return [
            'kind.trigger' => __d('basecoat_theme', 'Trigger'),
            'kind.action' => __d('basecoat_theme', 'Action'),
            'kind.condition' => __d('basecoat_theme', 'Condition'),
            'palette.title' => __d('basecoat_theme', 'Add a step'),
            'palette.hint' => __d('basecoat_theme', 'Drag a step onto the canvas, or press it to add it after the selected step.'),
            'panel.steps' => __d('basecoat_theme', 'Steps'),
            'panel.stepsHint' => __d('basecoat_theme', 'Choose a step to edit it.'),
            'panel.empty' => __d('basecoat_theme', 'No steps yet. Add one from the palette.'),
            'panel.name' => __d('basecoat_theme', 'Name'),
            'panel.next' => __d('basecoat_theme', 'Next step'),
            'panel.nextFor' => __d('basecoat_theme', 'Next step: {output}'),
            'panel.none' => __d('basecoat_theme', 'None'),
            'panel.delete' => __d('basecoat_theme', 'Delete step'),
            'panel.back' => __d('basecoat_theme', 'All steps'),
            'status.added' => __d('basecoat_theme', '{label} added.'),
            'status.deleted' => __d('basecoat_theme', '{label} deleted.'),
            'status.connected' => __d('basecoat_theme', '{source} now leads to {target}.'),
            'status.disconnected' => __d('basecoat_theme', '{source} no longer leads to another step.'),
            'canvas.label' => __d('basecoat_theme', 'Flow canvas'),
            'canvas.edge' => __d('basecoat_theme', 'Connection from {source} to {target}'),
            'canvas.edgeOutput' => __d('basecoat_theme', 'Connection from {source}, {output}, to {target}'),
        ];
    }

    /**
     * React Flow's own accessible texts, translated (`{direction}`, `{x}`, `{y}` are filled by the bundle).
     *
     * @return array<string, string>
     */
    protected function ariaLabels(): array
    {
        return [
            'node.a11yDescription.default' => __d('basecoat_theme', 'Press enter or space to select a step. Press delete to remove it and escape to cancel.'),
            'node.a11yDescription.keyboardDisabled' => __d('basecoat_theme', 'Press enter or space to select a step. You can then use the arrow keys to move it. Press delete to remove it and escape to cancel.'),
            'node.a11yDescription.ariaLiveMessage' => __d('basecoat_theme', 'Moved the selected step {direction}. New position, x: {x}, y: {y}'),
            'edge.a11yDescription.default' => __d('basecoat_theme', 'Press enter or space to select a connection. You can then press delete to remove it or escape to cancel.'),
            'node.a11yDescription.readonly' => __d('basecoat_theme', 'Press enter or space to select a step and see its details.'),
            'edge.a11yDescription.readonly' => __d('basecoat_theme', 'Press enter or space to select a connection.'),
            'controls.ariaLabel' => __d('basecoat_theme', 'Canvas controls'),
            'controls.zoomIn.ariaLabel' => __d('basecoat_theme', 'Zoom in'),
            'controls.zoomOut.ariaLabel' => __d('basecoat_theme', 'Zoom out'),
            'controls.fitView.ariaLabel' => __d('basecoat_theme', 'Fit view'),
            'controls.interactive.ariaLabel' => __d('basecoat_theme', 'Toggle interactivity'),
            'minimap.ariaLabel' => __d('basecoat_theme', 'Mini map'),
            'handle.ariaLabel' => __d('basecoat_theme', 'Connection point'),
        ];
    }

    /**
     * Validates and normalizes the nodes (keyed by id).
     *
     * @param array<mixed> $nodes Nodes.
     * @return array<string, array<string, mixed>>
     */
    protected function nodes(array $nodes): array
    {
        $out = [];
        foreach (array_values($nodes) as $index => $node) {
            if (!is_array($node)) {
                throw new InvalidArgumentException(sprintf('Flow node #%d must be an array.', $index));
            }
            $id = $node['id'] ?? null;
            if ((!is_string($id) && !is_int($id)) || (string)$id === '') {
                throw new InvalidArgumentException(sprintf('Flow node #%d needs a non-empty `id`.', $index));
            }
            $id = (string)$id;
            if (isset($out[$id])) {
                throw new InvalidArgumentException(sprintf('Flow node id `%s` is used twice.', $id));
            }
            $out[$id] = ['id' => $id] + $this->step($node, sprintf('Flow node `%s`', $id), true);
        }

        return $out;
    }

    /**
     * Validates and normalizes a node or a palette template.
     *
     * @param array<string, mixed> $step Node or template.
     * @param string $what Name used in error messages.
     * @param bool $positioned Whether `position` is required.
     * @return array<string, mixed>
     */
    protected function step(array $step, string $what, bool $positioned): array
    {
        $type = $step['type'] ?? null;
        if (!is_string($type) || !in_array($type, self::KINDS, true)) {
            throw new InvalidArgumentException(sprintf('%s: `type` must be one of %s.', $what, implode(', ', self::KINDS)));
        }
        $label = $step['label'] ?? '';
        if (!is_scalar($label)) {
            throw new InvalidArgumentException(sprintf('%s: `label` must be a string.', $what));
        }
        $out = ['type' => $type, 'label' => (string)$label];
        if (!empty($step['icon'])) {
            $icon = (string)$step['icon'];
            if (!$this->Icon->has($icon)) {
                throw new InvalidArgumentException(sprintf(
                    '%s: unknown icon `%s` (use an IconHelper name; register your own with IconHelper::add()).',
                    $what,
                    $icon,
                ));
            }
            $out['icon'] = $icon;
        }
        if ($positioned) {
            $position = $step['position'] ?? null;
            if (
                !is_array($position) || !is_numeric($position['x'] ?? null) || !is_numeric($position['y'] ?? null)
                || !is_finite((float)$position['x']) || !is_finite((float)$position['y'])
            ) {
                throw new InvalidArgumentException(sprintf('%s needs a `position` with finite numeric `x` and `y`.', $what));
            }
            $out['position'] = ['x' => $position['x'] + 0, 'y' => $position['y'] + 0];
        }
        $properties = [];
        foreach ((array)($step['properties'] ?? []) as $key => $value) {
            if ($value !== null && !is_scalar($value) || (is_float($value) && !is_finite($value))) {
                throw new InvalidArgumentException(sprintf('%s: property `%s` must be a string or a number.', $what, $key));
            }
            $properties[(string)$key] = is_int($value) || is_float($value) ? $value : (string)$value;
        }
        $out['properties'] = (object)$properties;
        if ($type === 'condition') {
            $outputs = [];
            foreach ((array)($step['outputs'] ?? []) as $key => $outputLabel) {
                if (!is_scalar($outputLabel)) {
                    throw new InvalidArgumentException(sprintf('%s: output `%s` needs a text label.', $what, $key));
                }
                $outputs[(string)$key] = (string)$outputLabel;
            }
            $out['outputs'] = (object)($outputs ?: [
                'yes' => __d('basecoat_theme', 'Yes'),
                'no' => __d('basecoat_theme', 'No'),
            ]);
        }

        return $out;
    }

    /**
     * Validates and normalizes the edges.
     *
     * @param array<mixed> $edges Edges.
     * @param array<string, array<string, mixed>> $nodes Normalized nodes.
     * @return array<int, array<string, string>>
     */
    protected function edges(array $edges, array $nodes): array
    {
        $out = [];
        $used = [];
        $ids = [];
        foreach (array_values($edges) as $index => $edge) {
            if (!is_array($edge)) {
                throw new InvalidArgumentException(sprintf('Flow edge #%d must be an array.', $index));
            }
            $source = (string)($edge['source'] ?? '');
            $target = (string)($edge['target'] ?? '');
            foreach (['source' => $source, 'target' => $target] as $end => $id) {
                if (!isset($nodes[$id])) {
                    throw new InvalidArgumentException(sprintf('Flow edge #%d: %s `%s` is not a node.', $index, $end, $id));
                }
            }
            if ($source === $target) {
                throw new InvalidArgumentException(sprintf('Flow edge #%d connects `%s` to itself.', $index, $source));
            }
            if ($nodes[$target]['type'] === 'trigger') {
                throw new InvalidArgumentException(sprintf('Flow edge #%d: trigger `%s` cannot be a target.', $index, $target));
            }
            $output = isset($edge['output']) && $edge['output'] !== '' ? (string)$edge['output'] : null;
            $outputs = $nodes[$source]['type'] === 'condition'
                ? array_map('strval', array_keys((array)$nodes[$source]['outputs']))
                : [];
            if ($outputs && !in_array($output, $outputs, true)) {
                throw new InvalidArgumentException(sprintf(
                    'Flow edge #%d: `output` must be one of the outputs of `%s` (%s).',
                    $index,
                    $source,
                    implode(', ', $outputs),
                ));
            }
            if (!$outputs && $output !== null) {
                throw new InvalidArgumentException(sprintf('Flow edge #%d: `%s` has no named outputs.', $index, $source));
            }
            $slot = $source . "\0" . $output;
            if (isset($used[$slot])) {
                throw new InvalidArgumentException(sprintf(
                    'Flow edge #%d: %s of `%s` already leads to another step.',
                    $index,
                    $output === null ? 'the output' : sprintf('output `%s`', $output),
                    $source,
                ));
            }
            $used[$slot] = true;
            $id = isset($edge['id']) && $edge['id'] !== ''
                ? (string)$edge['id']
                : $source . ($output !== null ? ':' . $output : '') . '->' . $target;
            if (isset($ids[$id])) {
                throw new InvalidArgumentException(sprintf('Flow edge id `%s` is used twice.', $id));
            }
            $ids[$id] = true;
            $item = ['id' => $id, 'source' => $source];
            if ($output !== null) {
                $item['output'] = $output;
            }
            $out[] = $item + ['target' => $target];
        }

        return $out;
    }

    /**
     * Validates and normalizes the palette.
     *
     * @param array<mixed> $palette Groups.
     * @return array<int, array<string, mixed>>
     */
    protected function palette(array $palette): array
    {
        $out = [];
        foreach (array_values($palette) as $g => $group) {
            if (!is_array($group)) {
                throw new InvalidArgumentException(sprintf('Flow palette group #%d must be an array.', $g));
            }
            $items = [];
            foreach (array_values((array)($group['items'] ?? [])) as $i => $item) {
                if (!is_array($item)) {
                    throw new InvalidArgumentException(sprintf('Flow palette item #%d of group #%d must be an array.', $i, $g));
                }
                $items[] = $this->step($item, sprintf('Flow palette item #%d of group #%d', $i, $g), false);
            }
            $out[] = ['label' => (string)($group['label'] ?? ''), 'items' => $items];
        }

        return $out;
    }

    /**
     * `<svg data-icon>` of every icon the editor can show (cloned by the bundle).
     *
     * @param array<string, array<string, mixed>> $nodes Nodes.
     * @param array<int, array<string, mixed>> $palette Palette.
     * @return string
     */
    protected function icons(array $nodes, array $palette): string
    {
        $names = array_values((array)$this->getConfig('kindIcons'));
        foreach ($nodes as $node) {
            $names[] = $node['icon'] ?? null;
        }
        foreach ($palette as $group) {
            foreach ($group['items'] as $item) {
                $names[] = $item['icon'] ?? null;
            }
        }
        $html = '';
        foreach (array_unique(array_filter($names)) as $name) {
            $html .= $this->Icon->render((string)$name, ['replaceClass' => true, 'class' => null, 'data-icon' => (string)$name]);
        }

        return $html;
    }

    /**
     * The steps in reading order (same as the editor's step list) with where each one leads.
     *
     * @param array<string, array<string, mixed>> $nodes Nodes.
     * @param array<int, array<string, string>> $edges Edges.
     * @param bool $escape Escape the labels.
     * @return string
     */
    protected function fallback(array $nodes, array $edges, bool $escape): string
    {
        if (!$nodes) {
            return $this->renderTemplate('empty', $this->partAttributes('fallback', [], 'bc-flow-hint bc-flow-fallback'), [
                'content' => h(__d('basecoat_theme', 'No steps yet.')),
            ]);
        }
        $texts = $this->texts();
        $items = '';
        foreach ($this->order($nodes, $edges) as $id) {
            $node = $nodes[$id];
            $outputs = $node['type'] === 'condition' ? (array)$node['outputs'] : [];
            $next = '';
            foreach ($this->outgoing($id, $outputs, $edges) as $edge) {
                $target = $this->text((string)$nodes[$edge['target']]['label'], $escape);
                $content = isset($edge['output'])
                    ? $this->text((string)($outputs[$edge['output']] ?? $edge['output']), $escape) . ': ' . $target
                    : $target;
                $next .= $this->formatTemplate('nextItem', ['content' => $content]);
            }
            $items .= $this->renderTemplate('item', ['data-step-type' => $node['type']], [
                'label' => $this->text((string)$node['label'], $escape),
                'kind' => h($texts['kind.' . $node['type']]),
                'next' => $next !== '' ? $this->formatTemplate('next', ['content' => $next]) : '',
            ]);
        }

        return $this->renderTemplate('list', $this->partAttributes('fallback', [], 'bc-flow-fallback'), ['content' => $items]);
    }

    /**
     * Reading order: breadth-first from the triggers (outputs in declared order), then the unreached
     * steps top to bottom, left to right. Mirrors `stepOrder()` of resources/flow/src/model.js.
     *
     * @param array<string, array<string, mixed>> $nodes Nodes.
     * @param array<int, array<string, string>> $edges Edges.
     * @return array<string>
     */
    protected function order(array $nodes, array $edges): array
    {
        $byPosition = array_map('strval', array_keys($nodes));
        usort($byPosition, fn (string $a, string $b): int => [$nodes[$a]['position']['y'], $nodes[$a]['position']['x']]
            <=> [$nodes[$b]['position']['y'], $nodes[$b]['position']['x']]);
        $seen = [];
        $order = [];
        $visit = function (string $start) use (&$seen, &$order, $nodes, $edges): void {
            $queue = [$start];
            while ($queue) {
                $id = (string)array_shift($queue);
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;
                $order[] = $id;
                $outputs = $nodes[$id]['type'] === 'condition' ? (array)$nodes[$id]['outputs'] : [];
                foreach ($this->outgoing($id, $outputs, $edges) as $edge) {
                    if (!isset($seen[$edge['target']])) {
                        $queue[] = $edge['target'];
                    }
                }
            }
        };
        foreach ($byPosition as $id) {
            if ($nodes[$id]['type'] === 'trigger' && !isset($seen[$id])) {
                $visit($id);
            }
        }
        foreach ($byPosition as $id) {
            if (!isset($seen[$id])) {
                $visit($id);
            }
        }

        return $order;
    }

    /**
     * Edges leaving a node, in the order of its outputs.
     *
     * @param string $id Node id.
     * @param array<string, string> $outputs Outputs of the node.
     * @param array<int, array<string, string>> $edges Edges.
     * @return array<int, array<string, string>>
     */
    protected function outgoing(string $id, array $outputs, array $edges): array
    {
        $keys = array_map('strval', array_keys($outputs));
        $list = array_values(array_filter($edges, fn (array $edge): bool => $edge['source'] === $id));
        $rank = function (array $edge) use ($keys): int {
            $i = array_search($edge['output'] ?? '', $keys, true);

            return $i === false ? count($keys) : (int)$i;
        };
        usort($list, fn (array $a, array $b): int => $rank($a) <=> $rank($b));

        return $list;
    }

    /**
     * JSON safe inside a `<script>` element and an attribute value (`<`, `>`, `&` escaped).
     *
     * @param mixed $value Value.
     * @return string
     */
    protected function json(mixed $value): string
    {
        return json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        );
    }
}
