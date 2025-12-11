<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Livewire\Driver\CreateReport;
use App\Models\DamageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('access control', function () {
    test('guest is redirected to login when accessing create page', function () {
        $this->get(route('driver.reports.create'))
            ->assertRedirect(route('login'));
    });

    test('authenticated driver can access create page', function () {
        $driver = User::factory()->driver()->create();

        $this->actingAs($driver)
            ->get(route('driver.reports.create'))
            ->assertOk();
    });

    test('supervisor cannot access create page', function () {
        $supervisor = User::factory()->supervisor()->create();

        $this->actingAs($supervisor)
            ->get(route('driver.reports.create'))
            ->assertForbidden();
    });
});

describe('form display', function () {
    test('create form shows all required fields', function () {
        $driver = User::factory()->driver()->create();

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->assertSee('Photo')
            ->assertSee('Package ID')
            ->assertSee('Location')
            ->assertSee('Description');
    });

    test('create form shows photo upload zone', function () {
        $driver = User::factory()->driver()->create();

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->assertSee('Click to upload or drag and drop')
            ->assertSee('JPG, PNG, WebP (max 5MB)');
    });

    test('create form shows save draft button', function () {
        $driver = User::factory()->driver()->create();

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->assertSee('Save Draft');
    });

    test('create form shows submit button', function () {
        $driver = User::factory()->driver()->create();

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->assertSee('Submit Report');
    });
});

describe('validation', function () {
    test('photo is required', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('saveDraft')
            ->assertHasErrors(['photo' => 'required']);
    });

    test('photo must be an image', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        // Use an image-like extension that passes preview but fails image validation
        $file = UploadedFile::fake()->create('document.svg', 100, 'image/svg+xml');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->set('photo', $file)
            ->call('saveDraft')
            ->assertHasErrors('photo');
    });

    test('photo max size is 5MB', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg')->size(5121); // 5MB + 1KB

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('saveDraft')
            ->assertHasErrors(['photo' => 'max']);
    });

    test('package_id is required', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', '')
            ->set('location', '123 Main St')
            ->call('saveDraft')
            ->assertHasErrors(['package_id' => 'required']);
    });

    test('package_id max length is 255', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', str_repeat('a', 256))
            ->set('location', '123 Main St')
            ->call('saveDraft')
            ->assertHasErrors(['package_id' => 'max']);
    });

    test('location is required', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '')
            ->call('saveDraft')
            ->assertHasErrors(['location' => 'required']);
    });

    test('location max length is 255', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', str_repeat('a', 256))
            ->call('saveDraft')
            ->assertHasErrors(['location' => 'max']);
    });

    test('description is optional', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->set('description', null)
            ->call('saveDraft')
            ->assertHasNoErrors('description');

        expect(DamageReport::count())->toBe(1);
    });

    test('description max length is 1000', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->set('description', str_repeat('a', 1001))
            ->call('saveDraft')
            ->assertHasErrors(['description' => 'max']);
    });
});

describe('validation with datasets', function () {
    test('required fields show validation errors', function (string $field) {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $component = Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St');

        if ($field === 'photo') {
            $component->set('photo', null);
        } else {
            $component->set($field, '');
        }

        $component->call('saveDraft')
            ->assertHasErrors([$field => 'required']);
    })->with(['photo', 'package_id', 'location']);

    test('max length fields show validation errors', function (string $field, int $maxLength) {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->set($field, str_repeat('a', $maxLength + 1))
            ->call('saveDraft')
            ->assertHasErrors([$field => 'max']);
    })->with([
        'package_id' => ['package_id', 255],
        'location' => ['location', 255],
        'description' => ['description', 1000],
    ]);
});

describe('save draft', function () {
    test('can save draft with valid data', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->set('description', 'Test damage description')
            ->call('saveDraft')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        expect(DamageReport::count())->toBe(1);
    });

    test('draft report has status Draft', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('saveDraft');

        $report = DamageReport::first();

        expect($report->status)->toBe(ReportStatus::Draft);
    });

    test('draft report does not have submitted_at timestamp', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('saveDraft');

        $report = DamageReport::first();

        expect($report->submitted_at)->toBeNull();
    });

    test('photo is stored correctly', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('saveDraft');

        $report = DamageReport::first();

        expect($report->photo_path)->not->toBeNull()
            ->and($report->photo_path)->toContain("damage-reports/{$driver->id}/");

        Storage::disk('public')->assertExists($report->photo_path);
    });

    test('redirects to dashboard after saving draft', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('saveDraft')
            ->assertRedirect(route('dashboard'));
    });

    test('draft report belongs to authenticated user', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('saveDraft');

        $report = DamageReport::first();

        expect($report->user_id)->toBe($driver->id);
    });

    test('draft report stores all form data correctly', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-99999')
            ->set('location', '456 Oak Avenue')
            ->set('description', 'Package was crushed during transit')
            ->call('saveDraft');

        $report = DamageReport::first();

        expect($report->package_id)->toBe('PKG-99999')
            ->and($report->location)->toBe('456 Oak Avenue')
            ->and($report->description)->toBe('Package was crushed during transit');
    });
});

describe('submit', function () {
    test('can submit report with valid data', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->set('description', 'Test damage description')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        expect(DamageReport::count())->toBe(1);
    });

    test('submitted report has status Submitted', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('submit');

        $report = DamageReport::first();

        expect($report->status)->toBe(ReportStatus::Submitted);
    });

    test('submitted report has submitted_at timestamp', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        $this->freezeTime();

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('submit');

        $report = DamageReport::first();

        expect($report->submitted_at)->not->toBeNull()
            ->and($report->submitted_at->toDateTimeString())->toBe(now()->toDateTimeString());
    });

    test('redirects to dashboard after submitting', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('submit')
            ->assertRedirect(route('dashboard'));
    });

    test('submitted report stores photo correctly', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('submit');

        $report = DamageReport::first();

        expect($report->photo_path)->not->toBeNull();
        Storage::disk('public')->assertExists($report->photo_path);
    });

    test('submitted report belongs to authenticated user', function () {
        Storage::fake('public');

        $driver = User::factory()->driver()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($driver)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('submit');

        $report = DamageReport::first();

        expect($report->user_id)->toBe($driver->id);
    });
});

describe('authorization', function () {
    test('supervisor cannot save draft', function () {
        Storage::fake('public');

        $supervisor = User::factory()->supervisor()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($supervisor)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('saveDraft')
            ->assertForbidden();

        expect(DamageReport::count())->toBe(0);
    });

    test('supervisor cannot submit report', function () {
        Storage::fake('public');

        $supervisor = User::factory()->supervisor()->create();
        $file = UploadedFile::fake()->image('photo.jpg');

        Livewire::actingAs($supervisor)
            ->test(CreateReport::class)
            ->set('photo', $file)
            ->set('package_id', 'PKG-12345')
            ->set('location', '123 Main St')
            ->call('submit')
            ->assertForbidden();

        expect(DamageReport::count())->toBe(0);
    });
});
