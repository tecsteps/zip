<?php

declare(strict_types=1);

namespace App\Livewire\Driver;

use App\Models\DamageReport;
use App\Models\User;
use App\Services\DamageReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public ?int $reportToDelete = null;

    private DamageReportService $damageReportService;

    /**
     * Initialize the service on every request.
     */
    public function boot(DamageReportService $damageReportService): void
    {
        $this->damageReportService = $damageReportService;
    }

    /**
     * @return Collection<int, DamageReport>
     */
    public function getReportsProperty(): Collection
    {
        /** @var User $user */
        $user = Auth::user();

        return $this->damageReportService->getReportsForDriver($user);
    }

    public function getHasReportsProperty(): bool
    {
        return $this->reports->isNotEmpty();
    }

    #[Computed]
    public function hasPendingReports(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $this->damageReportService->hasPendingReportsForDriver($user);
    }

    public function delete(int $reportId): void
    {
        $report = $this->damageReportService->findDraftReportForUser($reportId, (int) Auth::id());

        $this->authorize('delete', $report);

        $this->damageReportService->delete($report);
    }

    public function submit(int $reportId): void
    {
        $report = $this->damageReportService->findDraftReportForUser($reportId, (int) Auth::id());

        $this->authorize('submit', $report);

        $this->damageReportService->submit($report);
    }

    public function render(): View
    {
        return view('livewire.driver.dashboard');
    }
}
