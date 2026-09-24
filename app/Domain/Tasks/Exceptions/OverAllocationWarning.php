<?php

namespace App\Domain\Tasks\Exceptions;

use App\Domain\Tasks\DTOs\WorkloadEvaluation;
use RuntimeException;

/**
 * Thrown by TaskAssignmentService::assign() when the assignment would push
 * the candidate's workload score past settings('workload_threshold') and the
 * caller has not confirmed the override. This is decision support, not
 * enforcement: the caller is expected to show $evaluation to the manager and
 * re-call assign() with $confirmedOverride = true if they proceed anyway.
 */
class OverAllocationWarning extends RuntimeException
{
    public function __construct(
        public readonly WorkloadEvaluation $evaluation,
    ) {
        parent::__construct('Assigning this task would push the employee over the workload threshold.');
    }
}
