<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * @internal
 */
final class RoutingStamp implements StampInterface
{
    private $routeAlias;

    public function __construct(string $routeAlias)
    {
        $this->routeAlias = $routeAlias;
    }

    public function getRoute(): string
    {
        return $this->routeAlias;
    }
}
