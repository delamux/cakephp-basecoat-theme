<?php
/**
 * @title Avatar group
 * @description max collapses the rest into a +N counter; avatar options apply to every item.
 */
echo $this->Avatar->group(
    ['Elena Sarto', 'Kofi Mensah', 'Ines Duarte', 'Pavel Novak', 'Noor Haddad'],
    ['max' => 3, 'avatar' => ['size' => 'sm']],
);
