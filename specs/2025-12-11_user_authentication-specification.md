# User Authentication - Technical Specification

**Date:** 2025-12-11
**Status:** Ready for Implementation
**Q&A Reference:** specs/2025-12-11_user_authentication-questions.md

---

## 1. Overview

### 1.1 Feature Summary
User Authentication provides email/password login with role-based access control for the zip damage reporting app. Two roles exist: Drivers (who create damage reports) and Supervisors (who review all reports). The system uses Laravel Fortify for authentication backend with custom Livewire/Volt views.

### 1.2 Business Value
Enables secure access to the damage reporting system with appropriate data isolation - drivers can only see their own reports while supervisors have visibility across all reports.

### 1.3 Target Users
- **Drivers:** Courier drivers who encounter damaged packages and need to submit reports
- **Supervisors:** Managers who review and approve damage reports from all drivers

---

## 2. Requirements

### 2.1 Functional Requirements
- [ ] FR-1: Users can register with email/password (auto-assigned Driver role)
- [ ] FR-2: Users can log in with email/password
- [ ] FR-3: Users can log out
- [ ] FR-4: Users can reset their password via email (existing Laravel/Fortify flow)
- [ ] FR-5: System distinguishes between Driver and Supervisor roles
- [ ] FR-6: New registrations are auto-assigned the Driver role
- [ ] FR-7: Role middleware prevents drivers from accessing supervisor routes
- [ ] FR-8: Role middleware prevents supervisors from accessing driver routes
- [ ] FR-9: All users redirect to `/dashboard` after login
- [ ] FR-10: Dashboard displays role-appropriate content

**Note:** Data ownership (drivers only see own reports) will be implemented in Feature 3 when DamageReports are created.

### 2.2 Non-Functional Requirements
- [ ] NFR-1: Password minimum 8 characters (Laravel default)
- [ ] NFR-2: Login attempts rate-limited (Fortify default: 5 per minute)
- [ ] NFR-3: All auth pages must be mobile-responsive

### 2.3 Out of Scope
- Email verification (disabled for now)
- Two-factor authentication UI (Fortify enabled but not used)
- Social login (OAuth)
- Supervisor self-registration
- Data ownership query scoping (implemented in Feature 3 when DamageReports are created)

### 2.4 Existing Setup (No Changes Needed)
- Fortify configuration (`config/fortify.php`) - already has registration and resetPasswords enabled
- Auth views (`resources/views/livewire/auth/`) - already exist and work with Fortify
- Dashboard route (`routes/web.php`) - already registered at `/dashboard` with auth middleware
- FortifyServiceProvider - already registers views correctly

---

## 3. Architecture

### 3.1 Component Overview

```
+------------------+     +------------------+     +------------------+
|   Auth Views     |     |     Fortify      |     |    User Model    |
| (Livewire/Blade) | --> | (Backend Logic)  | --> |   (with role)    |
+------------------+     +------------------+     +------------------+
                                |
                                v
                    +------------------------+
                    |  Role Middleware       |
                    | EnsureUserIsDriver     |
                    | EnsureUserIsSupervisor |
                    +------------------------+
```

### 3.2 Data Flow

1. **Registration Flow:**
   - User submits registration form
   - Fortify validates input via `CreateNewUser` action
   - User created with `role = 'driver'`
   - User redirected to `/dashboard`

2. **Login Flow:**
   - User submits login form
   - Fortify authenticates credentials
   - User redirected to `/dashboard`
   - Dashboard displays role-appropriate content

3. **Authorization Flow:**
   - Middleware checks user role for protected routes
   - Driver routes enforce data ownership via query scoping
   - Supervisor routes allow access to all data

### 3.3 Dependencies
- Laravel Fortify (already installed)
- Flux UI components (already installed)
- Existing auth views in `resources/views/livewire/auth/`

---

## 4. Database Schema

### 4.1 New Tables
None required.

### 4.2 Table Modifications

**Table: `users`**
- Add column: `role` (string, default: 'driver') - User role enum

```php
// Laravel migration syntax
$table->string('role')->default('driver')->after('email');
$table->index('role');
```

### 4.3 Relationships
- User has many DamageReports (to be created in Feature 3)
- DamageReport belongs to User (driver who created it)

### 4.4 Indexes
- Index on `users.role` for filtering by role

---

## 5. Implementation Steps

### 5.1 Phase 1: Database & Model Setup
**Iteration scope:** Add role column to users table, update User model with role helpers

