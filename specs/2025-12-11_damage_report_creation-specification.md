# Damage Report Creation + AI Assessment - Technical Specification

**Date:** 2025-12-11
**Status:** Ready for Implementation
**Q&A Reference:** specs/2025-12-11_damage_report_creation-questions.md
**Features:** 3 + 4 (Combined)

---

## 1. Overview

### 1.1 Feature Summary
This specification combines Features 3 (Damage Report Creation) and 4 (AI Damage Assessment) into a single implementation. Drivers create damage reports by uploading a photo and entering package details. Upon submission, an asynchronous queue job sends the photo to OpenRouter for AI analysis, which assesses severity, damage type, value impact, and liability.

### 1.2 Business Value
Enables drivers to quickly document package damage in the field while AI automatically provides consistent, professional damage assessments - reducing manual review time and ensuring standardized reporting.

### 1.3 Target Users
- **Drivers:** Create and submit damage reports with photos
- **Supervisors:** Review reports with AI-generated assessments (Feature 5)

---

## 2. Requirements

### 2.1 Functional Requirements
- [ ] FR-1: Drivers can create new damage reports with photo upload
- [ ] FR-2: Photo upload accepts jpg/png/webp, max 5MB
- [ ] FR-3: Required fields: Package ID, Location
- [ ] FR-4: Optional field: Description/Notes
- [ ] FR-5: Auto-captured: Driver name (from auth), Date/Time (automatic)
- [ ] FR-6: Drivers can save reports as Draft
- [ ] FR-7: Drivers can submit reports (status changes to Submitted)
- [ ] FR-8: Drivers can edit their own Draft reports
- [ ] FR-9: On submission, AI analysis job is dispatched to queue
- [ ] FR-10: AI job calls OpenRouter API with damage photo
- [ ] FR-11: AI job populates severity, damage_type, value_impact, liability fields
- [ ] FR-12: AI job retries 3 times with exponential backoff on failure
- [ ] FR-13: Dashboard shows "Pending" badge for reports awaiting AI
- [ ] FR-14: Dashboard uses smart polling (3s) when pending reports exist
- [ ] FR-15: FAB and empty state buttons link to create report page

### 2.2 Non-Functional Requirements
- [ ] NFR-1: Form must be mobile-responsive
- [ ] NFR-2: Photo upload shows preview before submission
- [ ] NFR-3: Loading states on buttons during save/submit
- [ ] NFR-4: OpenRouter model configurable via environment variable
- [ ] NFR-5: API key stored securely in environment

### 2.3 Out of Scope
- Camera integration (file upload only)
- Multiple photos per report
- Barcode/QR scanning
- Drag-and-drop upload (simple file input only)
- Address autocomplete
- Real-time WebSocket updates (using polling instead)

---

## 3. Architecture

### 3.1 Component Overview

```
/driver/reports/create          /driver/reports/{id}/edit
         │                                │
         ▼                                ▼
┌─────────────────────────────────────────────────────┐
│              CreateReport / EditReport               │
│                 (Livewire Component)                 │
│  ┌─────────────────────────────────────────────┐    │
│  │  Photo Upload (file input + preview)        │    │
│  │  Package ID * [_______________]             │    │
│  │  Location *   [_______________]             │    │
│  │  Description  [_______________]             │    │
│  │  [Save Draft]        [Submit Report]        │    │
│  └─────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────┘
         │                         │
         │ Save Draft              │ Submit
         ▼                         ▼
┌─────────────────┐      ┌─────────────────────────┐
│ DamageReport    │      │ DamageReport            │
│ status: Draft   │      │ status: Submitted       │
└─────────────────┘      │ + dispatch job          │
                         └───────────┬─────────────┘
                                     │
                                     ▼
                         ┌─────────────────────────┐
                         │ AnalyzeDamageReportJob  │
                         │ (Queue, 3 retries)      │
                         └───────────┬─────────────┘
                                     │
                                     ▼
                         ┌─────────────────────────┐
                         │ OpenRouterService       │
                         │ POST /api/v1/chat       │
                         └───────────┬─────────────┘
                                     │
                                     ▼
                         ┌─────────────────────────┐
                         │ DamageReport updated    │
                         │ ai_severity, etc.       │
                         └─────────────────────────┘
```

### 3.2 Data Flow

