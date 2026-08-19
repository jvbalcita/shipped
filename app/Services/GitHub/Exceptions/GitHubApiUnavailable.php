<?php

namespace App\Services\GitHub\Exceptions;

use RuntimeException;

final class GitHubApiUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('GitHub API is temporarily unavailable.');
    }
}
