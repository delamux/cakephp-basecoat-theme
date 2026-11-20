<?php
/**
 * @title FAQ accordion
 * @description Title => content pairs; the chevron and single-open behaviour come from Basecoat.
 */
echo $this->Accordion->render([
    'Is it accessible?' => 'Yes. It uses native details elements and follows the WAI-ARIA pattern.',
    'Is it styled?' => 'Yes. It ships with the Basecoat look and adapts to light and dark mode.',
    'Can I translate it?' => 'Every label is passed through the basecoat_theme translation domain.',
]);
