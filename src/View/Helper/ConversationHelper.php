<?php
declare(strict_types=1);

namespace BasecoatTheme\View\Helper;

use function Cake\Core\h;

/**
 * Chat conversation: optional contact header, message thread and message composer.
 *
 * ```
 * echo $this->Conversation->render([
 *     ['text' => 'Hello!', 'from' => 'them', 'time' => '10:02', 'author' => 'Marta'],
 *     ['text' => 'Hi, how can I help?', 'from' => 'me'],
 * ], ['contact' => ['name' => 'Marta Vidal', 'subtitle' => 'Support']]);
 * ```
 *
 * ### Options
 *
 * - `contact`: `['name' => string, 'subtitle' => string, 'avatar' => string]` renders a header.
 * - `composer`: `false` to omit it, or `['url' => string|array, 'name' => 'message', 'placeholder' => string, 'label' => string]`.
 *   `label` is the accessible name (aria-label) of the input, default "Message".
 *   The composer is a plain POST form (`action` only when `url` is given).
 * - `headerActions`: HTML placed at the end of the header.
 *
 * @property \Cake\View\Helper\UrlHelper $Url
 * @property \BasecoatTheme\View\Helper\AvatarHelper $Avatar
 * @property \BasecoatTheme\View\Helper\ButtonHelper $Button
 */
class ConversationHelper extends AbstractComponentHelper
{
    /**
     * @var array<int|string, string|array<string, mixed>>
     */
    protected array $helpers = ['Url', 'BasecoatTheme.Avatar', 'BasecoatTheme.Button'];

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'class' => null,
        'attributes' => [],
        'classes' => [
            'header' => null,
            'thread' => null,
            'message' => null,
            'sent' => 'ml-auto bg-primary text-primary-foreground',
            'received' => 'bg-muted',
            'meta' => null,
            'composer' => null,
            'input' => null,
        ],
        'templates' => [
            'root' => '<div{{attrs}}>{{header}}{{thread}}{{composer}}</div>',
            'header' => '<div{{attrs}}>{{avatar}}<div class="min-w-0 flex-1"><p class="text-sm font-medium leading-none">{{name}}</p>{{subtitle}}</div>{{actions}}</div>',
            'subtitle' => '<p class="text-muted-foreground mt-1 text-xs">{{content}}</p>',
            'thread' => '<div{{attrs}}>{{content}}</div>',
            'message' => '<div{{attrs}}>{{author}}<p>{{text}}</p>{{time}}</div>',
            'author' => '<span class="block text-xs font-medium opacity-70">{{content}}</span>',
            'time' => '<span class="mt-1 block text-xs opacity-70">{{content}}</span>',
            'composer' => '<form{{attrs}}>{{csrf}}{{input}}{{button}}</form>',
            'input' => '<input type="text"{{attrs}}>',
            'csrf' => '<input type="hidden" name="_csrfToken" value="{{token}}">',
        ],
    ];

    /**
     * Renders the conversation.
     *
     * @param array<array<string, mixed>> $messages Messages (`text`, `from` me|them, `time`, `author`).
     * @param array<string, mixed> $options Options.
     * @return string
     */
    public function render(array $messages, array $options = []): string
    {
        $escape = $options['escape'] ?? true;
        $header = !empty($options['contact']) ? $this->header((array)$options['contact'], $options['headerActions'] ?? '', $escape) : '';

        $bubbles = '';
        foreach ($messages as $message) {
            $bubbles .= $this->message($message, $escape);
        }
        $thread = $this->renderTemplate('thread', $this->partAttributes('thread', [
            'role' => 'log',
            'aria-live' => 'polite',
        ], 'flex flex-1 flex-col gap-3 overflow-y-auto p-4'), ['content' => $bubbles]);

        $composer = '';
        if (($options['composer'] ?? true) !== false) {
            $composer = $this->composer(is_array($options['composer'] ?? null) ? $options['composer'] : []);
        }

        $attributes = $this->rootAttributes('flex flex-col rounded-xl border bg-card text-card-foreground', $options, [
            'contact', 'composer', 'headerActions',
        ]);

        return $this->renderTemplate('root', $attributes, [
            'header' => $header,
            'thread' => $thread,
            'composer' => $composer,
        ], $options['templateVars'] ?? []);
    }

    /**
     * @param array<string, mixed> $contact Contact.
     * @param string $actions Header actions HTML.
     * @param bool $escape Escape text.
     * @return string
     */
    protected function header(array $contact, string $actions, bool $escape): string
    {
        $name = (string)($contact['name'] ?? '');
        $avatarOptions = !empty($contact['avatar']) ? ['src' => $contact['avatar']] : [];
        $subtitle = isset($contact['subtitle']) && $contact['subtitle'] !== ''
            ? $this->formatTemplate('subtitle', ['content' => $this->text((string)$contact['subtitle'], $escape)])
            : '';

        return $this->renderTemplate('header', $this->partAttributes('header', [], 'flex items-center gap-3 border-b p-4'), [
            'avatar' => $this->Avatar->render($name, $avatarOptions),
            'name' => $this->text($name, $escape),
            'subtitle' => $subtitle,
            'actions' => $actions,
        ]);
    }

    /**
     * @param array<string, mixed> $message Message.
     * @param bool $escape Escape text.
     * @return string
     */
    protected function message(array $message, bool $escape): string
    {
        $mine = ($message['from'] ?? 'them') === 'me';
        $attributes = $this->partAttributes('message', [
            'class' => $this->mergeClasses($this->getConfig('classes.' . ($mine ? 'sent' : 'received'))),
        ], 'flex max-w-[75%] flex-col rounded-lg px-3 py-2 text-sm');

        $author = !empty($message['author'])
            ? $this->formatTemplate('author', ['content' => $this->text((string)$message['author'], $escape)])
            : '';
        $time = !empty($message['time'])
            ? $this->formatTemplate('time', ['content' => $this->text((string)$message['time'], $escape)])
            : '';

        return $this->renderTemplate('message', $attributes, [
            'author' => $author,
            'text' => $this->text((string)($message['text'] ?? ''), $escape),
            'time' => $time,
        ]);
    }

    /**
     * @param array<string, mixed> $composer Composer options.
     * @return string
     */
    protected function composer(array $composer): string
    {
        $attributes = ['method' => 'post'];
        if (isset($composer['url'])) {
            $attributes['action'] = $this->Url->build($composer['url']);
        }
        $attributes = $this->partAttributes('composer', $attributes, 'flex items-center gap-2 border-t p-3');

        $input = $this->renderTemplate('input', $this->partAttributes('input', [
            'name' => $composer['name'] ?? 'message',
            'placeholder' => $composer['placeholder'] ?? __d('basecoat_theme', 'Type a message…'),
            'aria-label' => $composer['label'] ?? __d('basecoat_theme', 'Message'),
            'autocomplete' => 'off',
            'required' => true,
        ], 'input flex-1'));

        $token = $this->getView()->getRequest()->getAttribute('csrfToken');
        $csrf = is_string($token) && $token !== ''
            ? $this->formatTemplate('csrf', ['token' => h($token)])
            : '';

        return $this->renderTemplate('composer', $attributes, [
            'csrf' => $csrf,
            'input' => $input,
            'button' => $this->Button->submit(__d('basecoat_theme', 'Send')),
        ]);
    }
}
