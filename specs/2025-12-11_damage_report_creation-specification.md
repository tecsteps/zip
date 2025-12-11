# Damage Report Creation - Technical Specification

**Date:** 2025-12-11
**Status:** Ready for Implementation
**Feature:** 3 of 6

---

## 1. Overview

### 1.1 Feature Summary
The Damage Report Creation feature allows drivers to submit damage reports for packages. Drivers upload a single photo of the damaged package, enter required information (Package/Shipment ID, Location/Address), and optionally add notes. Reports start as "Draft" and can be submitted for supervisor review.

### 1.2 Business Value
Enables drivers to quickly document package damage in the field, capturing essential information for insurance claims and quality tracking.

### 1.3 Target Users
Drivers (users with `role = 'driver'`) who need to report damaged packages during delivery operations.

---

## 2. Requirements

### 2.1 Functional Requirements
- Photo upload (single image, file upload - max 5MB, jpg/png/webp)
- Required fields: Package/Shipment ID, Location/Address
- Optional field: Description/Notes
- Auto-captured fields: Driver name (from auth), Date/Time (automatic)
- Report starts as "Draft" status
- Save as draft functionality
- Submit report functionality (changes status to "Submitted")
- Image preview before submission
- Validation with clear error messages
- Redirect to dashboard after successful creation

### 2.2 Non-Functional Requirements
- Mobile-responsive form layout
- Image compression/optimization on upload
- Form preserves data on validation failure
- Loading states during upload and submission

### 2.3 Out of Scope
- Camera integration (file upload only)
- Multiple photos per report
- Barcode/QR scanning
- AI assessment (Feature 4)
- Edit existing reports

---

## 3. Architecture

### 3.1 Component Overview

```
/driver/reports/create
┌─────────────────────────────────────────────────────────┐
│              x-layouts.app (existing)                    │
│  ┌───────────────────────────────────────────────────┐  │
│  │     App\Livewire\Driver\CreateReport              │  │
│  │  ┌─────────────────────────────────────────────┐  │  │
│  │  │  Photo Upload Zone                          │  │  │
│  │  │  [Drag & drop or click to upload]           │  │  │
│  │  │  [Image Preview]                            │  │  │
│  │  ├─────────────────────────────────────────────┤  │  │
│  │  │  Package ID *        [_______________]      │  │  │
│  │  │  Location *          [_______________]      │  │  │
│  │  │  Description         [_______________]      │  │  │
│  │  │                      [_______________]      │  │  │
│  │  ├─────────────────────────────────────────────┤  │  │
│  │  │  [Save Draft]              [Submit Report]  │  │  │
│  │  └─────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Data Flow
1. Driver clicks FAB or "Create Report" button on dashboard
2. Navigates to `/driver/reports/create`
3. Driver uploads photo - stored temporarily via Livewire
4. Driver fills in required fields
5. "Save Draft" creates report with status=Draft, redirects to dashboard
6. "Submit Report" creates report with status=Submitted, redirects to dashboard

### 3.3 Dependencies
- Existing: User model, DamageReport model, ReportStatus enum
- Existing: DamageReportService for data operations
- Existing: Flux UI components
- Required: Livewire WithFileUploads trait

---

## 4. Routes

| Method | Route | Component | Name |
|--------|-------|-----------|------|
| GET | /driver/reports/create | App\Livewire\Driver\CreateReport | driver.reports.create |

**Middleware:** auth, verified, driver role check

---

## 5. Implementation Steps

### 5.1 Phase 1: Routes and Component Setup

#### Requirements:
- [ ] **REQ-1.1:** Add route in `routes/web.php` for `/driver/reports/create`
- [ ] **REQ-1.2:** Create Livewire component `app/Livewire/Driver/CreateReport.php`
- [ ] **REQ-1.3:** Create blade view `resources/views/livewire/driver/create-report.blade.php`
- [ ] **REQ-1.4:** Add driver middleware/gate check to route
- [ ] **REQ-1.5:** Update FAB href in `resources/views/livewire/driver/dashboard.blade.php`
- [ ] **REQ-1.6:** Update empty state button href in `resources/views/livewire/driver/dashboard.blade.php`

---

### 5.2 Phase 2: Form Component Implementation

#### Requirements:
- [ ] **REQ-2.1:** Add `WithFileUploads` trait to component
- [ ] **REQ-2.2:** Add public properties: `$photo`, `$package_id`, `$location`, `$description`
- [ ] **REQ-2.3:** Add validation rules using `#[Validate]` attributes or `rules()` method
- [ ] **REQ-2.4:** Implement `saveDraft()` method - creates report with Draft status
- [ ] **REQ-2.5:** Implement `submit()` method - creates report with Submitted status
- [ ] **REQ-2.6:** Store uploaded photo to `damage-reports` disk/directory
- [ ] **REQ-2.7:** Redirect to dashboard with success message after save

