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
        $this->routing = isset($messenger['routing']) ? (array) $messenger['routing'] : null;
    }

    /**
     * @return array|string[]|null
     */
    public function getRouting()
    {
        return $this->routing;
    }
}