#### Requirements:
- [ ] **REQ-1.1:** Create migration `database/migrations/YYYY_MM_DD_HHMMSS_add_role_to_users_table.php` adding `role` column with default `'driver'` and index on `role`
- [ ] **REQ-1.2:** Create enum `app/Enums/UserRole.php` with cases: `Driver`, `Supervisor`
- [ ] **REQ-1.3:** Update `app/Models/User.php` to add `role` to `$fillable` array
- [ ] **REQ-1.4:** Update `app/Models/User.php` to add `role` cast to `UserRole` enum in `casts()` method
- [ ] **REQ-1.5:** Update `app/Models/User.php` to add `isDriver(): bool` helper method
- [ ] **REQ-1.6:** Update `app/Models/User.php` to add `isSupervisor(): bool` helper method
- [ ] **REQ-1.7:** Update `database/factories/UserFactory.php` to include `role` with default `UserRole::Driver`
- [ ] **REQ-1.8:** Update `database/factories/UserFactory.php` to add `supervisor()` state that sets `role` to `UserRole::Supervisor`
- [ ] **REQ-1.9:** Update `database/factories/UserFactory.php` to add `driver()` state that sets `role` to `UserRole::Driver`
- [ ] **REQ-1.10:** Run migration: `php artisan migrate`
- [ ] **REQ-1.11:** Create test file `tests/Unit/Models/UserTest.php` with tests for: isDriver(), isSupervisor(), role casting

#### Implementation Notes:
```php
// app/Enums/UserRole.php
enum UserRole: string
{
    case Driver = 'driver';
    case Supervisor = 'supervisor';
}

// User model helper methods
public function isDriver(): bool
{
    return $this->role === UserRole::Driver;
}

public function isSupervisor(): bool
{
    return $this->role === UserRole::Supervisor;
}
```

---

### 5.2 Phase 2: Registration & Seeder
**Iteration scope:** Auto-assign driver role on registration, create supervisor seeder

#### Requirements:
- [ ] **REQ-2.1:** Update `app/Actions/Fortify/CreateNewUser.php` to set `role` to `UserRole::Driver` when creating user
- [ ] **REQ-2.2:** Create seeder `database/seeders/SupervisorSeeder.php` that creates supervisor: email=`supervisor@zip.test`, password=`password`, role=`supervisor`
- [ ] **REQ-2.3:** Update `database/seeders/DatabaseSeeder.php` to call `SupervisorSeeder`
- [ ] **REQ-2.4:** Create test file `tests/Feature/Auth/RegistrationTest.php` testing: new user registration assigns driver role
- [ ] **REQ-2.5:** Run seeder: `php artisan db:seed --class=SupervisorSeeder`

#### Implementation Notes:
```php
// SupervisorSeeder.php
User::factory()->supervisor()->create([
    'name' => 'Supervisor',
    'email' => 'supervisor@zip.test',
]);
```

---

### 5.3 Phase 3: Role Middleware
**Iteration scope:** Create middleware to protect routes by role

#### Requirements:
- [ ] **REQ-3.1:** Create middleware `app/Http/Middleware/EnsureUserIsDriver.php` that aborts 403 if user is not a driver
- [ ] **REQ-3.2:** Create middleware `app/Http/Middleware/EnsureUserIsSupervisor.php` that aborts 403 if user is not a supervisor
- [ ] **REQ-3.3:** Register middleware aliases in `bootstrap/app.php`: `'driver'` and `'supervisor'`
- [ ] **REQ-3.4:** Create test file `tests/Feature/Middleware/EnsureUserIsDriverTest.php` testing: driver access, supervisor denied, guest redirect
- [ ] **REQ-3.5:** Create test file `tests/Feature/Middleware/EnsureUserIsSupervisorTest.php` testing: supervisor access, driver denied, guest redirect

#### Implementation Notes:
```php
// EnsureUserIsDriver.php
public function handle(Request $request, Closure $next): Response
{
    if (! $request->user()?->isDriver()) {
        abort(403);
    }

    return $next($request);
}

// bootstrap/app.php - register middleware aliases
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'driver' => \App\Http\Middleware\EnsureUserIsDriver::class,
        'supervisor' => \App\Http\Middleware\EnsureUserIsSupervisor::class,
    ]);
})
```

---

### 5.4 Phase 4: Dashboard Update
**Iteration scope:** Update dashboard to show role-appropriate content

#### Requirements:
- [ ] **REQ-4.1:** Update existing `resources/views/dashboard.blade.php` to conditionally display Driver or Supervisor dashboard based on `auth()->user()->role`
- [ ] **REQ-4.2:** Driver view shows: welcome message, placeholder for "My Reports" section
- [ ] **REQ-4.3:** Supervisor view shows: welcome message, placeholder for "All Reports" section
- [ ] **REQ-4.4:** Create test file `tests/Feature/DashboardTest.php` testing: driver content, supervisor content, guest redirect

