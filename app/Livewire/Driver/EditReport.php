<?php

declare(strict_types=1);

namespace App\Livewire\Driver;

use App\Enums\ReportStatus;
use App\Models\DamageReport;
use App\Services\DamageReportService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Edit Damage Report')]
class EditReport extends Component
{
    use WithFileUploads;

    public DamageReport $report;

    /** @var TemporaryUploadedFile|null */
    #[Validate('nullable|image|max:5120|mimes:jpg,jpeg,png,webp')]
    public $photo = null;

    public ?string $existingPhotoPath = null;

    #[Validate('required|string|max:255')]
    public string $package_id = '';

    #[Validate('required|string|max:255')]
    public string $location = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $description = null;

    private DamageReportService $damageReportService;

    /**
     * Initialize the service on every request.
     */
    public function boot(DamageReportService $damageReportService): void
    {
        $this->damageReportService = $damageReportService;
    }

    public function mount(DamageReport $report): void
    {
        $this->authorize('update', $report);

        if ($report->status !== ReportStatus::Draft) {
            abort(403, 'Only draft reports can be edited.');
        }

        $this->report = $report;
        $this->package_id = $report->package_id;
        $this->location = $report->location;
        $this->description = $report->description;
        $this->existingPhotoPath = $report->photo_path;
    }

    /**
     * Save the report as a draft (update without submitting).
     */
    public function save(): void
    {
        $this->updateReport(submitted: false);
    }

    /**
     * Update and submit the report for review.
     */
    public function submit(): void
    {
        $this->updateReport(submitted: true);
    }

    /**
     * Update the damage report (save or submit).
     */
    private function updateReport(bool $submitted): void
    {
        $this->authorize('update', $this->report);

        $this->validate();

        $photoPath = $this->existingPhotoPath;

        if ($this->photo !== null) {
            $photoPath = $this->damageReportService->storePhoto($this->photo, $this->report->user_id);

            if ($this->existingPhotoPath !== null) {
                $this->damageReportService->deletePhoto($this->existingPhotoPath);
            }
        }

        $data = [
            'package_id' => $this->package_id,
            'location' => $this->location,
            'description' => $this->description,
            'photo_path' => $photoPath,
        ];

        if ($submitted) {
            $this->damageReportService->updateAndSubmit($this->report, $data);
            session()->flash('status', 'Report submitted successfully.');
        } else {
            $this->damageReportService->update($this->report, $data);
            session()->flash('status', 'Report updated successfully.');
        }

        $this->redirect(route('dashboard'), navigate: true);
    }

    /**
     * Remove the newly uploaded photo (revert to existing).
     */
    public function removeNewPhoto(): void
    {
        $this->photo = null;
    }

    public function render(): View
    {
        return view('livewire.driver.edit-report');
    }
}