**Create/Submit Flow:**
1. Driver navigates to `/driver/reports/create`
2. Driver uploads photo (stored temporarily via Livewire)
3. Driver fills Package ID, Location, optional Description
4. Driver clicks "Save Draft" or "Submit Report"
5. Photo moved to permanent storage: `damage-reports/{user_id}/{uuid}.{ext}`
6. DamageReport created with appropriate status
7. If submitted: `AnalyzeDamageReportJob` dispatched to queue
8. Driver redirected to dashboard with success message

**AI Analysis Flow:**
1. Job retrieves report and photo from storage
2. Job calls OpenRouter API with image and prompt
3. API returns structured JSON with assessment
4. Job updates report with AI fields
5. Dashboard polls and displays updated results

### 3.3 Dependencies
- Existing: User model, DamageReport model, ReportStatus enum
- Existing: Flux UI components
- Required: Livewire WithFileUploads trait
- Required: OpenRouter API key in environment
- Required: Queue worker running (database or redis driver)

---

## 4. Database Schema

### 4.1 Existing Tables (No Changes)
The `damage_reports` table already exists from Feature 2 with all required columns:
- `id`, `user_id`, `package_id`, `location`, `description`
- `photo_path`, `status`
- `ai_severity`, `ai_damage_type`, `ai_value_impact`, `ai_liability`
- `submitted_at`, `approved_at`, `approved_by`
- `created_at`, `updated_at`

### 4.2 Configuration
Add to `.env`:
```
OPENROUTER_API_KEY=your-api-key-here
OPENROUTER_MODEL=openai/gpt-5-mini
```

Add to `config/services.php`:
```php
'openrouter' => [
    'api_key' => env('OPENROUTER_API_KEY'),
    'model' => env('OPENROUTER_MODEL', 'openai/gpt-5-mini'),
    'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
],
```

---

## 5. Implementation Steps

### 5.1 Phase 1: Routes and Basic Component
**Iteration scope:** Set up routes, create Livewire component shell, link from dashboard

#### Requirements:
- [ ] **REQ-1.1:** Add route `GET /driver/reports/create` in `routes/web.php` with name `driver.reports.create`, middleware `['auth', 'driver']`
- [ ] **REQ-1.2:** Add route `GET /driver/reports/{report}/edit` in `routes/web.php` with name `driver.reports.edit`, middleware `['auth', 'driver']`
- [ ] **REQ-1.3:** Create Livewire component `app/Livewire/Driver/CreateReport.php`
- [ ] **REQ-1.4:** Create blade view `resources/views/livewire/driver/create-report.blade.php` with basic form structure
- [ ] **REQ-1.5:** Create Livewire component `app/Livewire/Driver/EditReport.php`
- [ ] **REQ-1.6:** Create blade view `resources/views/livewire/driver/edit-report.blade.php`
- [ ] **REQ-1.7:** Update FAB href in `resources/views/components/fab.blade.php` to use `route('driver.reports.create')`
- [ ] **REQ-1.8:** Update empty state button in `resources/views/livewire/driver/dashboard.blade.php` to use `route('driver.reports.create')`
- [ ] **REQ-1.9:** Update Edit button in `resources/views/livewire/driver/partials/report-card.blade.php` to use `route('driver.reports.edit', $report)`
- [ ] **REQ-1.10:** Write test `tests/Feature/Livewire/Driver/CreateReportTest.php` - driver can access create page
- [ ] **REQ-1.11:** Write test `tests/Feature/Livewire/Driver/CreateReportTest.php` - supervisor cannot access create page
- [ ] **REQ-1.12:** Write test `tests/Feature/Livewire/Driver/CreateReportTest.php` - guest redirected to login

---

### 5.2 Phase 2: Create Report Form Implementation
**Iteration scope:** Implement form with validation, photo upload, save draft functionality

#### Requirements:
- [ ] **REQ-2.1:** Add `WithFileUploads` trait to `app/Livewire/Driver/CreateReport.php`
- [ ] **REQ-2.2:** Add public properties in `app/Livewire/Driver/CreateReport.php`: `$photo`, `$package_id`, `$location`, `$description`
- [ ] **REQ-2.3:** Add validation rules in `app/Livewire/Driver/CreateReport.php`:
  - `photo`: required, image, max:5120, mimes:jpg,jpeg,png,webp
  - `package_id`: required, string, max:255
  - `location`: required, string, max:255
  - `description`: nullable, string, max:1000
