<?php
/**
 * @title Support conversation
 * @description A customer chat about a duplicated invoice with a contact header and a message composer.
 */
$messages = [
    ['text' => 'Hi, I was charged twice for invoice INV-2041.', 'from' => 'them', 'author' => 'Marta Vidal', 'time' => '10:02'],
    ['text' => 'Sorry about that, let me check your account.', 'from' => 'me', 'time' => '10:04'],
    ['text' => 'Found it: the second charge was a duplicate. I am refunding it now.', 'from' => 'me', 'time' => '10:06'],
    ['text' => 'Great, thank you for the quick help!', 'from' => 'them', 'author' => 'Marta Vidal', 'time' => '10:07'],
];
// ---
echo $this->Conversation->render($messages, [
    'contact' => ['name' => 'Marta Vidal', 'subtitle' => 'marta.vidal@example.com'],
    'composer' => ['placeholder' => 'Reply to Marta…'],
    'class' => 'max-w-xl',
]);
