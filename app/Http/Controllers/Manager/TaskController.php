<?php

namespace App\Http\Controllers\Manager;

use App\Domain\Tasks\Services\TaskService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Account;
use App\Models\Task;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $tasks) {}

    public function index(): View
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->with(['account', 'assignee'])
            ->latest()
            ->paginate(20);

        return view('manager.tasks.index', compact('tasks'));
    }

    public function create(): View
    {
        $this->authorize('create', Task::class);

        $accounts = Account::query()->where('is_active', true)->orderBy('name')->get();

        return view('manager.tasks.create', compact('accounts'));
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $task = $this->tasks->create($request->validated(), $request->user());

        return redirect()
            ->route('manager.tasks.index')
            ->with('status', "Task {$task->reference} created.");
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        $accounts = Account::query()->where('is_active', true)->orderBy('name')->get();

        return view('manager.tasks.edit', compact('task', 'accounts'));
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->tasks->update($task, $request->validated());

        return redirect()
            ->route('manager.tasks.index')
            ->with('status', "Task {$task->reference} updated.");
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $this->tasks->delete($task);

        return redirect()
            ->route('manager.tasks.index')
            ->with('status', 'Task deleted.');
    }
}
