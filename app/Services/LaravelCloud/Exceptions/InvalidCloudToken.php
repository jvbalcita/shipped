<?php

namespace App\Services\LaravelCloud\Exceptions;

use RuntimeException;

final class InvalidCloudToken extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Laravel Cloud token is invalid.');
    }
}
