<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exceptions\OpenRouterException;
use App\Models\DamageReport;
use App\Services\OpenRouterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeDamageReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public DamageReport $damageReport) {}

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Execute the job.
     *
     * @throws OpenRouterException
     */
    public function handle(OpenRouterService $openRouterService): void
    {
        $photoPath = $this->damageReport->photo_path;

        if (empty($photoPath)) {
            return;
        }

        $analysis = $openRouterService->analyzeDamagePhoto($photoPath);

        $this->damageReport->update([
            'ai_severity' => $analysis['severity'],
            'ai_damage_type' => $analysis['damage_type'],
            'ai_value_impact' => $analysis['value_impact'],
            'ai_liability' => $analysis['liability'],
        ]);
    }
}