- [ ] **REQ-2.4:** Implement `saveDraft()` method in `app/Livewire/Driver/CreateReport.php`:
  - Validate all fields
  - Store photo to `damage-reports/{user_id}/{uuid}.{ext}` with UUID filename
  - Create DamageReport with status Draft
  - Redirect to dashboard with success message
- [ ] **REQ-2.5:** Add `create` method to `app/Policies/DamageReportPolicy.php` - only drivers can create
- [ ] **REQ-2.6:** Register `create` policy check in component
- [ ] **REQ-2.7:** Build form UI in `resources/views/livewire/driver/create-report.blade.php`:
  - Page heading "Create Damage Report"
  - File input for photo with accepted types
  - Photo preview using `$photo->temporaryUrl()` when photo exists
  - flux:input for Package ID with placeholder
  - flux:input for Location with placeholder
  - flux:textarea for Description with placeholder
  - Validation error display using flux:field error states for each field
  - "Save Draft" button (secondary)
  - Loading state on button
- [ ] **REQ-2.8:** Write test - can save draft with valid data
- [ ] **REQ-2.9:** Write test - draft has status Draft
- [ ] **REQ-2.10:** Write test - photo is stored correctly
- [ ] **REQ-2.11:** Write test - validation fails without photo
- [ ] **REQ-2.12:** Write test - validation fails without package_id
- [ ] **REQ-2.13:** Write test - validation fails without location
- [ ] **REQ-2.14:** Write test - redirects to dashboard after save

---

### 5.3 Phase 3: Submit Report & Queue Job Setup
**Iteration scope:** Add submit functionality, create queue job shell, dispatch on submit

#### Requirements:
- [ ] **REQ-3.1:** Implement `submit()` method in `app/Livewire/Driver/CreateReport.php`:
  - Validate all fields
  - Store photo
  - Create DamageReport with status Submitted
  - Set `submitted_at` timestamp
  - Dispatch `AnalyzeDamageReportJob`
  - Redirect to dashboard with success message
- [ ] **REQ-3.2:** Add "Submit Report" button (primary) to form view
- [ ] **REQ-3.3:** Create job `app/Jobs/AnalyzeDamageReportJob.php`:
  - Implements ShouldQueue
  - Constructor accepts DamageReport
  - `$tries = 3`
  - `$backoff = [10, 30, 60]` (exponential backoff)
  - Empty `handle()` method for now
- [ ] **REQ-3.4:** Write test - submit creates report with Submitted status
- [ ] **REQ-3.5:** Write test - submit sets submitted_at timestamp
- [ ] **REQ-3.6:** Write test - submit dispatches AnalyzeDamageReportJob
- [ ] **REQ-3.7:** Write test - newly submitted report has NULL ai_severity fields

---

### 5.4 Phase 4: OpenRouter Service & AI Integration
**Iteration scope:** Create OpenRouter service, implement AI analysis in job

#### Requirements:
- [ ] **REQ-4.1:** Add OpenRouter config to `config/services.php`
- [ ] **REQ-4.1a:** Create exception `app/Exceptions/OpenRouterException.php`
- [ ] **REQ-4.2:** Create service `app/Services/OpenRouterService.php`:
  - Constructor with Http client, inject config values
  - `analyzeDamagePhoto(string $imagePath): array` method
  - Reads image from storage, converts to base64 with data URI prefix
  - Sends POST to `{base_url}/chat/completions` with headers:
    - `Authorization: Bearer {api_key}`
    - `Content-Type: application/json`
  - Request body: model, messages array with image_url content type
  - Parses JSON response, extracts content from choices[0].message.content
  - Throws `OpenRouterException` on API errors or invalid response
  - Returns array with severity, damage_type, value_impact, liability
- [ ] **REQ-4.3:** Create AI prompt in `app/Services/OpenRouterService.php`:
  ```
  Analyze this image of a damaged package. Provide a JSON assessment with:
  - severity: "minor", "moderate", or "severe"
  - damage_type: type of damage (e.g., "crushed", "wet", "torn", "punctured")
  - value_impact: "low", "medium", "high", or "total_loss"
  - liability: "carrier", "sender", "recipient", or "unknown"

  Respond ONLY with valid JSON, no other text.
  ```
