<?php
/**
 * Basecoat templates for the core PaginatorHelper.
 *
 * Usage (src/View/AppView.php):
 *   $this->addHelper('Paginator', ['templates' => 'BasecoatTheme.paginator_templates']);
 *
 * Wrap the links in your template:
 *   <nav role="navigation" aria-label="pagination" class="mx-auto flex w-full justify-center">
 *     <ul class="flex flex-row items-center gap-1">
 *       <?= $this->Paginator->prev('Previous') ?><?= $this->Paginator->numbers() ?><?= $this->Paginator->next('Next') ?>
 *     </ul>
 *   </nav>
 *
 * Or simply use PaginationHelper: <?= $this->Pagination->render() ?>
 */
return [
    'nextActive' => '<li><a class="btn" data-variant="ghost" rel="next" href="{{url}}">{{text}}</a></li>',
    'nextDisabled' => '<li><a class="btn pointer-events-none opacity-50" data-variant="ghost" aria-disabled="true" tabindex="-1">{{text}}</a></li>',
    'prevActive' => '<li><a class="btn" data-variant="ghost" rel="prev" href="{{url}}">{{text}}</a></li>',
    'prevDisabled' => '<li><a class="btn pointer-events-none opacity-50" data-variant="ghost" aria-disabled="true" tabindex="-1">{{text}}</a></li>',
    'counterRange' => '{{start}} - {{end}} of {{count}}',
    'counterPages' => '{{page}} of {{pages}}',
    'first' => '<li><a class="btn" data-variant="ghost" data-size="icon" href="{{url}}">{{text}}</a></li>',
    'last' => '<li><a class="btn" data-variant="ghost" data-size="icon" href="{{url}}">{{text}}</a></li>',
    'number' => '<li><a class="btn" data-variant="ghost" data-size="icon" href="{{url}}">{{text}}</a></li>',
    'current' => '<li><a class="btn" data-variant="outline" data-size="icon" aria-current="page" href="{{url}}">{{text}}</a></li>',
    'ellipsis' => '<li><div class="flex size-9 items-center justify-center" aria-hidden="true">&hellip;</div></li>',
    'sort' => '<a class="inline-flex items-center gap-1 hover:underline" href="{{url}}">{{text}}</a>',
    'sortAsc' => '<a class="inline-flex items-center gap-1 hover:underline" data-direction="asc" href="{{url}}">{{text}} <span aria-hidden="true">&uarr;</span></a>',
    'sortDesc' => '<a class="inline-flex items-center gap-1 hover:underline" data-direction="desc" href="{{url}}">{{text}} <span aria-hidden="true">&darr;</span></a>',
    'sortAscLocked' => '<a class="inline-flex items-center gap-1" data-direction="asc" data-locked="true" href="{{url}}">{{text}} <span aria-hidden="true">&uarr;</span></a>',
    'sortDescLocked' => '<a class="inline-flex items-center gap-1" data-direction="desc" data-locked="true" href="{{url}}">{{text}} <span aria-hidden="true">&darr;</span></a>',
];
