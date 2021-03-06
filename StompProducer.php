<?php

declare(strict_types=1);

namespace Enqueue\Stomp;

use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Stomp\Client;
use Stomp\Transport\Message as StompLibMessage;

class StompProducer implements Producer
{
    /**
     * @var Client
     */
    private $stomp;

    /**
     * @var DeliveryDelay
     */
    private $deliveryDelay;

    /**
     * @param Client $stomp
     */
    public function __construct(Client $stomp)
    {
        $this->stomp = $stomp;
    }

    /**
     * @param StompDestination $destination
     * @param StompMessage     $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, StompDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, StompMessage::class);

        //set persistence
        $message->setPersistent(true);

        //set delivery delay
        $message->setHeader('AMQ_SCHEDULED_DELAY',$this->deliveryDelay);

        $headers = array_merge($message->getHeaders(), $destination->getHeaders());
        $headers = StompHeadersEncoder::encode($headers, $message->getProperties());

        $stompMessage = new StompLibMessage($message->getBody(), $headers);

        $this->stomp->send($destination->getQueueName(), $stompMessage);
    }

    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        //default delay of 0
        if(is_null($deliveryDelay))
            $deliveryDelay=0;

        $this->deliveryDelay=$deliveryDelay;
        return $this;
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->deliveryDelay;
    }

    public function setPriority(int $priority = null): Producer
    {
        if (null === $priority) {
            return $this;
        }

        throw PriorityNotSupportedException::providerDoestNotSupportIt();
    }

    public function getPriority(): ?int
    {
        return null;
    }

    public function setTimeToLive(int $timeToLive = null): Producer
    {
        if (null === $timeToLive) {
            return $this;
        }

        throw new \LogicException('Not implemented');
    }

    public function getTimeToLive(): ?int
    {
        return null;
    }
}
