<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Messenger;

use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

final class CronSendersLocator implements SendersLocatorInterface
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
    public function getSenders(Envelope $envelope): iterable
    {
        if (!$envelope->getMessage() instanceof CronMessage || null === $envelope->last(RoutingStamp::class)) {
            return yield from $this->sendersLocatorWrapper->getSenders($envelope);
        }

        foreach ($envelope->all(RoutingStamp::class) as $stamp) {
            if (!$this->container->has($alias = $stamp->getRoute())) {
                throw new \RuntimeException(sprintf('Could not find messenger transport "%s" for schedule.', $alias));
            }

            yield $alias => $this->container->get($alias);
        }
    }
}
