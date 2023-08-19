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
            $this->addStamp($stamp, $this);
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
            $this->addStamp($stamp, $cloned);
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
            $this->removeStamp($stampFqcn, $cloned);
        }

        return $cloned;
    }

    /**
     * @template TStamp of CommandStamp
     *
     * @param class-string<TStamp> $stampFqcn
     *
     * @return TStamp|null
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
    public function __sleep(): array
    {
        foreach ($this->stamps as $name => $stamp) {
            if ($stamp instanceof UnserializableStamp) {
                unset($this->stamps[$name]);
            }
        }

        return ['command', 'stamps'];
    }

    public function __unserialize(array $data): void
    {
        $this->command = $data['command'];
        $this->stamps = $data['stamps'];
    }

    private function addStamp($stamp, ScheduleEnvelope $envelope): void
    {
        $stampRefl = new \ReflectionObject($stamp);
        foreach ($stampRefl->getInterfaceNames() as $interfaceName) {
            $envelope->stamps[$interfaceName] = $stamp;
        }

        while ($stampRefl) {
            $envelope->stamps[$stampRefl->getName()] = $stamp;
            $stampRefl = $stampRefl->getParentClass();
        }
    }

    private function removeStamp($stamp, ScheduleEnvelope $envelope): void
    {
        $stampRefl = \is_object($stamp) ? new \ReflectionObject($stamp) : (\class_exists($stamp) || \interface_exists($stamp) ? new \ReflectionClass($stamp) : null);
        if (\is_string($stamp)) {
            unset($envelope->stamps[$stamp]);
        }

        if (null !== $stampRefl) {
            foreach ($stampRefl->getInterfaceNames() as $interfaceName) {
                unset($envelope->stamps[$interfaceName]);
            }
            while ($stampRefl) {
                unset($envelope->stamps[$stampRefl->getName()]);
                $stampRefl = $stampRefl->getParentClass();
            }
        }
    }
}