- [ ] **REQ-4.4:** Implement `handle()` in `app/Jobs/AnalyzeDamageReportJob.php`:
  - Get photo path from report
  - Call OpenRouterService->analyzeDamagePhoto()
  - Update report with AI fields
  - Handle exceptions gracefully (let job retry)
- [ ] **REQ-4.5:** Write test for OpenRouterService with mocked HTTP responses
- [ ] **REQ-4.6:** Write test - job updates report with AI fields on success
- [ ] **REQ-4.7:** Write test - job retries on API failure
- [ ] **REQ-4.8:** Write test - after max retries, report status remains Submitted and AI fields remain NULL

---

### 5.5 Phase 5: Edit Report Functionality
**Iteration scope:** Implement edit form for draft reports

#### Requirements:
- [ ] **REQ-5.1:** Add `update` method to `app/Policies/DamageReportPolicy.php` - owner can update only Draft reports
- [ ] **REQ-5.2:** Implement `mount(DamageReport $report)` in `app/Livewire/Driver/EditReport.php`:
  - Authorize using policy (update permission)
  - Abort 403 if report status is not Draft
  - Populate form fields from report
  - Store existing photo path
- [ ] **REQ-5.3:** Add public properties: `$report`, `$photo`, `$existingPhotoPath`, `$package_id`, `$location`, `$description`
- [ ] **REQ-5.4:** Implement `save()` method - updates draft report, handles photo replacement
- [ ] **REQ-5.5:** Implement `submit()` method - updates and submits report
- [ ] **REQ-5.6:** Build edit form UI in `resources/views/livewire/driver/edit-report.blade.php`:
  - Similar to create form
  - Show existing photo if no new upload
  - Pre-fill all fields
- [ ] **REQ-5.7:** Write test - driver can edit own draft
- [ ] **REQ-5.8:** Write test - driver cannot edit submitted report
- [ ] **REQ-5.9:** Write test - driver cannot edit other driver's report
- [ ] **REQ-5.10:** Write test - can update and submit draft
- [ ] **REQ-5.11:** Write test - can replace photo when editing draft

---

### 5.6 Phase 6: Dashboard Updates (Polling & Pending Badge)
**Iteration scope:** Add smart polling, pending badge, update severity display

#### Requirements:
- [ ] **REQ-6.1:** Add `hasPendingReports` computed property to `app/Livewire/Driver/Dashboard.php`:
  - Returns true if any report has status=Submitted and ai_severity=null
- [ ] **REQ-6.2:** Add `wire:poll.3s` conditionally in `resources/views/livewire/driver/dashboard.blade.php` when `$this->hasPendingReports`
- [ ] **REQ-6.3:** Update severity badge logic in `resources/views/livewire/driver/partials/report-card.blade.php`:
  - If status=Submitted and ai_severity=null: show amber "Analyzing..." badge
  - If ai_severity exists: show severity badge with appropriate color
  - If status=Draft: don't show severity badge
- [ ] **REQ-6.4:** Add severity color mapping:
  - minor: green
  - moderate: amber
  - severe: red
- [ ] **REQ-6.5:** Write test - pending reports show "Analyzing..." badge
- [ ] **REQ-6.6:** Write test - completed reports show severity badge
- [ ] **REQ-6.7:** Write test - draft reports do not show severity badge
- [ ] **REQ-6.8:** Write test - hasPendingReports returns correct value

---

### 5.7 Phase 7: Integration & Final Testing
**Iteration scope:** End-to-end testing, code cleanup, manual verification

#### Requirements:
- [ ] **REQ-7.1:** All tests pass (`php artisan test`)
- [ ] **REQ-7.2:** Code style validated (`vendor/bin/pint --dirty`)
- [ ] **REQ-7.3:** Manual test: Create draft, verify saved correctly
- [ ] **REQ-7.4:** Manual test: Submit report, verify job dispatched
- [ ] **REQ-7.5:** Manual test: Run queue worker, verify AI fields populated
- [ ] **REQ-7.6:** Manual test: Dashboard shows "Analyzing..." then updates to severity
- [ ] **REQ-7.7:** Manual test: Edit draft, make changes, submit
- [ ] **REQ-7.8:** Verify photo storage and retrieval works correctly

