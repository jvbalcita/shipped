<?php

namespace App\Enums;

/**
 * How a curator closed a content report. Resolving always records an
 * explicit outcome so the trust contract (roadmap section 15) stays
 * inspectable: a dismissal and a takedown leave different traces.
 */
enum ContentReportResolution: string
{
    case ActionTaken = 'action_taken';
    case NoAction = 'no_action';
}
