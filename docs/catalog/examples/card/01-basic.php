<?php
/**
 * @title Card with header action and footer
 * @description Title, description, an action in the header and a footer around a body.
 */
echo $this->Card->render('<p>Revenue grew steadily over the last six months.</p>', [
    'title' => 'Total Revenue',
    'description' => 'January - June 2026',
    'action' => $this->Badge->render('+12%', ['variant' => 'outline', 'icon' => 'trending-up']),
    'footer' => $this->Button->render('View report', ['variant' => 'outline', 'size' => 'sm']),
]);
