<?php
/**
 * @title Empty state with footer link
 * @description footer adds a secondary line below the actions.
 */
echo $this->EmptyState->render('No results found', [
    'description' => 'Try adjusting your search or filters.',
    'icon' => 'search',
    'footer' => $this->Button->link('Learn about search filters', '/docs/search', ['variant' => 'link']),
]);
