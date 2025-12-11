<?php

declare(strict_types=1);

namespace App\Livewire\Driver;

use App\Models\DamageReport;
use App\Services\DamageReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Edit Damage Report')]
class EditReport extends Component
{
    public DamageReport $report;

    private DamageReportService $damageReportService;

    public function __construct()
    {
        $this->damageReportService = app(DamageReportService::class);
    }

    public function mount(DamageReport $report): void
    {
        $this->authorize('update', $report);

        $this->report = $report;
    }

    public function render(): View
    {
        return view('livewire.driver.edit-report');
    }
}
