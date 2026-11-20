<?php
/**
 * @title Card with header action and footer
 * @description Title, description, an action in the header and a footer around a body.
 */
echo $this->Card->render('<p>Fernway closed 214 new workspaces between August and October.</p>', [
    'title' => 'Quarterly bookings',
    'description' => 'Q3 2026, all plans',
    'action' => $this->Badge->render('+9.4%', ['variant' => 'outline', 'icon' => 'trending-up']),
    'footer' => $this->Button->render('View report', ['variant' => 'outline', 'size' => 'sm']),
]);