---

## 6. API / Interface Design

### 6.1 Routes
| Method | Route | Component | Name | Middleware |
|--------|-------|-----------|------|------------|
| GET | /driver/reports/create | CreateReport | driver.reports.create | auth, driver |
| GET | /driver/reports/{report}/edit | EditReport | driver.reports.edit | auth, driver |

### 6.2 Livewire Components
| Component | Purpose | Key Properties | Key Methods |
|-----------|---------|----------------|-------------|
| Driver\CreateReport | Create new damage report | $photo, $package_id, $location, $description | saveDraft(), submit() |
| Driver\EditReport | Edit draft report | $report, $photo, $existingPhotoPath, $package_id, $location, $description | save(), submit() |
| Driver\Dashboard | Display reports | $reports, $hasPendingReports | delete(), submit() |

### 6.3 Services
| Class | Purpose | Key Methods |
|-------|---------|-------------|
| OpenRouterService | AI damage analysis | analyzeDamagePhoto(string $imagePath): array |

### 6.4 Jobs
| Class | Purpose | Retries | Backoff |
|-------|---------|---------|---------|
| AnalyzeDamageReportJob | Process AI analysis | 3 | [10, 30, 60] seconds |

---

## 7. UI/UX Specification

### 7.1 Create Report Page Layout
```
┌─────────────────────────────────────────────────────┐
│  < Back to Dashboard                                │
│                                                     │
│  Create Damage Report                               │
│  ─────────────────────                              │
│                                                     │
│  Photo *                                            │
│  ┌─────────────────────────────────────────────┐   │
│  │  [Choose File] No file chosen               │   │
│  │  JPG, PNG, WebP (max 5MB)                   │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  [Photo Preview - if uploaded]                      │
│                                                     │
│  Package ID *                                       │
│  ┌─────────────────────────────────────────────┐   │
│  │  e.g., PKG-12345                            │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  Location *                                         │
│  ┌─────────────────────────────────────────────┐   │
│  │  e.g., 123 Main St, City                    │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  Description                                        │
│  ┌─────────────────────────────────────────────┐   │
│  │  Describe the damage...                     │   │
│  │                                             │   │
│  │                                             │   │
│  └─────────────────────────────────────────────┘   │
│                                                     │
│  ┌──────────────┐          ┌──────────────────┐   │
│  │ Save Draft   │          │ Submit Report    │   │
│  └──────────────┘          └──────────────────┘   │
└─────────────────────────────────────────────────────┘
```

### 7.2 User Interactions
1. Driver clicks FAB or "Create Report" button
2. Driver selects photo file -> preview displayed
3. Driver fills Package ID and Location (required)
4. Driver optionally adds Description
5. Driver clicks "Save Draft" -> saved, redirected to dashboard
6. OR Driver clicks "Submit Report" -> saved, AI job queued, redirected

### 7.3 States and Transitions
- **Empty form:** All fields empty, buttons enabled
- **Photo selected:** Preview shown below input
- **Validation error:** Red border on field, error message below
- **Saving/Submitting:** Button shows spinner, disabled
- **Success:** Redirect to dashboard with flash message

### 7.4 Flux UI Components
- `flux:heading` - page title
- `flux:input` - Package ID, Location fields
- `flux:textarea` - Description field
- `flux:button` - Save Draft (secondary), Submit Report (primary)
- `flux:badge` - Status and severity indicators on dashboard

---

## 8. Testing Strategy

### 8.1 Unit Tests
| Test File | Test Cases |
|-----------|------------|
| `tests/Unit/Services/OpenRouterServiceTest.php` | - parses valid JSON response |
|                                                   | - handles missing fields gracefully |
|                                                   | - constructs correct API request |

### 8.2 Feature Tests
| Test File | Test Cases |
|-----------|------------|
| `tests/Feature/Livewire/Driver/CreateReportTest.php` | - driver can access create page |
|                                                        | - supervisor cannot access |
|                                                        | - guest redirected to login |
|                                                        | - can save draft with valid data |
|                                                        | - can submit report |
|                                                        | - validation errors displayed |
|                                                        | - photo stored correctly |
|                                                        | - redirects after save |
| `tests/Feature/Livewire/Driver/EditReportTest.php` | - driver can edit own draft |
|                                                      | - cannot edit submitted report |
|                                                      | - cannot edit other's report |
|                                                      | - can update and submit |
| `tests/Feature/Jobs/AnalyzeDamageReportJobTest.php` | - updates report on success |
|                                                       | - retries on failure |
|                                                       | - handles malformed response |

