<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

class LockStamp implements CommandStamp
{
    private $lockName;
    private $ttl;

    public function __construct($lock = null)
    {
        $this->lockName = isset($lock['name']) ? (string) $lock['name'] :
            (is_string($lock) ? $lock : md5(serialize($lock)));

        $this->ttl = isset($lock['ttl']) ? (int) $lock['ttl'] : null;
    }

    /**
     * @return null|string
     */
    public function lockName(): string
    {
        return $this->lockName;
    }

    public function getTtl(): ?int
    {
        return $this->ttl;
    }
}