#### Implementation Notes:
```blade
@if(auth()->user()->isDriver())
    {{-- Driver Dashboard Content --}}
@else
    {{-- Supervisor Dashboard Content --}}
@endif
```

---

### 5.5 Phase 5: Integration & Testing
**Iteration scope:** Final integration and comprehensive testing

#### Requirements:
- [ ] **REQ-5.1:** All tests pass (`php artisan test`)
- [ ] **REQ-5.2:** Code style validated (`vendor/bin/pint --dirty`)
- [ ] **REQ-5.3:** Manual test: Register new user, check database shows `role = 'driver'`, verify dashboard shows "My Reports" placeholder
- [ ] **REQ-5.4:** Manual test: Login as `supervisor@zip.test` with password `password`, verify dashboard shows "All Reports" placeholder
- [ ] **REQ-5.5:** Manual test: Click "Forgot password?" on login, enter email, verify form submits successfully (email delivery depends on mail config)

---

## 6. API / Interface Design

### 6.1 Routes

**Existing routes (no changes needed):**
| Method | Route | Handler | Middleware | Description |
|--------|-------|---------|------------|-------------|
| GET | `/login` | Fortify | guest | Login form |
| POST | `/login` | Fortify | guest | Process login |
| POST | `/logout` | Fortify | auth | Logout user |
| GET | `/register` | Fortify | guest | Registration form |
| POST | `/register` | Fortify | guest | Process registration |
| GET | `/forgot-password` | Fortify | guest | Password reset request form |
| POST | `/forgot-password` | Fortify | guest | Send reset email |
| GET | `/reset-password/{token}` | Fortify | guest | Password reset form |
| POST | `/reset-password` | Fortify | guest | Process password reset |
| GET | `/dashboard` | view | auth | User dashboard |

**Future routes (for Features 2-5):**
| Method | Route | Middleware | Description |
|--------|-------|------------|-------------|
| GET | `/driver/*` | auth, driver | Driver-specific routes |
| GET | `/supervisor/*` | auth, supervisor | Supervisor-specific routes |

### 6.2 Livewire Components
Existing auth components in `resources/views/livewire/auth/` are used as-is.

### 6.3 Actions/Services

| Class | Purpose | Input | Output |
|-------|---------|-------|--------|
| `CreateNewUser` | Create new user with driver role | name, email, password | User |

---

## 7. UI/UX Specification

### 7.1 Page/Component Layout

**Login Page** (existing):
```
+----------------------------------+
|           [App Logo]             |
|     Log in to your account       |
|   Enter your email and password  |
|                                  |
|   [Email Input]                  |
|   [Password Input] [Forgot?]     |
|   [ ] Remember me                |
|   [      Log in      ]           |
|                                  |
|   Don't have an account? Sign up |
+----------------------------------+
```

**Registration Page** (existing):
```
+----------------------------------+
|           [App Logo]             |
|      Create an account           |
|                                  |
|   [Name Input]                   |
|   [Email Input]                  |
|   [Password Input]               |
|   [Confirm Password Input]       |
|   [      Create account  ]       |
|                                  |
|   Already have account? Log in   |
+----------------------------------+
```

**Dashboard** (to be updated):
```
+----------------------------------+
| [Logo]  Dashboard    [User Menu] |
+----------------------------------+
|                                  |
|   Welcome, {name}!               |
|   Role: Driver/Supervisor        |
|                                  |
|   [Role-specific content area]   |
|   (placeholder for now)          |
|                                  |
+----------------------------------+
```

### 7.2 User Interactions
1. User visits `/login` -> enters credentials -> clicks "Log in" -> redirected to `/dashboard`
2. User visits `/register` -> fills form -> clicks "Create account" -> auto-assigned driver -> redirected to `/dashboard`
3. User clicks "Forgot password?" -> enters email -> receives reset link -> resets password

### 7.3 States and Transitions
- **Empty state:** N/A for auth pages
- **Loading state:** Button disabled during form submission
- **Error state:** Validation errors displayed below inputs (Flux UI handles this)
- **Success state:** Redirect to dashboard

### 7.4 Flux UI Components Used
- `flux:input` for text/email/password fields
- `flux:button` for submit buttons
- `flux:checkbox` for remember me
- `flux:link` for navigation links

---

## 8. Testing Strategy

### 8.1 Unit Tests

