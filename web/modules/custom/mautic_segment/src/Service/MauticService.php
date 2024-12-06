<?php

namespace Drupal\mautic_segment\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class MauticService
{
    protected $logger;

    public function __construct(LoggerChannelFactoryInterface $logger_factory)
    {
        $this->logger = $logger_factory->get('mautic_segment');
    }

    public function logMtcId($mtcId)
    {
        // Log the mtc_id locally.
        $this->logger->info('Received MTC ID: @mtc_id', ['@mtc_id' => $mtcId]);
        return true;
    }
}
