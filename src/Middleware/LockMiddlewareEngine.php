<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Model\LockStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Symfony\Component\Lock\Factory as LockFactory;

final class LockMiddlewareEngine implements MiddlewareEngineInterface
{
    private $factory;

    public function __construct(LockFactory $factory = null)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        /** @var LockStamp $stamp */
        if (!$stamp = $envelope->get(LockStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        if (null === $this->factory) {
            throw new \LogicException('You need to install symfony/lock to run tasks with locking.');
        }

        $lock = $this->factory->createLock(
            $stamp->lockName(),
            $stamp->getTtl()
        );

        if (!$lock->acquire()) {
            return $stack->end()->handle($envelope, $stack);
        }

        try {
            return $stack->next()->handle($envelope->without(LockStamp::class), $stack);
        } finally {
            $lock->release();
        }
    }
}
