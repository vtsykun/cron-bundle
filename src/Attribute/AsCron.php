<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsCron
{
    public $cron;
    public $lock;
    public $async;
    public $options;
    public $messenger;
    public $jitter;

    public function __construct(
        string $cron,
        ?bool $lock = null,
        ?bool $async = null,
        array $options = [],
        ?bool $messenger = null,
        ?int $jitter = null,
    ) {
        // Replace when update PHP > 7.2
        $this->async = $async;
        $this->lock = $lock;
        $this->cron = $cron;
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