#### Validation Rules:
```php
'photo' => ['required', 'image', 'max:5120'], // 5MB
'package_id' => ['required', 'string', 'max:255'],
'location' => ['required', 'string', 'max:255'],
'description' => ['nullable', 'string', 'max:1000'],
```

---

### 5.3 Phase 3: Form UI

#### Requirements:
- [ ] **REQ-3.1:** Create photo upload zone with drag-and-drop styling
- [ ] **REQ-3.2:** Show image preview using `$photo->temporaryUrl()`
- [ ] **REQ-3.3:** Add upload progress indicator
- [ ] **REQ-3.4:** Add Package ID input with `flux:input`
- [ ] **REQ-3.5:** Add Location input with `flux:input`
- [ ] **REQ-3.6:** Add Description textarea with `flux:textarea`
- [ ] **REQ-3.7:** Add "Save Draft" button (secondary variant)
- [ ] **REQ-3.8:** Add "Submit Report" button (primary variant)
- [ ] **REQ-3.9:** Show validation errors with `flux:field` error states
- [ ] **REQ-3.10:** Add loading states on buttons during submission
- [ ] **REQ-3.11:** Mobile-responsive layout with proper spacing

---

### 5.4 Phase 4: Service Layer Integration

#### Requirements:
- [ ] **REQ-4.1:** Add `create()` method to `DamageReportService`
- [ ] **REQ-4.2:** Add `createAndSubmit()` method to `DamageReportService`
- [ ] **REQ-4.3:** Use service methods in component instead of direct model calls
- [ ] **REQ-4.4:** Add `create` method to `DamageReportPolicy`

---

### 5.5 Phase 5: Testing

#### Requirements:
- [ ] **REQ-5.1:** Test driver can access create page
- [ ] **REQ-5.2:** Test non-driver cannot access create page
- [ ] **REQ-5.3:** Test validation errors display correctly
- [ ] **REQ-5.4:** Test photo upload works
- [ ] **REQ-5.5:** Test save as draft creates report with Draft status
- [ ] **REQ-5.6:** Test submit creates report with Submitted status
- [ ] **REQ-5.7:** Test redirect to dashboard after save
- [ ] **REQ-5.8:** Test photo is stored correctly

---

## 6. UI/UX Specification

### 6.1 Photo Upload Zone
- Dashed border container
- Icon and "Click to upload or drag and drop" text
- Accepted formats hint: "JPG, PNG, WebP (max 5MB)"
- After upload: Show image preview with remove button
- Upload progress bar during upload

### 6.2 Form Fields
- Package ID: Text input, required, placeholder "e.g., PKG-12345"
- Location: Text input, required, placeholder "e.g., 123 Main St, City"
- Description: Textarea, optional, placeholder "Describe the damage..."

### 6.3 Action Buttons
- "Save Draft" - Secondary button, left side
- "Submit Report" - Primary button, right side
- Both show loading spinners during submission

### 6.4 Flux UI Components
- `flux:heading` for page title
- `flux:input` for text fields
- `flux:textarea` for description
- `flux:button` for actions
- `flux:field` for field wrappers with labels/errors

---

## 7. File Storage

### 7.1 Configuration
- Disk: `public` (or dedicated `damage-reports` disk)
- Directory: `damage-reports/{user_id}/`
- Filename: `{uuid}.{extension}`

### 7.2 Storage Path
```php
$path = $this->photo->store(
    "damage-reports/{$userId}",
    'public'
);
```

---

## 8. Testing Strategy

### 8.1 Feature Tests
| Test File | Test Cases |
|-----------|------------|
| `tests/Feature/Livewire/Driver/CreateReportTest.php` | - driver can view create form |
| | - supervisor cannot view create form |
| | - guest is redirected to login |
| | - can save draft with valid data |
| | - can submit report with valid data |
| | - validation fails without photo |
| | - validation fails without package_id |
| | - validation fails without location |
| | - photo must be image |
| | - photo max size is 5MB |
| | - redirects to dashboard after save |

---

## 9. Success Criteria

- [ ] Driver can navigate to create form via FAB
- [ ] Driver can upload a photo and see preview
- [ ] Driver can fill required fields and save as draft
- [ ] Driver can submit report directly
- [ ] Validation errors display correctly
- [ ] Photo is stored and linked to report
- [ ] All tests pass
- [ ] Code passes pint formatting
