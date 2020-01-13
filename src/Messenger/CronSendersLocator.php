<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Messenger;

use Okvpn\Bundle\CronBundle\Model\MessengerStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Symfony\Component\Messenger\Envelope as MessengerEnvelope;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;

class CronSendersLocator implements SendersLocatorInterface
{
    private $sendersLocatorWrapper;

    public function __construct(SendersLocatorInterface $sendersLocatorWrapper)
    {
        $this->sendersLocatorWrapper = $sendersLocatorWrapper;
    }

    /**
     * @inheritDoc
     */
    public function getSenders(MessengerEnvelope $messengerEnvelope): iterable
    {
        $message = $messengerEnvelope->getMessage();
        if (!$message instanceof ScheduleEnvelope || null === $message->get(MessengerStamp::class)) {
            return $this->sendersLocatorWrapper->getSenders($messengerEnvelope);
        }

        $routingKey = $message->get(MessengerStamp::class)->getRouting();
        if (empty($routingKey)) {
            return $this->sendersLocatorWrapper->getSenders($messengerEnvelope);
        }


    }
}