### 8.3 Critical Test Scenarios
1. **Happy Path:** Driver creates report, submits, AI processes, dashboard updates
2. **Draft Flow:** Driver saves draft, edits later, then submits
3. **AI Failure:** API fails 3 times, report stays Submitted with NULL AI fields
4. **Validation:** All required field validations work correctly

### 8.4 Test Data Requirements
- DamageReportFactory with states: draft, submitted, approved, withAiAssessment
- Fake image file for upload tests
- Mocked OpenRouter HTTP responses

---

## 9. Architecture Guidelines

### 9.1 Code Location
- Livewire Components: `app/Livewire/Driver/`
- Services: `app/Services/`
- Jobs: `app/Jobs/`
- Views: `resources/views/livewire/driver/`
- Tests: `tests/Feature/Livewire/Driver/`, `tests/Feature/Jobs/`

### 9.2 Naming Conventions
- Components: `CreateReport`, `EditReport` (PascalCase)
- Views: `create-report.blade.php`, `edit-report.blade.php` (kebab-case)
- Jobs: `AnalyzeDamageReportJob` (descriptive, ends with Job)
- Services: `OpenRouterService` (ends with Service)

### 9.3 Patterns to Follow
- Use Livewire class components (not Volt) - matches existing Dashboard
- Use services for external API calls
- Use jobs for async processing
- Use policies for authorization
- Store files in `storage/app/public/damage-reports/{user_id}/`
- Use `Storage::disk('public')` for file operations
- Display existing photos using `Storage::url($path)` in edit form
- Policy auto-discovery is enabled (no manual registration needed)

### 9.4 Code Quality Rules
- Run `vendor/bin/pint --dirty` before committing
- All methods must have explicit return types
- Use PHP 8 constructor property promotion
- Inject dependencies via constructor

---

## 10. Validation & Completion Checklist

### Per-Phase Completion:
- [ ] Phase 1: Routes and Basic Component (REQ-1.1 through REQ-1.12)
- [ ] Phase 2: Create Report Form (REQ-2.1 through REQ-2.14)
- [ ] Phase 3: Submit & Queue Job (REQ-3.1 through REQ-3.7)
- [ ] Phase 4: OpenRouter & AI (REQ-4.1 through REQ-4.8)
- [ ] Phase 5: Edit Report (REQ-5.1 through REQ-5.11)
- [ ] Phase 6: Dashboard Updates (REQ-6.1 through REQ-6.8)
- [ ] Phase 7: Integration & Testing (REQ-7.1 through REQ-7.8)

### Final Completion:
- [ ] ALL phases completed
- [ ] All tests pass (`php artisan test`)
- [ ] Code style validated (`vendor/bin/pint --dirty`)
- [ ] Manual testing completed
- [ ] Queue worker tested with real OpenRouter API

---

## 11. Notes & Decisions Log

| Decision | Rationale | Date |
|----------|-----------|------|
| Combined Features 3 + 4 | Tightly coupled - submission triggers AI | 2025-12-11 |
| Async queue processing | Better UX - driver doesn't wait for AI | 2025-12-11 |
| 3 retries with exponential backoff | Balance reliability and quick failure feedback | 2025-12-11 |
| Smart polling (3s) | Simple, no WebSocket infrastructure needed | 2025-12-11 |
| Simple file input | MVP simplicity over drag-and-drop | 2025-12-11 |
| Free text location | No Google Places dependency | 2025-12-11 |
| "Analyzing..." badge | Clear feedback that AI is processing | 2025-12-11 |
| OpenRouter model via env | Flexibility to change models without code changes | 2025-12-11 |

---

## 12. Open Questions / Future Considerations

- **Real-time updates:** Could upgrade polling to Laravel Reverb for instant updates
- **Multiple photos:** Currently single photo - could expand later
- **Photo optimization:** Could add image compression on upload
- **Offline support:** Could cache form data for poor connectivity
- **AI prompt tuning:** May need to adjust prompt based on real results
