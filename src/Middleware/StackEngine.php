<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

final class StackEngine implements StackInterface, MiddlewareEngineInterface
{
    /**
     * @var \Iterator
     */
    private $iterator;

    /**
     * @param \Iterator|null $iterator
     */
    public function __construct(\Iterator $iterator = null)
    {
        $this->iterator = $iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): MiddlewareEngineInterface
    {
        if (null === $iterator = $this->iterator) {
            return $this;
        }
        $iterator->next();

        if (!$iterator->valid()) {
            $this->iterator = null;

            return $this;
        }

        return $iterator->current();
    }

    /**
     * {@inheritdoc}
     */
    public function end(): MiddlewareEngineInterface
    {
        $this->iterator = null;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        return $envelope;
    }
}
