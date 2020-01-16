<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Messenger\CronMessage;
use Okvpn\Bundle\CronBundle\Messenger\RoutingStamp;
use Okvpn\Bundle\CronBundle\Model\MessengerStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerEngine implements MiddlewareEngineInterface
{
    private $messageBus;

    public function __construct(MessageBusInterface $messageBus = null)
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @inheritDoc
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        if (false === $envelope->has(MessengerStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        if (null === $this->messageBus) {
            throw new \LogicException('To use messenger cron handler you need enable/install "symfony/messenger" component and configure default_bus');
        }

        $message = new CronMessage($envelope);
        $routing = $envelope->get(MessengerStamp::class)->getRouting();
        $stamps = \array_map(function ($route) {return new RoutingStamp($route);}, $routing);
        $this->messageBus->dispatch($message, $stamps);

        return $stack->end()->handle($envelope, $stack);
    }
}
