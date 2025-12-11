# Driver Dashboard - Technical Specification

**Date:** 2025-12-11
**Status:** Ready for Implementation
**Q&A Reference:** specs/2025-12-11_driver_dashboard-questions.md

---

## 1. Overview

### 1.1 Feature Summary
The Driver Dashboard is the primary interface for drivers to manage their damage reports. It displays a card-style list of the driver's own reports with status indicators, photo thumbnails, and key information. Drivers can create new reports via a floating action button (FAB) and manage their drafts.

### 1.2 Business Value
Provides drivers with a centralized view of all their damage reports, enabling them to track submission status and quickly create new reports when encountering damaged packages.

### 1.3 Target Users
Drivers (users with `role = 'driver'`) who need to submit and track damage reports for courier operations.

---

## 2. Requirements

### 2.1 Functional Requirements Summary
The following requirements are implemented in Section 5 (Implementation Steps) with specific file paths:
- Display driver's own damage reports, ordered by newest first
- Each report card shows: Package ID, Date, Status badge, Location, Photo thumbnail, AI severity
- Status badges: Draft (gray), Submitted (yellow), Approved (green)
- Empty state with "Create Report" button
- Floating action button (FAB) in bottom-right corner
- Draft reports show Edit, Submit, Delete actions
- Submitted/Approved reports are view-only
- Dashboard accessible only to authenticated drivers

### 2.2 Non-Functional Requirements Summary
- Mobile-responsive layout
- No pagination (all reports loaded)
- Optimized photo thumbnails

### 2.3 Out of Scope
- Filtering or sorting options
- Pagination or infinite scroll
- Share/download report URL functionality
- Request edit workflow for submitted reports

---

## 3. Architecture

### 3.1 Component Overview

```
┌─────────────────────────────────────────────────────────┐
│                    /dashboard                            │
│  ┌───────────────────────────────────────────────────┐  │
│  │              x-layouts.app (existing)              │  │
│  │  ┌─────────────────────────────────────────────┐  │  │
│  │  │     livewire/driver/dashboard (Volt)        │  │  │
│  │  │  ┌───────────────────────────────────────┐  │  │  │
│  │  │  │  Report Card 1                        │  │  │  │
│  │  │  │  Report Card 2                        │  │  │  │
│  │  │  │  ...                                  │  │  │  │
│  │  │  └───────────────────────────────────────┘  │  │  │
│  │  │  ┌─────┐                                    │  │  │
│  │  │  │ FAB │  (bottom-right)                   │  │  │
│  │  │  └─────┘                                    │  │  │
│  │  └─────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Data Flow
1. Driver logs in and is redirected to `/dashboard`
2. Dashboard checks user role - if driver, loads `driver.dashboard` Volt component
3. Component queries `DamageReport::where('user_id', auth()->id())->latest()->get()`
4. Reports rendered as card list with status badges and thumbnails
5. Click on card navigates to report detail (future feature)
6. FAB click navigates to report creation (future feature)

### 3.3 Dependencies
- Existing: User model with `isDriver()` method, UserRole enum
- Existing: Flux UI components (badge, button, heading, text)
- Required: DamageReport model (created in Feature 3, but model stub needed here)

---

## 4. Database Schema

### 4.1 New Tables

```sql
-- Note: Full DamageReport table created in Feature 3 (Damage Report Creation)
-- This feature requires a minimal stub for display purposes

CREATE TABLE damage_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    package_id VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    description TEXT NULL,
    photo_path VARCHAR(255) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'draft',
    ai_severity VARCHAR(50) NULL,
    ai_damage_type VARCHAR(255) NULL,
    ai_value_impact VARCHAR(255) NULL,
    ai_liability VARCHAR(255) NULL,
    submitted_at DATETIME NULL,
    approved_at DATETIME NULL,
    approved_by INTEGER NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### 4.2 Table Modifications
None - users table already has role column.

