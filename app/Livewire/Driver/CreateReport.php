<?php

declare(strict_types=1);

namespace App\Livewire\Driver;

use App\Models\DamageReport;
use App\Models\User;
use App\Services\DamageReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Create Damage Report')]
class CreateReport extends Component
{
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    #[Validate('required|image|max:5120')]
    public $photo = null;

    #[Validate('required|string|max:255')]
    public string $package_id = '';

    #[Validate('required|string|max:255')]
    public string $location = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $description = null;

    private DamageReportService $damageReportService;

    public function __construct()
    {
        $this->damageReportService = app(DamageReportService::class);
    }

    /**
     * Save the report as a draft.
     */
    public function saveDraft(): void
    {
        $this->createReport(submitted: false);
    }

    /**
     * Submit the report for review.
     */
    public function submit(): void
    {
        $this->createReport(submitted: true);
    }

    /**
     * Create a damage report (as draft or submitted).
     */
    private function createReport(bool $submitted): void
    {
        $this->authorize('create', DamageReport::class);
        $this->validate();

        /** @var User $user */
        $user = Auth::user();

        $photoPath = $this->damageReportService->storePhoto($this->photo, $user->id);

        $data = [
            'package_id' => $this->package_id,
            'location' => $this->location,
            'description' => $this->description,
            'photo_path' => $photoPath,
        ];

        if ($submitted) {
            $this->damageReportService->createAndSubmit($user, $data);
            session()->flash('status', 'Report submitted successfully.');
        } else {
            $this->damageReportService->create($user, $data);
            session()->flash('status', 'Report saved as draft.');
        }

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.driver.create-report');
    }
}
