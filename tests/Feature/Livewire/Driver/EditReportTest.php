<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Jobs\AnalyzeDamageReportJob;
use App\Livewire\Driver\EditReport;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('access control', function () {
    test('guest is redirected to login when accessing edit page', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        $this->get(route('driver.reports.edit', $report))
            ->assertRedirect(route('login'));
    });

    test('driver can access edit page for own draft report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        $this->actingAs($driver)
            ->get(route('driver.reports.edit', $report))
            ->assertOk();
    });

    test('driver cannot edit submitted report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->submitted()->create(['user_id' => $driver->id]);

        $this->actingAs($driver)
            ->get(route('driver.reports.edit', $report))
            ->assertForbidden();
    });

    test('driver cannot edit approved report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->approved()->create(['user_id' => $driver->id]);

        $this->actingAs($driver)
            ->get(route('driver.reports.edit', $report))
            ->assertForbidden();
    });

    test('driver cannot edit other driver report', function () {
        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $otherDriver->id]);

        $this->actingAs($driver)
            ->get(route('driver.reports.edit', $report))
            ->assertForbidden();
    });

    test('supervisor cannot access driver edit page', function () {
        $driver = User::factory()->driver()->create();
        $supervisor = User::factory()->supervisor()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        $this->actingAs($supervisor)
            ->get(route('driver.reports.edit', $report))
            ->assertForbidden();
    });
});

describe('form display', function () {
    test('edit form shows all required fields', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->assertSee('Photo')
            ->assertSee('Package ID')
            ->assertSee('Location')
            ->assertSee('Description');
    });

    test('edit form pre-fills package_id from report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'package_id' => 'PKG-12345',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->assertSet('package_id', 'PKG-12345');
    });

    test('edit form pre-fills location from report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'location' => '123 Main Street',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->assertSet('location', '123 Main Street');
    });

    test('edit form pre-fills description from report', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'description' => 'Package was crushed',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->assertSet('description', 'Package was crushed');
    });

    test('edit form stores existing photo path', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->assertSet('existingPhotoPath', 'damage-reports/1/test.jpg');
    });

    test('edit form shows save changes button', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->assertSee('Save Changes');
    });

    test('edit form shows submit button', function () {
        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create(['user_id' => $driver->id]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->assertSee('Submit Report');
    });
});

describe('validation', function () {
    test('package_id is required', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('package_id', '')
            ->call('save')
            ->assertHasErrors(['package_id' => 'required']);
    });

    test('location is required', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('location', '')
            ->call('save')
            ->assertHasErrors(['location' => 'required']);
    });

    test('new photo must be an image', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        $file = UploadedFile::fake()->create('document.svg', 100, 'image/svg+xml');

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('photo', $file)
            ->call('save')
            ->assertHasErrors('photo');
    });

    test('new photo max size is 5MB', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        $file = UploadedFile::fake()->image('photo.jpg')->size(5121);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('photo', $file)
            ->call('save')
            ->assertHasErrors(['photo' => 'max']);
    });
});

describe('save changes', function () {
    test('can update draft report with valid data', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'package_id' => 'PKG-OLD',
            'location' => 'Old Location',
            'description' => 'Old description',
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('package_id', 'PKG-NEW')
            ->set('location', 'New Location')
            ->set('description', 'New description')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $report->refresh();

        expect($report->package_id)->toBe('PKG-NEW')
            ->and($report->location)->toBe('New Location')
            ->and($report->description)->toBe('New description');
    });

    test('updated draft report keeps Draft status', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('package_id', 'PKG-UPDATED')
            ->call('save');

        $report->refresh();

        expect($report->status)->toBe(ReportStatus::Draft);
    });

    test('redirects to dashboard after saving', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->call('save')
            ->assertRedirect(route('dashboard'));
    });

    test('can replace photo when editing draft', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $originalPath = 'damage-reports/'.$driver->id.'/original.jpg';
        Storage::disk('public')->put($originalPath, 'original content');

        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => $originalPath,
        ]);

        $newPhoto = UploadedFile::fake()->image('new-photo.jpg');

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('photo', $newPhoto)
            ->call('save')
            ->assertHasNoErrors();

        $report->refresh();

        expect($report->photo_path)->not->toBe($originalPath)
            ->and($report->photo_path)->toContain("damage-reports/{$driver->id}/");

        Storage::disk('public')->assertExists($report->photo_path);
        Storage::disk('public')->assertMissing($originalPath);
    });

    test('keeps existing photo when no new photo uploaded', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $existingPath = 'damage-reports/'.$driver->id.'/existing.jpg';
        Storage::disk('public')->put($existingPath, 'existing content');

        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => $existingPath,
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('package_id', 'PKG-UPDATED')
            ->call('save')
            ->assertHasNoErrors();

        $report->refresh();

        expect($report->photo_path)->toBe($existingPath);
        Storage::disk('public')->assertExists($existingPath);
    });
});

describe('submit', function () {
    test('can update and submit draft report', function () {
        Storage::fake('public');
        Queue::fake();

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'package_id' => 'PKG-OLD',
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('package_id', 'PKG-SUBMITTED')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $report->refresh();

        expect($report->package_id)->toBe('PKG-SUBMITTED')
            ->and($report->status)->toBe(ReportStatus::Submitted);
    });

    test('submitted report has submitted_at timestamp', function () {
        Storage::fake('public');
        Queue::fake();

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        $this->freezeTime();

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->call('submit');

        $report->refresh();

        expect($report->submitted_at)->not->toBeNull()
            ->and($report->submitted_at->toDateTimeString())->toBe(now()->toDateTimeString());
    });

    test('submit dispatches AnalyzeDamageReportJob', function () {
        Storage::fake('public');
        Queue::fake();

        $driver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->call('submit');

        Queue::assertPushed(AnalyzeDamageReportJob::class, function ($job) use ($report) {
            return $job->damageReport->id === $report->id;
        });
    });

    test('can replace photo and submit', function () {
        Storage::fake('public');
        Queue::fake();

        $driver = User::factory()->driver()->create();
        $originalPath = 'damage-reports/'.$driver->id.'/original.jpg';
        Storage::disk('public')->put($originalPath, 'original content');

        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => $originalPath,
        ]);

        $newPhoto = UploadedFile::fake()->image('new-photo.jpg');

        Livewire::actingAs($driver)
            ->test(EditReport::class, ['report' => $report])
            ->set('photo', $newPhoto)
            ->call('submit')
            ->assertHasNoErrors();

        $report->refresh();

        expect($report->status)->toBe(ReportStatus::Submitted)
            ->and($report->photo_path)->not->toBe($originalPath);

        Storage::disk('public')->assertExists($report->photo_path);
        Storage::disk('public')->assertMissing($originalPath);
    });
});

describe('authorization', function () {
    test('other driver cannot save changes', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $otherDriver = User::factory()->driver()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($otherDriver)
            ->test(EditReport::class, ['report' => $report])
            ->assertForbidden();
    });

    test('supervisor cannot save changes', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $supervisor = User::factory()->supervisor()->create();
        $report = DamageReport::factory()->draft()->create([
            'user_id' => $driver->id,
            'photo_path' => 'damage-reports/1/test.jpg',
        ]);

        Livewire::actingAs($supervisor)
            ->test(EditReport::class, ['report' => $report])
            ->assertForbidden();
    });
});