### 4.3 Relationships
- `User` hasMany `DamageReport` (as driver)
- `User` hasMany `DamageReport` (as approver, via approved_by)
- `DamageReport` belongsTo `User` (driver)
- `DamageReport` belongsTo `User` (approver)

### 4.4 Indexes
- `damage_reports_user_id_index` on `user_id`
- `damage_reports_status_index` on `status`

---

## 5. Implementation Steps

### 5.1 Phase 1: Database & Model Setup
**Iteration scope:** Create DamageReport model, migration, factory, and enum for status

#### Requirements:
- [ ] **REQ-1.1:** Create enum `app/Enums/ReportStatus.php` with cases: Draft, Submitted, Approved
- [ ] **REQ-1.2:** Create migration `database/migrations/YYYY_MM_DD_create_damage_reports_table.php` with all columns from Section 4.1 schema
- [ ] **REQ-1.3:** Create model `app/Models/DamageReport.php` with fillable attributes and status enum cast
- [ ] **REQ-1.4:** Add `user()` belongsTo relationship in `app/Models/DamageReport.php`
- [ ] **REQ-1.5:** Add `approver()` belongsTo relationship in `app/Models/DamageReport.php`
- [ ] **REQ-1.6:** Add `scopeForDriver()` query scope in `app/Models/DamageReport.php`
- [ ] **REQ-1.7:** Create factory `database/factories/DamageReportFactory.php` with definition method
- [ ] **REQ-1.8:** Add factory states (draft, submitted, approved, withAiAssessment) in `database/factories/DamageReportFactory.php`
- [ ] **REQ-1.9:** Add `damageReports()` hasMany relationship in `app/Models/User.php`
- [ ] **REQ-1.10:** Run migration: `php artisan migrate`
- [ ] **REQ-1.11:** Write test `tests/Feature/Models/DamageReportTest.php` covering model relationships and scopes

#### Implementation Notes:
```php
// app/Enums/ReportStatus.php
enum ReportStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
}
```

---

### 5.2 Phase 2: Driver Dashboard Volt Component
**Iteration scope:** Create the Volt component that displays the driver's reports

#### Requirements:
- [ ] **REQ-2.1:** Create Volt component `resources/views/livewire/driver/dashboard.blade.php` using functional API
- [ ] **REQ-2.2:** Component must query driver's reports: `DamageReport::where('user_id', auth()->id())->latest()->get()`
- [ ] **REQ-2.3:** Component must include computed property for checking if reports exist
- [ ] **REQ-2.4:** Update `resources/views/dashboard.blade.php` to embed the Volt component for drivers
- [ ] **REQ-2.5:** Create test file `tests/Feature/Livewire/Driver/DashboardTest.php`
- [ ] **REQ-2.6:** Write test for driver seeing only own reports in `tests/Feature/Livewire/Driver/DashboardTest.php`
- [ ] **REQ-2.7:** Write test for reports ordered newest first in `tests/Feature/Livewire/Driver/DashboardTest.php`

#### Implementation Notes:
```php
// Volt functional component pattern
<?php
use App\Models\DamageReport;
use function Livewire\Volt\{computed};

$reports = computed(fn () => DamageReport::where('user_id', auth()->id())->latest()->get());
$hasReports = computed(fn () => $this->reports->isNotEmpty());
?>
```

---

### 5.3 Phase 3: Report Card UI
**Iteration scope:** Build the card UI for displaying individual reports

