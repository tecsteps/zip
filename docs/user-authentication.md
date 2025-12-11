# User Authentication

This document describes the role-based authentication system implemented in the application.

## Overview

The application supports two user roles:

| Role | Description |
|------|-------------|
| **Driver** | Default role assigned to all new registrations. Can view and manage their own damage reports. |
| **Supervisor** | Administrative role with access to all driver reports. Must be created via seeder or database. |

## User Roles

### UserRole Enum

Roles are defined in `app/Enums/UserRole.php`:

```php
enum UserRole: string
{
    case Driver = 'driver';
    case Supervisor = 'supervisor';
}
```

### User Model Helpers

The `User` model provides helper methods for role checking:

```php
$user->isDriver();     // Returns true if user has Driver role
$user->isSupervisor(); // Returns true if user has Supervisor role
```

## Registration

All users who register through the application are automatically assigned the **Driver** role. This is handled in `app/Actions/Fortify/CreateNewUser.php`.

Supervisors cannot self-register and must be created by:
- Running the `SupervisorSeeder`
- Direct database insertion
- A future admin interface

## Middleware

Two middleware classes are available for protecting routes:

### EnsureUserIsDriver

Restricts access to driver-only routes.

```php
Route::middleware(['auth', 'driver'])->group(function () {
    // Driver-only routes
});
```

### EnsureUserIsSupervisor

Restricts access to supervisor-only routes.

```php
Route::middleware(['auth', 'supervisor'])->group(function () {
    // Supervisor-only routes
});
```

Both middleware return a `403 Forbidden` response if the user does not have the required role.

## Dashboard

The dashboard (`/dashboard`) displays role-appropriate content:

- **Drivers** see "My Reports" - their own damage reports
- **Supervisors** see "All Reports" - reports from all drivers

## Test Users

The database seeder creates the following test users:

| Email | Password | Role |
|-------|----------|------|
| `driver1@zip.test` | `password` | Driver |
| `driver2@zip.test` | `password` | Driver |
| `supervisor@zip.test` | `password` | Supervisor |

### Seeding the Database

```bash
# Fresh migration with seeding
php artisan migrate:fresh --seed

# Or just run seeders
php artisan db:seed
```

## Database Schema

The `users` table includes a `role` column:

| Column | Type | Default |
|--------|------|---------|
| `role` | `string` | `'driver'` |

The column is indexed for query performance.

## Testing

### Factory States

The `UserFactory` provides states for creating test users:

```php
// Create a driver (default)
User::factory()->create();
User::factory()->driver()->create();

// Create a supervisor
User::factory()->supervisor()->create();
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test files
php artisan test tests/Unit/Models/UserTest.php
php artisan test tests/Feature/Middleware/EnsureUserIsDriverTest.php
php artisan test tests/Feature/Middleware/EnsureUserIsSupervisorTest.php
php artisan test tests/Feature/DashboardTest.php
```

## File Structure

```
app/
├── Enums/
│   └── UserRole.php
├── Http/
│   └── Middleware/
│       ├── EnsureUserIsDriver.php
│       └── EnsureUserIsSupervisor.php
├── Models/
│   └── User.php
└── Actions/
    └── Fortify/
        └── CreateNewUser.php

database/
├── factories/
│   └── UserFactory.php
├── migrations/
│   └── *_add_role_to_users_table.php
└── seeders/
    ├── DatabaseSeeder.php
    └── SupervisorSeeder.php

tests/
├── Unit/
│   └── Models/
│       └── UserTest.php
└── Feature/
    ├── Middleware/
    │   ├── EnsureUserIsDriverTest.php
    │   └── EnsureUserIsSupervisorTest.php
    └── DashboardTest.php
```

## Usage Examples

### Checking User Role in Blade

```blade
@if(auth()->user()->isDriver())
    {{-- Driver content --}}
@endif

@if(auth()->user()->isSupervisor())
    {{-- Supervisor content --}}
@endif
```

### Checking User Role in Services

All data access must go through service classes with constructor dependency injection:

```php
// app/Services/ReportService.php
class ReportService
{
    public function getReportsForUser(User $user): Collection
    {
        if ($user->isSupervisor()) {
            return DamageReport::query()->latest()->get();
        }

        return DamageReport::query()
            ->forDriver($user)
            ->latest()
            ->get();
    }
}

// app/Livewire/Dashboard.php - Inject service via constructor
class Dashboard extends Component
{
    public function __construct(private ReportService $reportService) {}

    public function getReportsProperty(): Collection
    {
        return $this->reportService->getReportsForUser(auth()->user());
    }
}
```

**Important:** Never query the database directly in Livewire components or controllers. Always delegate to service classes.

### Protecting Routes

```php
// In routes/web.php
Route::middleware(['auth', 'driver'])->group(function () {
    Route::get('/my-reports', [ReportController::class, 'index']);
    Route::post('/reports', [ReportController::class, 'store']);
});

Route::middleware(['auth', 'supervisor'])->group(function () {
    Route::get('/all-reports', [SupervisorController::class, 'index']);
});
```

## Architectural Guidelines

This project follows strict architectural rules:

### 1. Service Layer for Data Access

- **NEVER** query the database directly from Livewire components
- **ALL** database queries must be in service classes
- Livewire components handle UI concerns only

### 2. Constructor Dependency Injection

- **NEVER** use `app()` helper inside methods
- **NEVER** use `new ServiceClass()` inside methods
- **ALWAYS** inject dependencies via constructor

```php
// WRONG - Don't do this
public function getReports(): Collection
{
    $service = app(ReportService::class);  // Violation!
    return $service->getAll();
}

// CORRECT - Always do this
public function __construct(private ReportService $reportService) {}

public function getReports(): Collection
{
    return $this->reportService->getAll();
}
```
