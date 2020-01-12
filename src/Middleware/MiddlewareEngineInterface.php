<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

interface MiddlewareEngineInterface
{
    /**
     * @param ScheduleEnvelope $envelope
     * @param StackInterface $stack
     *
     * @return ScheduleEnvelope
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope;
}
