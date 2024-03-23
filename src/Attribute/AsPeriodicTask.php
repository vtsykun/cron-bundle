<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsPeriodicTask
{
    public $interval;
    public $lock;
    public $async;
    public $options;
    public $messenger;
    public $jitter;

    public function __construct(
        /*int|string */ $interval,
        ?bool $lock = null,
        ?bool $async = null,
        ?bool $messenger = null,
        ?int $jitter = null,
        array $options = [],
    ) {
        $this->async = $async;
        $this->lock = $lock;
        $this->interval = $interval;
        $this->options = $options;
        $this->messenger = $messenger;
        $this->jitter = $jitter;
    }

    public function getAttributes(): array
    {
        $attributes = get_object_vars($this);
        foreach ($attributes as $name => $value) {
            if (null === $value) {
                unset($attributes[$name]);
            }
        }

        $attributes = $attributes + $attributes['options'];
        unset($attributes['options']);

        return $attributes;
    }
}
