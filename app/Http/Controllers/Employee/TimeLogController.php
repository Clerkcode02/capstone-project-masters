<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use Illuminate\Contracts\View\View;

class TimeLogController extends Controller
{
    public function __invoke(): View
    {
        $this->authorize('viewAny', TimeLog::class);

        return view('employee.time-logs');
    }
}
