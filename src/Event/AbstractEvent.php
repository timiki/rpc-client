<?php

namespace Timiki\RpcClient\Event;

use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    /**
     * @var null|string
     */
    private $address;

    /**
     * @param null|string $address
     */
    public function __construct($address = null)
    {
        $this->address = $address;
    }

    /**
     * Get address.
     *
     * @return null|string
     */
    public function getAddress()
    {
        return $this->address;
    }
}
