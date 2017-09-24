<?php

namespace Timiki\RpcClient\Event;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    /**
     * @var string|null
     */
    private $address;

    /**
     * @param $address
     */
    public function __construct($address = null)
    {
        $this->address = $address;
    }

    /**
     * Get address.
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->address;
    }
}