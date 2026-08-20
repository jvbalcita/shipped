<?php

namespace App\Services\LaravelCloud;

enum CloudUrlProbeOutcome
{
    /** The exact Cloud origin answered with a successful response. */
    case Reachable;

    /** The evidence is invalid: the origin definitively rejected the probe. */
    case DefinitiveFailure;

    /** The probe could not be evaluated and may succeed on a later attempt. */
    case RetryableFailure;
}
