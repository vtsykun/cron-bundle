<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Loader;

use Okvpn\Bundle\CronBundle\Model\ArgumentsStamp;
use Okvpn\Bundle\CronBundle\Model\AsyncStamp;
use Okvpn\Bundle\CronBundle\Model\LockStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Okvpn\Bundle\CronBundle\Model\ScheduleStamp;

final class ScheduleFactory implements ScheduleFactoryInterface
{
    private $withStampsFqcn;

    /**
     * @param array $withStampsFqcn Default stamps
     */
    public function __construct(array $withStampsFqcn = [])
    {
        $this->withStampsFqcn = $withStampsFqcn;
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $config): ScheduleEnvelope
    {
        if (!isset($config['command'])) {
            throw new \InvalidArgumentException('Command name is a required parameter');
        }

        $commandName = $config['command'];

        $stamps = [];
        if (isset($config['cron'])) {
            $stamps[] = new ScheduleStamp($config['cron']);
        }
        if ((isset($config['lock']) && $config['lock']) || isset($config['lockName'])) {
            $stamps[] = new LockStamp($config['lockName'] ?? $commandName, $config['ttl'] ?? null);
        }
        if (isset($config['arguments'])) {
            $stamps[] = new ArgumentsStamp($config['arguments']);
        }
        if (isset($config['async'])) {
            $stamps[] = new AsyncStamp();
        }

        foreach ($this->withStampsFqcn as $stampsFqcn) {
            $stamps[] = new $stampsFqcn($config);
        }

        return new ScheduleEnvelope($commandName, ...$stamps);
    }
}
