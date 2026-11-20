<?php
/**
 * @title Avatar group
 * @description max collapses the rest into a +N counter; avatar options apply to every item.
 */
echo $this->Avatar->group(
    ['Jane Doe', 'Marta Vega', 'Leo Park', 'Sam Okafor', 'Ines Rossi'],
    ['max' => 3, 'avatar' => ['size' => 'sm']],
);
