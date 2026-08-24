<?php

namespace App\Livewire\Optimization;

use App\Domain\Optimization\Enums\RecommendationStatus;
use App\Domain\Optimization\Services\RecommendationReviewService;
use App\Models\RedistributionRecommendation;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;

class RecommendationQueue extends Component
{
    public ?int $confirmingAcceptId = null;

    public ?int $confirmingDismissId = null;

    public string $reason = '';

    public function mount(): void
    {
        $this->authorize('viewAny', RedistributionRecommendation::class);
    }

    #[Computed]
    public function pendingRecommendations(): Collection
    {
        return RedistributionRecommendation::query()
            ->where('status', RecommendationStatus::Pending)
            ->with(['task.account', 'fromUser', 'suggestedUser'])
            ->orderBy('created_at')
            ->get();
    }

    public function confirmAccept(int $recommendationId): void
    {
        $this->authorize('review', RedistributionRecommendation::findOrFail($recommendationId));

        $this->confirmingDismissId = null;
        $this->reason = '';
        $this->confirmingAcceptId = $recommendationId;
    }

    public function confirmDismiss(int $recommendationId): void
    {
        $this->authorize('review', RedistributionRecommendation::findOrFail($recommendationId));

        $this->confirmingAcceptId = null;
        $this->confirmingDismissId = $recommendationId;
    }

    public function cancel(): void
    {
        $this->confirmingAcceptId = null;
        $this->confirmingDismissId = null;
        $this->reason = '';
        $this->resetErrorBag();
    }

    public function accept(RecommendationReviewService $service): void
    {
        $recommendation = RedistributionRecommendation::findOrFail($this->confirmingAcceptId);

        $this->authorize('review', $recommendation);

        $this->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        try {
            $service->accept($recommendation, auth()->user(), $this->reason);
        } catch (RuntimeException $e) {
            $this->addError('reason', $e->getMessage());

            return;
        }

        $this->cancel();
        unset($this->pendingRecommendations);
        session()->flash('status', 'Recommendation accepted and task reassigned.');
    }

    public function dismiss(RecommendationReviewService $service): void
    {
        $recommendation = RedistributionRecommendation::findOrFail($this->confirmingDismissId);

        $this->authorize('review', $recommendation);

        try {
            $service->dismiss($recommendation, auth()->user());
        } catch (RuntimeException $e) {
            $this->cancel();
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->cancel();
        unset($this->pendingRecommendations);
        session()->flash('status', 'Recommendation dismissed.');
    }

    public function render()
    {
        return view('livewire.optimization.recommendation-queue');
    }
}