| Test File | Test Cases |
|-----------|------------|
| `tests/Unit/Models/UserTest.php` | - isDriver() returns true for driver role |
|                                   | - isDriver() returns false for supervisor role |
|                                   | - isSupervisor() returns true for supervisor role |
|                                   | - isSupervisor() returns false for driver role |
|                                   | - role attribute is cast to UserRole enum |

### 8.2 Feature Tests

| Test File | Test Cases |
|-----------|------------|
| `tests/Feature/Auth/RegistrationTest.php` | - user can register with valid data |
|                                            | - registered user has driver role |
|                                            | - registration fails with invalid email |
|                                            | - registration fails with short password |
| `tests/Feature/Middleware/EnsureUserIsDriverTest.php` | - driver can access driver routes |
|                                                        | - supervisor cannot access driver routes |
|                                                        | - guest is redirected to login |
| `tests/Feature/Middleware/EnsureUserIsSupervisorTest.php` | - supervisor can access supervisor routes |
|                                                            | - driver cannot access supervisor routes |
|                                                            | - guest is redirected to login |
| `tests/Feature/DashboardTest.php` | - driver sees driver dashboard content |
|                                    | - supervisor sees supervisor dashboard content |
|                                    | - guest is redirected to login |

### 8.3 Critical Test Scenarios
1. **Happy Path:** User registers -> assigned driver role -> can access dashboard
2. **Edge Case:** Supervisor tries to register (should become driver, not supervisor)
3. **Error Case:** Invalid credentials on login shows error message

### 8.4 Test Data Requirements
- `UserFactory` with `driver()` and `supervisor()` states
- Seeded supervisor account for manual testing

---

## 9. Architecture Guidelines

### 9.1 Code Location
- Enums: `app/Enums/`
- Models: `app/Models/`
- Middleware: `app/Http/Middleware/`
- Actions: `app/Actions/Fortify/`
- Views: `resources/views/`
- Tests: `tests/Unit/` and `tests/Feature/`

### 9.2 Naming Conventions
- Enum: `UserRole` (PascalCase)
- Enum cases: `Driver`, `Supervisor` (PascalCase)
- Middleware: `EnsureUserIsDriver`, `EnsureUserIsSupervisor`
- Helper methods: `isDriver()`, `isSupervisor()` (camelCase)

### 9.3 Patterns to Follow
- Use PHP 8 enums for role values (not string constants)
- Use Fortify's `CreateNewUser` action for registration logic
- Use middleware for route-level authorization
- Use model helper methods for role checks in views/components

### 9.4 Code Quality Rules
- All methods must have explicit return type declarations
- Use PHP 8 constructor property promotion where applicable
- Run `vendor/bin/pint --dirty` before committing

---

## 10. Validation & Completion Checklist

### Per-Phase Completion:
- [ ] All phase requirements implemented (checkboxes in Section 5)
- [ ] Tests pass for phase
- [ ] Code style validated

### Final Completion:
- [ ] ALL phases completed (Phase 1-5)
- [ ] All tests pass (`php artisan test`)
- [ ] Code style validated (`vendor/bin/pint --dirty`)
- [ ] Manual testing completed:
  - [ ] Register new user -> driver role assigned
  - [ ] Login as driver -> see driver dashboard
  - [ ] Login as supervisor -> see supervisor dashboard
  - [ ] Password reset flow works

---

## 11. Notes & Decisions Log

| Decision | Rationale | Date |
|----------|-----------|------|
| Self-registration for drivers | Simple onboarding, no admin overhead | 2025-12-11 |
| Seeded supervisor account | Only way to create supervisors, secure | 2025-12-11 |
| Simple role column (not Spatie) | Only 2 fixed roles, no granular permissions needed | 2025-12-11 |
| Single `/dashboard` route | Cleaner than separate routes, content varies by role | 2025-12-11 |
| Custom middleware for routes | Clean route-level protection, easy to group routes | 2025-12-11 |
| Drivers scoped to own data | Privacy requirement, enforced in queries | 2025-12-11 |
| Login + Register pages only | Password reset uses Laravel defaults | 2025-12-11 |
| Fixed supervisor credentials | Dev simplicity: supervisor@zip.test / password | 2025-12-11 |

---

## 12. Open Questions / Future Considerations

- **Email verification:** Currently disabled. May enable in future for security.
- **Two-factor authentication:** Fortify configured but UI not exposed. Can enable later.
- **Multiple supervisors:** Current design supports it (just seed more), but no UI to manage.
- **Role changes:** No UI to change user roles. Would require admin panel in future.
