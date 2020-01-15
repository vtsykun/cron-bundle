<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

class ShellStamp implements CommandStamp
{
    private $timeout;

    public function __construct($shell = null)
    {
        $this->timeout = isset($shell['timeout']) ? (int)$shell['timeout'] : null;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }
}
