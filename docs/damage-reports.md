# Damage Reports

This document describes the damage reporting system implemented in the application.

## Overview

Drivers can create damage reports for packages they encounter during deliveries. Reports include a photo, package ID, location, and optional description. Reports go through a workflow from Draft to Submitted to Approved.

## Report Status

Reports have three possible statuses defined in `app/Enums/ReportStatus.php`:

| Status | Description |
|--------|-------------|
| **Draft** | Initial status. Report can be edited or deleted by the driver. |
| **Submitted** | Report has been submitted for supervisor review. Read-only for driver. |
| **Approved** | Report has been approved by a supervisor. |

## Features

### Feature 2: Driver Dashboard

Drivers see their own reports on the dashboard at `/dashboard`:

- Card-style list showing Package ID, Location, Date, Status, and Photo thumbnail
- Status badges: Draft (gray), Submitted (yellow), Approved (green)
- Draft reports show Submit and Delete actions
- Floating Action Button (FAB) to create new reports
- Empty state with "Create Report" button when no reports exist

### Feature 3: Damage Report Creation

Drivers create new reports at `/driver/reports/create`:

- Photo upload (required, max 5MB, JPG/PNG/WebP)
- Package ID field (required)
- Location field (required)
- Description field (optional)
- Save as Draft or Submit directly

## Routes

| Method | Route | Name | Description |
|--------|-------|------|-------------|
| GET | `/dashboard` | `dashboard` | Driver/Supervisor dashboard |
| GET | `/driver/reports/create` | `driver.reports.create` | Create report form |

## Database Schema

### damage_reports Table

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `user_id` | bigint | Foreign key to users table (driver) |
| `package_id` | string | Package/shipment identifier |
| `location` | string | Address where damage was found |
| `description` | text (nullable) | Optional damage description |
| `photo_path` | string (nullable) | Path to uploaded photo |
| `status` | string | draft, submitted, or approved |
| `ai_severity` | string (nullable) | AI assessment severity |
| `ai_damage_type` | string (nullable) | AI assessment damage type |
| `ai_value_impact` | string (nullable) | AI assessment value impact |
| `ai_liability` | string (nullable) | AI assessment liability |
| `submitted_at` | datetime (nullable) | When report was submitted |
| `approved_at` | datetime (nullable) | When report was approved |
| `approved_by` | bigint (nullable) | Foreign key to users (supervisor) |
| `created_at` | datetime | Creation timestamp |
| `updated_at` | datetime | Last update timestamp |

## Authorization

Report access is controlled by `app/Policies/DamageReportPolicy.php`:

| Action | Allowed |
|--------|---------|
| `create` | Drivers only |
| `view` | Owner only |
| `update` | Owner + Draft status only |
| `delete` | Owner + Draft status only |
| `submit` | Owner + Draft status only |

## Service Layer

All database operations go through `app/Services/DamageReportService.php`:

```php
// Get reports for a driver
$service->getReportsForDriver($user);

// Create a draft report
$service->create($user, [
    'package_id' => 'PKG-12345',
    'location' => '123 Main St',
    'description' => 'Box crushed',
    'photo_path' => 'damage-reports/1/uuid.jpg',
]);

// Create and submit immediately
$service->createAndSubmit($user, $data);

// Submit a draft
$service->submit($report);

// Delete a draft
$service->delete($report);

// Store uploaded photo
$service->storePhoto($uploadedFile, $userId);
```

## File Storage

Photos are stored in the `public` disk under `damage-reports/{user_id}/{uuid}.{ext}`.

## Components

### Livewire Components

| Component | Path | Description |
|-----------|------|-------------|
| `Driver\Dashboard` | `app/Livewire/Driver/Dashboard.php` | Driver dashboard with report list |
| `Driver\CreateReport` | `app/Livewire/Driver/CreateReport.php` | Report creation form |

### Blade Views

| View | Path |
|------|------|
| Dashboard | `resources/views/livewire/driver/dashboard.blade.php` |
| Report Card | `resources/views/livewire/driver/partials/report-card.blade.php` |
| Create Form | `resources/views/livewire/driver/create-report.blade.php` |
| FAB Component | `resources/views/components/fab.blade.php` |

## Testing

### Factory States

```php
// Create draft report (default)
DamageReport::factory()->create();
DamageReport::factory()->draft()->create();

// Create submitted report
DamageReport::factory()->submitted()->create();

// Create approved report
DamageReport::factory()->approved()->create();

// With AI assessment
DamageReport::factory()->withAiAssessment()->create();
```

### Running Tests

```bash
# All damage report tests
php artisan test --filter=DamageReport

# Specific test files
php artisan test tests/Feature/Models/DamageReportTest.php
php artisan test tests/Feature/Policies/DamageReportPolicyTest.php
php artisan test tests/Feature/Livewire/Driver/DashboardTest.php
php artisan test tests/Feature/Livewire/Driver/CreateReportTest.php
```

## File Structure

```
app/
├── Enums/
│   └── ReportStatus.php
├── Livewire/
│   └── Driver/
│       ├── Dashboard.php
│       └── CreateReport.php
├── Models/
│   └── DamageReport.php
├── Policies/
│   └── DamageReportPolicy.php
└── Services/
    └── DamageReportService.php

database/
├── factories/
│   └── DamageReportFactory.php
└── migrations/
    └── *_create_damage_reports_table.php

resources/views/
├── components/
│   └── fab.blade.php
└── livewire/driver/
    ├── dashboard.blade.php
    ├── create-report.blade.php
    └── partials/
        └── report-card.blade.php

tests/Feature/
├── Livewire/Driver/
│   ├── DashboardTest.php
│   └── CreateReportTest.php
├── Models/
│   └── DamageReportTest.php
└── Policies/
    └── DamageReportPolicyTest.php
```

## Usage Examples

### Creating a Report Programmatically

```php
use App\Services\DamageReportService;
use App\Models\User;

$service = app(DamageReportService::class);
$driver = User::factory()->driver()->create();

$report = $service->create($driver, [
    'package_id' => 'PKG-12345',
    'location' => '123 Main Street, Berlin',
    'description' => 'Package was crushed during transit',
    'photo_path' => 'damage-reports/1/photo.jpg',
]);
```

### Checking Report Status in Blade

```blade
@if($report->status === \App\Enums\ReportStatus::Draft)
    <flux:badge variant="default">Draft</flux:badge>
@elseif($report->status === \App\Enums\ReportStatus::Submitted)
    <flux:badge variant="warning">Submitted</flux:badge>
@else
    <flux:badge variant="success">Approved</flux:badge>
@endif
```
