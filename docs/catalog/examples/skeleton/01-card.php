<?php
/**
 * @title Loading placeholders
 * @description render() takes size classes for one block; lines() renders text rows.
 */
?>
<div class="flex items-center gap-4">
    <?= $this->Skeleton->render(['class' => 'size-12 rounded-full']) ?>
    <div class="grid gap-2">
        <?= $this->Skeleton->render(['class' => 'h-4 w-[250px]']) ?>
        <?= $this->Skeleton->render(['class' => 'h-4 w-[200px]']) ?>
    </div>
</div>
<div class="mt-6 w-[320px]">
    <?= $this->Skeleton->lines(3) ?>
</div>
