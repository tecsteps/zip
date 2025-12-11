<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\DamageReport;
use App\Models\User;

class DamageReportPolicy
{
    /**
     * Determine whether the user can create damage reports.
     * Only drivers can create damage reports.
     */
    public function create(User $user): bool
    {
        return $user->isDriver();
    }

    /**
     * Determine whether the user can view the model.
     * Owner can view their own reports.
     */
    public function view(User $user, DamageReport $damageReport): bool
    {
        return $this->isOwner($user, $damageReport);
    }

    /**
     * Determine whether the user can update the model.
     * Owner can update only draft reports.
     */
    public function update(User $user, DamageReport $damageReport): bool
    {
        return $this->canModifyDraft($user, $damageReport);
    }

    /**
     * Determine whether the user can delete the model.
     * Owner can delete only draft reports.
     */
    public function delete(User $user, DamageReport $damageReport): bool
    {
        return $this->canModifyDraft($user, $damageReport);
    }

    /**
     * Determine whether the user can submit the model.
     * Owner can submit only draft reports.
     */
    public function submit(User $user, DamageReport $damageReport): bool
    {
        return $this->canModifyDraft($user, $damageReport);
    }

    /**
     * Determine whether the user owns the damage report.
     */
    private function isOwner(User $user, DamageReport $damageReport): bool
    {
        return $user->id === $damageReport->user_id;
    }

    /**
     * Determine whether the user can modify a draft report.
     * Only the owner can modify their own draft reports.
     */
    private function canModifyDraft(User $user, DamageReport $damageReport): bool
    {
        return $this->isOwner($user, $damageReport)
            && $damageReport->status === ReportStatus::Draft;
    }
}
