<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Messenger;

use Okvpn\Bundle\CronBundle\Model\MessengerStamp;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope as MessengerEnvelope;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

class CronSendersLocator implements SendersLocatorInterface
{
    private $sendersLocatorWrapper;
    private $container;

    public function __construct(SendersLocatorInterface $sendersLocatorWrapper, ContainerInterface $container)
    {
        $this->sendersLocatorWrapper = $sendersLocatorWrapper;
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function getSenders(MessengerEnvelope $messengerEnvelope): iterable
    {
        $message = $messengerEnvelope->getMessage();
        if (!$message instanceof CronMessage || null === $message->getSchedule()->get(MessengerStamp::class)) {
            return yield from $this->sendersLocatorWrapper->getSenders($messengerEnvelope);
        }

        $routingKey = $message->getSchedule()->get(MessengerStamp::class)->getRouting();
        if (empty($routingKey)) {
            return yield from $this->sendersLocatorWrapper->getSenders($messengerEnvelope);
        }

        foreach ($routingKey as $routing) {
            if (!$this->container->has($routing)) {
                throw new \RuntimeException(sprintf('Could not find messenger transport "%s" for schedule "%s".', $routing, $message->getCommand()));
            }

            yield $routing => $this->container->get($routing);
        }
    }
}
