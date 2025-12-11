<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Livewire\Driver\Dashboard;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('authentication', function () {
    test('dashboard component throws exception when user is not authenticated', function () {
        Livewire::test(Dashboard::class);
    })->throws(\Illuminate\View\ViewException::class);
});

describe('report visibility', function () {
    test('driver sees only own reports', function () {
        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();

        $ownReport = DamageReport::factory()->create(['user_id' => $driver->id]);
        $otherReport = DamageReport::factory()->create(['user_id' => $otherDriver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSee($ownReport->package_id)
            ->assertDontSee($otherReport->package_id);
    });

    test('reports are ordered newest first', function () {
        $driver = User::factory()->driver()->create();

        $olderReport = DamageReport::factory()->create([
            'user_id' => $driver->id,
            'created_at' => now()->subDays(2),
        ]);
        $newerReport = DamageReport::factory()->create([
            'user_id' => $driver->id,
            'created_at' => now()->subDay(),
        ]);
        $newestReport = DamageReport::factory()->create([
            'user_id' => $driver->id,
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($driver)->test(Dashboard::class);

        $reports = $component->get('reports');

        expect($reports->pluck('id')->toArray())
            ->toBe([$newestReport->id, $newerReport->id, $olderReport->id]);
    });
});

describe('empty state', function () {
    test('empty state is displayed when no reports exist', function () {
        $driver = User::factory()->driver()->create();

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSee('No damage reports yet')
            ->assertSee('Create your first damage report to get started.');
    });
});

describe('action buttons visibility', function () {
    test('draft reports show action buttons', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSee($report->package_id)
            ->assertSee('Edit')
            ->assertSee('Submit')
            ->assertSee('Delete');
    });

    test('submitted reports hide action buttons', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSee($report->package_id)
            ->assertDontSee('Edit')
            ->assertDontSeeHtml('wire:click="submit('.$report->id.')"')
            ->assertDontSeeHtml('reportId: '.$report->id);
    });

    test('approved reports hide action buttons', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSee($report->package_id)
            ->assertDontSee('Edit')
            ->assertDontSeeHtml('wire:click="submit('.$report->id.')"')
            ->assertDontSeeHtml('reportId: '.$report->id);
    });
});

describe('delete action', function () {
    test('can delete a draft report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->call('delete', $report->id)
            ->assertDontSee($report->package_id);

        expect(DamageReport::find($report->id))->toBeNull();
    });

    test('cannot delete a submitted report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->call('delete', $report->id);
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    test('cannot delete an approved report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->call('delete', $report->id);
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    test('submitted report still exists after failed delete attempt', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $driver->id]);

        try {
            Livewire::actingAs($driver)
                ->test(Dashboard::class)
                ->call('delete', $report->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // Expected exception
        }

        expect(DamageReport::find($report->id))->not->toBeNull();
    });

    test('cannot delete another drivers report', function () {
        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $otherDriver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->call('delete', $report->id);
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    test('other drivers report still exists after failed delete attempt', function () {
        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $otherDriver->id]);

        try {
            Livewire::actingAs($driver)
                ->test(Dashboard::class)
                ->call('delete', $report->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // Expected exception
        }

        expect(DamageReport::find($report->id))->not->toBeNull();
    });

    test('approved report still exists after failed delete attempt', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $driver->id]);

        try {
            Livewire::actingAs($driver)
                ->test(Dashboard::class)
                ->call('delete', $report->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // Expected exception
        }

        expect(DamageReport::find($report->id))->not->toBeNull();
    });

    test('reportToDelete property is set correctly', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->set('reportToDelete', $report->id)
            ->assertSet('reportToDelete', $report->id);
    });
});

describe('submit action', function () {
    test('can submit a draft report', function () {
        Queue::fake();

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->call('submit', $report->id);

        $report->refresh();

        expect($report->status)->toBe(ReportStatus::Submitted)
            ->and($report->submitted_at)->not->toBeNull();
    });

    test('cannot submit an already submitted report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->call('submit', $report->id);
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    test('cannot submit an approved report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->call('submit', $report->id);
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    test('cannot submit another drivers report', function () {
        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $otherDriver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->call('submit', $report->id);
    })->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    test('other drivers report remains draft after failed submit attempt', function () {
        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $otherDriver->id]);

        try {
            Livewire::actingAs($driver)
                ->test(Dashboard::class)
                ->call('submit', $report->id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            // Expected exception
        }

        $report->refresh();

        expect($report->status)->toBe(ReportStatus::Draft);
    });
});

describe('FAB component', function () {
    test('FAB is always visible regardless of report count', function (int $reportCount) {
        $driver = User::factory()->driver()->create();

        if ($reportCount > 0) {
            DamageReport::factory()->count($reportCount)->create(['user_id' => $driver->id]);
        }

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSeeHtml('aria-label="Create new report"');
    })->with([
        'no reports' => 0,
        'with reports' => 3,
    ]);
});

describe('computed properties', function () {
    test('hasReports returns true when reports exist', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSet('hasReports', true);
    });

    test('hasReports returns false when no reports exist', function () {
        $driver = User::factory()->driver()->create();

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSet('hasReports', false);
    });

    test('reports property returns collection of damage reports', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->count(3)->create(['user_id' => $driver->id]);

        $component = Livewire::actingAs($driver)->test(Dashboard::class);

        $reports = $component->get('reports');

        expect($reports)->toHaveCount(3)
            ->and($reports->first())->toBeInstanceOf(DamageReport::class);
    });

    test('hasPendingReports returns true when submitted reports without ai_severity exist', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->submitted()->create([
            'user_id' => $driver->id,
            'ai_severity' => null,
        ]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSet('hasPendingReports', true);
    });

    test('hasPendingReports returns false when submitted reports have ai_severity', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->submitted()->create([
            'user_id' => $driver->id,
            'ai_severity' => 'moderate',
        ]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSet('hasPendingReports', false);
    });

    test('hasPendingReports returns false when no submitted reports exist', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSet('hasPendingReports', false);
    });

    test('hasPendingReports returns false when only approved reports exist', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->approved()->create([
            'user_id' => $driver->id,
            'ai_severity' => null,
        ]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSet('hasPendingReports', false);
    });
});

describe('severity badges', function () {
    test('pending reports show Analyzing badge', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->submitted()->create([
            'user_id' => $driver->id,
            'ai_severity' => null,
        ]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSee('Analyzing...');
    });

    test('completed reports show severity badge', function (string $severity) {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->submitted()->create([
            'user_id' => $driver->id,
            'ai_severity' => $severity,
        ]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSee(ucfirst($severity))
            ->assertDontSee('Analyzing...');
    })->with(['low', 'moderate', 'high', 'critical']);

    test('draft reports do not show severity badge', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'ai_severity' => null,
        ]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertDontSee('Analyzing...');
    });

    test('draft reports do not show severity badge even if ai_severity is set', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'ai_severity' => 'moderate',
        ]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertDontSee('Moderate');
    });
});

describe('polling', function () {
    test('wire:poll is present when pending reports exist', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->submitted()->create([
            'user_id' => $driver->id,
            'ai_severity' => null,
        ]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertSeeHtml('wire:poll.3s');
    });

    test('wire:poll is not present when no pending reports exist', function () {
        $driver = User::factory()->driver()->create();
        DamageReport::factory()->submitted()->create([
            'user_id' => $driver->id,
            'ai_severity' => 'moderate',
        ]);

        Livewire::actingAs($driver)
            ->test(Dashboard::class)
            ->assertDontSeeHtml('wire:poll.3s');
    });
});
