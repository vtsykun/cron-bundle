<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

class MessengerStamp implements CommandStamp
{
    private $routing;

    /**
     * @param string[]|null $routing
     */
    public function __construct(array $routing = null)
    {
        $this->routing = $routing;
    }

    /**
     * @return array|string[]|null
     */
    public function getRouting()
    {
        return $this->routing;
    }
}
