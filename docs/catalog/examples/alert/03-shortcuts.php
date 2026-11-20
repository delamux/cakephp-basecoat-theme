<?php
/**
 * @title Shortcut methods
 * @description success(), info(), warning() and error() preconfigure variant and icon.
 */
echo $this->Alert->success('Dashboard created', 'Churn forecast is ready to share.');
echo $this->Alert->info('Maintenance window', 'The service will be read-only on Sunday at 02:00 UTC.');
echo $this->Alert->warning('Storage almost full', 'You have used 92% of your plan.');
echo $this->Alert->error('Upload failed', 'The file exceeds the 10 MB limit.');
