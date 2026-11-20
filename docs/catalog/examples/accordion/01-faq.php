<?php
/**
 * @title FAQ accordion
 * @description Title => content pairs; the chevron and single-open behaviour come from Basecoat.
 */
echo $this->Accordion->render([
    'How often does Fernway refresh dashboards?' => 'Every five minutes on Growth and Scale, hourly on Starter.',
    'Can I export raw events?' => 'Yes. Scheduled exports send a daily CSV or Parquet file to your warehouse.',
    'Can I translate it?' => 'Every label is passed through the basecoat_theme translation domain.',
]);
