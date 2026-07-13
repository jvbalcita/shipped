<?php

namespace App\Services\LaravelCloud\Exceptions;

use RuntimeException;

final class CloudApiUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Laravel Cloud API is temporarily unavailable.');
    }
}
