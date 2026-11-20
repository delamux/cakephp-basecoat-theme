<?php
/**
 * @title Open, disabled and multiple items
 * @description Array items can start open or disabled; multiple allows several panels open at once.
 */
echo $this->Accordion->render([
    ['title' => 'Shipping', 'content' => '<p>Orders leave the warehouse within 24 hours.</p>', 'open' => true],
    ['title' => 'Returns', 'content' => '<p>Returns are accepted for 30 days after delivery.</p>'],
    ['title' => 'Gift cards', 'content' => '<p>Not available yet.</p>', 'disabled' => true],
], ['multiple' => true]);