#### Requirements:
- [ ] **REQ-3.1:** Create report card partial `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.1a:** Add photo thumbnail (80x80px, placeholder if no photo) in `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.1b:** Add package ID as primary text in `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.1c:** Add location as secondary text in `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.1d:** Add formatted date (e.g., "Dec 11, 2025") in `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.1e:** Add status badge with color coding in `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.1f:** Add AI severity badge (if available) in `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.2:** Make card clickable with link wrapper in `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.3:** Add Edit, Submit, Delete buttons for Draft status in `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.4:** Hide action buttons for Submitted/Approved status in `resources/views/livewire/driver/partials/report-card.blade.php`
- [ ] **REQ-3.5:** Add `delete($reportId)` action in `resources/views/livewire/driver/dashboard.blade.php`
- [ ] **REQ-3.6:** Add `submit($reportId)` action in `resources/views/livewire/driver/dashboard.blade.php`
- [ ] **REQ-3.7:** Add delete confirmation modal using `flux:modal` in `resources/views/livewire/driver/dashboard.blade.php`
- [ ] **REQ-3.8:** Write test for draft action buttons visible in `tests/Feature/Livewire/Driver/DashboardTest.php`
- [ ] **REQ-3.9:** Write test for submitted/approved reports hiding buttons in `tests/Feature/Livewire/Driver/DashboardTest.php`
- [ ] **REQ-3.10:** Write test for delete action in `tests/Feature/Livewire/Driver/DashboardTest.php`
- [ ] **REQ-3.11:** Write test for submit action in `tests/Feature/Livewire/Driver/DashboardTest.php`

#### Implementation Notes:
```blade
{{-- Status badge colors --}}
@php
$badgeVariant = match($report->status) {
    \App\Enums\ReportStatus::Draft => 'default',
    \App\Enums\ReportStatus::Submitted => 'warning',
    \App\Enums\ReportStatus::Approved => 'success',
};
@endphp
<flux:badge :variant="$badgeVariant">{{ $report->status->value }}</flux:badge>
```

---

### 5.4 Phase 4: Empty State & FAB
**Iteration scope:** Implement empty state UI and floating action button

#### Requirements:
- [ ] **REQ-4.1:** Add empty state UI with icon and message in `resources/views/livewire/driver/dashboard.blade.php`
- [ ] **REQ-4.2:** Add "Create Report" button in empty state in `resources/views/livewire/driver/dashboard.blade.php`
- [ ] **REQ-4.3:** Create FAB component `resources/views/components/fab.blade.php` with fixed bottom-right position
- [ ] **REQ-4.4:** Include FAB component in `resources/views/livewire/driver/dashboard.blade.php`
- [ ] **REQ-4.5:** Style FAB for mobile responsiveness in `resources/views/components/fab.blade.php`
- [ ] **REQ-4.6:** Write test for empty state display in `tests/Feature/Livewire/Driver/DashboardTest.php`
- [ ] **REQ-4.7:** Write test for FAB presence in `tests/Feature/Livewire/Driver/DashboardTest.php`

#### Implementation Notes:
```blade
{{-- FAB component --}}
<a href="{{ route('reports.create') }}"
   class="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-zinc-900 text-white shadow-lg hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
    <flux:icon name="plus" class="size-6" />
</a>
```

---

### 5.5 Phase 5: Integration & Polish
**Iteration scope:** Final integration, authorization, and comprehensive testing

#### Requirements:
- [ ] **REQ-5.1:** Add driver role check in `resources/views/dashboard.blade.php` to show driver dashboard content
- [ ] **REQ-5.2:** Verify supervisor sees supervisor content in `resources/views/dashboard.blade.php`
- [ ] **REQ-5.3:** Create policy `app/Policies/DamageReportPolicy.php` with view method
- [ ] **REQ-5.4:** Add update method to `app/Policies/DamageReportPolicy.php` (owner + draft status)
- [ ] **REQ-5.5:** Add delete method to `app/Policies/DamageReportPolicy.php` (owner + draft status)
- [ ] **REQ-5.6:** Add submit method to `app/Policies/DamageReportPolicy.php` (owner + draft status)
- [ ] **REQ-5.7:** Register policy in `app/Providers/AppServiceProvider.php`
- [ ] **REQ-5.8:** Apply policy checks in `resources/views/livewire/driver/dashboard.blade.php` actions
- [ ] **REQ-5.9:** Write policy tests in `tests/Feature/Policies/DamageReportPolicyTest.php`
- [ ] **REQ-5.10:** Run all tests: `php artisan test --filter=DamageReport`
- [ ] **REQ-5.11:** Run code style check: `vendor/bin/pint --dirty`

---

## 6. API / Interface Design

### 6.1 Routes
| Method | Route | Controller/Action | Description |
|--------|-------|-------------------|-------------|
| GET | /dashboard | (view) | Shows driver or supervisor dashboard based on role (exists) |
| GET | /reports/create | (future) | Create new report form - implemented in Feature 3 |
| GET | /reports/{report} | (future) | View report detail - implemented in Feature 6 |

**Note:** FAB and card links will use `#` as placeholder hrefs until Feature 3 and Feature 6 are implemented.

