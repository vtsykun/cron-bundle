<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Runner;

use Okvpn\Bundle\CronBundle\Middleware\MiddlewareEngineInterface;
use Okvpn\Bundle\CronBundle\Middleware\StackEngine;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;

final class ScheduleRunner implements ScheduleRunnerInterface
{
    /**
     * @var iterable|MiddlewareEngineInterface[]
     */
    private $middleware;

    public function __construct(iterable $middleware)
    {
        $this->middleware = $middleware;
    }

    /**
     * @inheritDoc
     */
    public function execute(ScheduleEnvelope $envelope): ScheduleEnvelope
    {
        $middleware = $this->middleware instanceof \Traversable ? iterator_to_array($this->middleware)
            : $this->middleware;

        $aggregate = new \ArrayObject($middleware);
        $handlersIterator = $aggregate->getIterator();
        $handlersIterator->rewind();

        if (!$handlersIterator->valid()) {
            return $envelope;
        }

        $stack = new StackEngine($handlersIterator);

        return $handlersIterator->current()->handle($envelope, $stack);
    }
}
