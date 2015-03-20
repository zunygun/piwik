<?php

use Interop\Container\ContainerInterface;
use Piwik\Tracker;
use Piwik\Tracker\Db as TrackerDb;
use Piwik\Tracker\Db\DbException;

return array(
    'db.connection' => function () {
        try {
            return TrackerDb::connectPiwikTrackerDb();
        } catch (Exception $e) {
            throw new DbException($e->getMessage(), $e->getCode(), $e);
        }
    }
);