<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

/**
 * A wrapped to run the commands.
 */
final class ScheduleEnvelope
{
    private $command;
    private $stamps;

    public function __construct(string $command, CommandStamp ...$stamps)
    {
        $this->command = $command;

        foreach ($stamps as $stamp) {
            $stampRefl = new \ReflectionObject($stamp);
            while ($stampRefl) {
                $this->stamps[$stampRefl->getName()] = $stamp;
                $stampRefl = $stampRefl->getParentClass();
            }
        }
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param CommandStamp ...$stamps
     * @return $this
     */
    public function with(CommandStamp ...$stamps): self
    {
        $cloned = clone $this;

        foreach ($stamps as $stamp) {
            $stampRefl = new \ReflectionObject($stamp);
            while ($stampRefl) {
                $cloned->stamps[$stampRefl->getName()] = $stamp;
                $stampRefl = $stampRefl->getParentClass();
            }
        }

        return $cloned;
    }

    /**
     * @param mixed $stampsFqcn
     * @return $this
     */
    public function without(string ...$stampsFqcn): self
    {
        $cloned = clone $this;
        foreach ($stampsFqcn as $stampFqcn) {
            unset($cloned->stamps[$stampFqcn]);
        }

        return $cloned;
    }

    /**
     * @param string $stampFqcn
     * @return CommandStamp|null
     */
    public function get(string $stampFqcn): ?CommandStamp
    {
        return $this->stamps[$stampFqcn] ?? null;
    }

    /**
     * @param string $stampFqcn
     * @return bool
     */
    public function has(string $stampFqcn): bool
    {
        return isset($this->stamps[$stampFqcn]);
    }

    public function __serialize(): array
    {
        $this->__sleep();

        return [
            'command' => $this->command,
            'stamps' => $this->stamps,
        ];
    }

    // For php7.2 BC support, __serialize only from php 7.4
    public function __sleep()
    {
        foreach ($this->stamps as $name => $stamp) {
            if ($stamp instanceof UnserializableStamp) {
                unset($this->stamps[$name]);
            }
        }
    }

    public function __unserialize(array $data): void
    {
        $this->command = $data['command'];
        $this->stamps = $data['stamps'];
    }
}
