<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReportStatus;
use App\Jobs\AnalyzeDamageReportJob;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DamageReportService
{
    /**
     * Get all damage reports for a driver, ordered by most recent.
     *
     * @return Collection<int, DamageReport>
     */
    public function getReportsForDriver(User $driver): Collection
    {
        return DamageReport::query()
            ->forDriver($driver)
            ->latest()
            ->get();
    }

    /**
     * Find a draft report belonging to a specific user.
     *
     * @throws ModelNotFoundException
     */
    public function findDraftReportForUser(int $reportId, int $userId): DamageReport
    {
        return DamageReport::query()
            ->where('user_id', $userId)
            ->where('id', $reportId)
            ->where('status', ReportStatus::Draft)
            ->firstOrFail();
    }

    /**
     * Delete a damage report.
     */
    public function delete(DamageReport $report): void
    {
        $report->delete();
    }

    /**
     * Submit a draft report for review.
     */
    public function submit(DamageReport $report): void
    {
        $report->update([
            'status' => ReportStatus::Submitted,
            'submitted_at' => now(),
        ]);

        AnalyzeDamageReportJob::dispatch($report);
    }

    /**
     * Store an uploaded photo and return the storage path.
     */
    public function storePhoto(TemporaryUploadedFile $photo, int $userId): string
    {
        $extension = $photo->getClientOriginalExtension();
        $filename = Str::uuid()->toString().'.'.$extension;

        return $photo->storeAs(
            "damage-reports/{$userId}",
            $filename,
            'public'
        );
    }

    /**
     * Create a new damage report as draft.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): DamageReport
    {
        return DamageReport::create($this->buildReportData($user, $data, ReportStatus::Draft));
    }

    /**
     * Create a new damage report and submit it immediately.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAndSubmit(User $user, array $data): DamageReport
    {
        $reportData = $this->buildReportData($user, $data, ReportStatus::Submitted);
        $reportData['submitted_at'] = now();

        $report = DamageReport::create($reportData);

        AnalyzeDamageReportJob::dispatch($report);

        return $report;
    }

    /**
     * Update an existing draft damage report.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(DamageReport $report, array $data): DamageReport
    {
        $report->update([
            'package_id' => $data['package_id'],
            'location' => $data['location'],
            'description' => $data['description'] ?? null,
            'photo_path' => $data['photo_path'],
        ]);

        return $report;
    }

    /**
     * Update an existing draft damage report and submit it.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAndSubmit(DamageReport $report, array $data): DamageReport
    {
        $report->update([
            'package_id' => $data['package_id'],
            'location' => $data['location'],
            'description' => $data['description'] ?? null,
            'photo_path' => $data['photo_path'],
            'status' => ReportStatus::Submitted,
            'submitted_at' => now(),
        ]);

        AnalyzeDamageReportJob::dispatch($report);

        return $report;
    }

    /**
     * Delete a photo from storage.
     */
    public function deletePhoto(string $photoPath): void
    {
        Storage::disk('public')->delete($photoPath);
    }

    /**
     * Build the report data array for creation.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function buildReportData(User $user, array $data, ReportStatus $status): array
    {
        return [
            'user_id' => $user->id,
            'package_id' => $data['package_id'],
            'location' => $data['location'],
            'description' => $data['description'] ?? null,
            'photo_path' => $data['photo_path'],
            'status' => $status,
        ];
    }
}
