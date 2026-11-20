<?php
/**
 * User dropdown for the sidebar footer.
 *
 * @var \Cake\View\View $this
 * @var array<string, mixed> $user name, email, avatar, menu
 */
$name = (string)($user['name'] ?? '');
$email = (string)($user['email'] ?? '');
$avatar = $this->Avatar->render($name, array_filter([
    'src' => $user['avatar'] ?? null,
    'class' => 'rounded-lg',
    // The name is printed next to the avatar.
    'decorative' => true,
]));
$identity = sprintf(
    '<span class="grid flex-1 text-left text-sm leading-tight"><span class="truncate font-medium">%s</span><span class="truncate text-xs text-muted-foreground">%s</span></span>',
    h($name),
    h($email),
);
$menu = (array)($user['menu'] ?? []);
array_unshift(
    $menu,
    ['type' => 'heading', 'label' => $avatar . str_replace('flex-1 ', '', $identity), 'escape' => false, 'attributes' => ['class' => 'flex items-center gap-2 font-normal']],
    '-',
);

echo $this->DropdownMenu->render($avatar . $identity . $this->Icon->render('ellipsis', ['class' => 'ml-auto']), $menu, [
    'id' => 'user-menu',
    'class' => 'w-full',
    'escape' => false,
    'side' => 'top',
    'align' => 'start',
    'trigger' => ['variant' => 'ghost', 'class' => 'btn h-12 w-full justify-start gap-2 px-2 text-left'],
    'popoverOptions' => ['class' => 'min-w-56'],
]);
