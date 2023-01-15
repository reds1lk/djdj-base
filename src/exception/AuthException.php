<?php

namespace Djdj\Base\exception;

use Exception;

class AuthException extends Exception
{
    public $state;

    public function __construct($state)
    {
        parent::__construct();
        $this->state = $state;
    }

}