### 6.2 Livewire Components
| Component | Purpose | Key Properties | Key Methods |
|-----------|---------|----------------|-------------|
| driver.dashboard | Display driver's reports | $reports (computed), $hasReports (computed) | delete($id), submit($id) |

### 6.3 Actions/Services
| Class | Purpose | Input | Output |
|-------|---------|-------|--------|
| DamageReportPolicy | Authorization | User, DamageReport | bool |

---

## 7. UI/UX Specification

### 7.1 Page/Component Layout

```
┌─────────────────────────────────────────────────────────┐
│  Header (existing - logo, user menu, logout)            │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Welcome, [Driver Name]!                                │
│  Role: driver                                           │
│                                                         │
│  My Reports                                             │
│  ┌─────────────────────────────────────────────────┐   │
│  │ ┌──────┐  Package: PKG-12345      [Draft]      │   │
│  │ │ IMG  │  123 Main St, City                     │   │
│  │ │      │  Dec 11, 2025                          │   │
│  │ └──────┘  [Edit] [Submit] [Delete]              │   │
│  └─────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────┐   │
│  │ ┌──────┐  Package: PKG-12346      [Submitted]  │   │
│  │ │ IMG  │  456 Oak Ave, Town       [Moderate]   │   │
│  │ │      │  Dec 10, 2025                          │   │
│  │ └──────┘                                        │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
│                                            ┌─────┐     │
│                                            │  +  │     │
│                                            └─────┘     │
└─────────────────────────────────────────────────────────┘
```

### 7.2 User Interactions
1. Driver logs in -> Redirected to /dashboard
2. Driver sees list of their reports with status badges
3. Driver clicks report card -> Navigates to report detail (future)
4. Driver clicks Edit on draft -> Navigates to edit form (future)
5. Driver clicks Submit on draft -> Status changes to Submitted, buttons disappear
6. Driver clicks Delete on draft -> Confirmation modal, then report removed from list
7. Driver clicks FAB (+) -> Navigates to create report form (future)

### 7.3 States and Transitions
- **Empty state:** No reports - show message "No damage reports yet" with icon and create button
- **Loading state:** Skeleton cards while data loads (Livewire handles automatically)
- **Error state:** Toast notification for failed actions (delete/submit)
- **Success state:** Toast notification for successful actions, list updates reactively

### 7.4 Flux UI Components to Use
- `flux:heading` for page title
- `flux:text` for secondary text
- `flux:badge` for status indicators (variant: default/warning/success)
- `flux:button` for actions (Edit/Submit/Delete/Create)
- `flux:icon` for FAB plus icon and empty state icon
- `flux:modal` for delete confirmation

---

## 8. Testing Strategy

