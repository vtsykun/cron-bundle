<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

class MessengerStamp implements CommandStamp
{
    private $routing;

    /**
     * @param mixed|null $messenger
     */
    public function __construct($messenger = null)
    {
        $this->routing = isset($messenger['routing']) ? (array) $messenger['routing'] : [];
    }

    /**
     * @return array|string[]
     */
    public function getRouting(): array
    {
        return $this->routing;
    }
}
