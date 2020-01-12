<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

final class TimeoutStamp implements CommandStamp
{
    private $timeout;

    public function __construct(?int $timeout)
    {
        $this->timeout = $timeout;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }
}
