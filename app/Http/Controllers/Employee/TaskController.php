<?php

namespace App\Http\Controllers\Employee;

use App\Domain\Tasks\Exceptions\InvalidTaskStatusTransitionException;
use App\Domain\Tasks\Services\TaskService;
use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(): View
    {
        $tasks = Task::query()
            ->visibleTo(auth()->user())
            ->with('account')
            ->orderBy('due_date')
            ->get();

        return view('employee.tasks.index', compact('tasks'));
    }

    public function start(Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        try {
            $this->tasks->start($task);
        } catch (InvalidTaskStatusTransitionException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', "Task {$task->reference} started.");
    }

    public function complete(Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        try {
            $this->tasks->complete($task);
        } catch (InvalidTaskStatusTransitionException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', "Task {$task->reference} marked complete.");
    }
}
