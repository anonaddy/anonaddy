<?php

namespace App\Exceptions;

use RuntimeException;

class CouldNotGetVersionException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Could not get version string (`git describe` failed)");
    }
}