### 8.1 Feature Tests
| Test File | Test Cases |
|-----------|------------|
| `tests/Feature/Models/DamageReportTest.php` | - belongs to user |
|                                               | - has approver relationship |
|                                               | - forDriver scope filters correctly |
|                                               | - status casts to enum |
| `tests/Feature/Livewire/Driver/DashboardTest.php` | - driver sees only own reports |
|                                                     | - reports ordered newest first |
|                                                     | - empty state when no reports |
|                                                     | - draft shows action buttons |
|                                                     | - submitted hides action buttons |
|                                                     | - can delete draft report |
|                                                     | - can submit draft report |
|                                                     | - FAB is visible |
| `tests/Feature/Policies/DamageReportPolicyTest.php` | - owner can view own report |
|                                                       | - owner can update draft |
|                                                       | - owner cannot update submitted |
|                                                       | - owner can delete draft |
|                                                       | - other driver cannot view |

### 8.3 Critical Test Scenarios
1. **Happy Path:** Driver logs in, sees reports, submits a draft, status changes
2. **Edge Case:** Driver with zero reports sees empty state with create button
3. **Error Case:** Attempting to delete non-draft report fails with policy denial

### 8.4 Test Data Requirements
- DamageReportFactory with states: draft, submitted, approved, withAiAssessment
- UserFactory already exists with driver state

---

## 9. Architecture Guidelines

### 9.1 Code Location
- Models: `app/Models/DamageReport.php`
- Enums: `app/Enums/ReportStatus.php`
- Policies: `app/Policies/DamageReportPolicy.php`
- Livewire/Volt: `resources/views/livewire/driver/dashboard.blade.php`
- Partials: `resources/views/livewire/driver/partials/report-card.blade.php`
- Components: `resources/views/components/fab.blade.php`
- Factories: `database/factories/DamageReportFactory.php`

### 9.2 Naming Conventions
- Model: `DamageReport` (singular)
- Table: `damage_reports` (plural snake_case)
- Enum: `ReportStatus` (PascalCase)
- Volt component: `driver.dashboard` (dot notation)
- Policy: `DamageReportPolicy`

### 9.3 Patterns to Follow
- Use Volt functional API (consistent with project)
- Use computed properties for reactive data
- Use Flux UI components for all UI elements
- Use policies for authorization (not inline checks)
- Use factories with states for test data

### 9.4 Code Quality Rules
- Run `vendor/bin/pint --dirty` before committing
- All new code must have test coverage
- Use explicit return types on all methods
- Use enum casts for status fields

---

## 10. Validation & Completion Checklist

### Per-Phase Completion:
- [ ] Phase 1: Database & Model Setup (REQ-1.1 through REQ-1.11)
- [ ] Phase 2: Driver Dashboard Volt Component (REQ-2.1 through REQ-2.7)
- [ ] Phase 3: Report Card UI (REQ-3.1 through REQ-3.11)
- [ ] Phase 4: Empty State & FAB (REQ-4.1 through REQ-4.7)
- [ ] Phase 5: Integration & Polish (REQ-5.1 through REQ-5.11)

### Final Completion:
- [ ] All phase requirements checked off in Section 5
- [ ] All tests pass (`php artisan test`)
- [ ] Code style validated (`vendor/bin/pint --dirty`)
- [ ] Manual testing via browser completed

---

## 11. Notes & Decisions Log

| Decision | Rationale | Date |
|----------|-----------|------|
| Simple card-style list | Mobile-friendly, easy to scan | 2025-12-11 |
| Show all report info on card | User requested Package ID, Date, Status, Location, Photo, AI severity | 2025-12-11 |
| No filtering/pagination | MVP simplicity - drivers typically have few reports | 2025-12-11 |
| FAB for create button | Thumb-friendly mobile pattern | 2025-12-11 |
| View-only for Submitted/Approved | No edit workflow for submitted reports in scope | 2025-12-11 |
| /dashboard route | Standard convention, already exists | 2025-12-11 |

---

## 12. Open Questions / Future Considerations

- Report detail view (Feature 6) will be linked from card clicks
- Report creation (Feature 3) will be linked from FAB
- Consider adding filtering when drivers have many reports
- Consider lazy loading images for performance
- Share report URL could be added later
