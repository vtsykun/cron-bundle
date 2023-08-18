<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

/**
 * An environment stamp uses to pass okvpn:cron options.
 */
class EnvironmentStamp implements CommandStamp
{
    private $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function get(string $name)
    {
        return $this->options[$name] ?? null;
    }

    public function toArray(): array
    {
        return $this->options;
    }
}
