<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

class LockStamp implements CommandStamp
{
    private $lockName;
    private $ttl;

    public function __construct(string $lockName, int $ttl = null)
    {
        $this->lockName = $lockName;
        $this->ttl = $ttl;
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
