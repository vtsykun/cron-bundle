<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Model\ArgumentsStamp;
use Okvpn\Bundle\CronBundle\Model\OutputStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Psr\Container\ContainerInterface;

final class ServiceInvokeEngine implements MiddlewareEngineInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        if (!$this->container->has($envelope->getCommand())) {
            return $stack->next()->handle($envelope, $stack);
        }

        $handler = $envelope->get($envelope->getCommand());
        if (is_callable($handler)) {
            $commandArguments = $envelope->get(ArgumentsStamp::class) ?
                $envelope->get(ArgumentsStamp::class)->getArguments() : [];

            $result = $handler($commandArguments);
            return $stack->end()->handle($envelope->with(new OutputStamp($result)), $stack);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
