<?php
/**
 * @title Read-only conversation
 * @description A closed ticket history shown without the composer.
 */
$messages = [
    ['text' => 'The export keeps timing out.', 'from' => 'them', 'time' => 'Mon 09:15'],
    ['text' => 'We raised the timeout on our side. Could you try again?', 'from' => 'me', 'time' => 'Mon 09:40'],
    ['text' => 'Works now. Closing this ticket.', 'from' => 'them', 'time' => 'Mon 10:05'],
];
// ---
echo $this->Conversation->render($messages, [
    'contact' => ['name' => 'Tomas Herrera', 'subtitle' => 'Ticket closed'],
    'composer' => false,
    'class' => 'max-w-xl',
]);
