<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

use Psr\Log\LoggerInterface;

final class LoggerAwareStamp implements CommandStamp, UnserializableStamp
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
