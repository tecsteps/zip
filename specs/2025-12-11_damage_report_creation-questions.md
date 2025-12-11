# Damage Report Creation - Q&A Session

**Date:** 2025-12-11
**Status:** Ready for Specification

---

## Message 1: Understanding Your Vision

### The Feature

**My Current Understanding:**
Based on the roadmap and existing specification, Damage Report Creation is Feature 3 of the zip app. It allows drivers to submit damage reports by uploading a single photo of a damaged package, entering required information (Package/Shipment ID, Location/Address), and optionally adding notes. Reports start as "Draft" and can be submitted for supervisor review.

The existing specification covers the basics but I want to ensure we have all the details needed for a complete implementation.

**Questions:**
1. The existing spec mentions photo upload with drag-and-drop - is this essential, or would a simple file input be sufficient for MVP?
2. For the Location field - should this be free text, or would you prefer address autocomplete (e.g., Google Places)?
3. When a driver saves a draft, should they be able to edit it later from the dashboard?

**Fabian's Comment:**

Look into the roadmap and find out what'S missing (e.g. AI integration)

---

## Message 2: Roadmap Review - Feature 3 vs Feature 4

### Scope Clarification from Roadmap

**From the Roadmap (Feature 3 - Damage Report Creation):**
- Photo upload (single image, file upload - no camera integration)
- Required fields: Package/Shipment ID, Location/Address
- Optional field: Description/Notes
- Auto-captured fields: Driver name (from account), Date/Time (automatic)
- Report status starts as "Draft", moves to "Submitted" upon submission

**From the Roadmap (Feature 4 - AI Damage Assessment):**
- OpenRouter API integration (model configurable via environment variable)
- Image analysis for damage assessment
- Structured output: severity category, damage type, value impact, liability
- Store AI assessment results with the report
- Handle API errors gracefully

**Key Insight:** The roadmap states that upon submission, "the system triggers AI analysis." This means:
- **Feature 3** = Create/upload the report form + submission mechanism
- **Feature 4** = The actual AI integration that gets triggered when a report is submitted

**My Recommendation:**
Feature 3 should include the database columns for AI results (already in the schema: `ai_severity`, `ai_damage_type`, `ai_value_impact`, `ai_liability`) but leave them NULL. Feature 4 will populate these fields.

**Questions:**
1. Should Feature 3 submission simply change status to "Submitted" and Feature 4 handles AI? Or should we stub out the AI trigger in Feature 3?

Let's build features 3 and 4 now in this brainstorming

2. The Driver Dashboard (Feature 2) shows AI severity on report cards - should this show "Pending" or be hidden until Feature 4 populates it?

Let's solve this now

3. Do you want to implement Features 3 and 4 together since they're tightly coupled, or keep them separate?

Let's do it now

---

## Decisions Summary

| Topic | Decision |
|-------|----------|
| **Scope** | Features 3 + 4 combined in one specification |
| **AI Trigger** | AI analysis triggered on report submission |
| **AI Severity Display** | Show "Pending" badge for reports awaiting AI |
| **AI Processing** | Asynchronous via queue job |
| **AI Failure Handling** | Retry automatically (3x with exponential backoff), stays "Submitted" |
| **OpenRouter Model** | `openai/gpt-5-mini` (configurable via env) |
| **AI Output** | severity, damage_type, value_impact, liability |
| **Photo Upload** | Simple file input |
| **Location Field** | Free text input |
| **Draft Editing** | Yes, drivers can edit drafts |
| **UI Updates** | Smart polling (wire:poll.3s) when pending reports exist |

---

## Message 3: Combined Feature Scope - Report Creation + AI Assessment

### Expanded Scope

We're now building **Features 3 + 4 together**:
1. **Report Creation Form** - Photo upload, Package ID, Location, Description
2. **AI Damage Assessment** - OpenRouter integration triggered on submission

### Q1: AI Severity Display on Dashboard

The Driver Dashboard (Feature 2) shows AI severity badges on report cards. For reports without AI assessment yet:

- [x] **A) Show "Pending"** - Yellow badge saying "Pending" or "Analyzing..."
- [ ] **B) Hide completely** - Don't show any severity badge until AI completes
- [ ] **C) Show "Not assessed"** - Gray badge indicating no assessment

**Recommendation:** Option A (Pending) - gives feedback that something is happening.

**Fabian's Comment:**

---

### Q2: AI Processing - Sync vs Async

When should AI analysis happen?

- [ ] **A) Synchronous** - Driver waits on submit, sees AI results immediately (slower UX, simpler)
- [x] **B) Asynchronous (Queue)** - Submit returns immediately, AI runs in background job
- [ ] **C) Hybrid** - Try sync with timeout, fall back to queue if slow

**Recommendation:** Option A for MVP - simpler implementation, drivers see results immediately.

**Fabian's Comment:**

---

### Q3: AI Failure Handling

If OpenRouter API fails or returns an error:

- [ ] **A) Block submission** - Report not submitted, show error, let driver retry
- [ ] **B) Submit anyway** - Report submitted without AI, supervisor reviews manually
- [x] **C) Retry automatically** - Queue retries, report submitted with "Pending" status

**Recommendation:** Option B - don't block the driver's workflow due to AI issues.

**Fabian's Comment:**

---

### Q4: OpenRouter Model Configuration

The roadmap mentions "model configurable via env." What model should be the default?

- [ ] **A) GPT-4 Vision** - `openai/gpt-4-vision-preview`
- [ ] **B) Claude 3 Haiku** - `anthropic/claude-3-haiku` (fast, cheap)
- [ ] **C) Claude 3 Sonnet** - `anthropic/claude-3-sonnet` (balanced)
- [ ] **D) Other** - Please specify

**Recommendation:** Option B (Claude 3 Haiku) - good vision, fast, cost-effective for high volume.

**Fabian's Comment:**

We'll use openai/gpt-5-mini (don't change it)

---

## Message 4: Queue & AI Implementation Details

### Q5: Queue Retry Configuration

How many times should the AI job retry before giving up?

- [x] **A) 3 retries** - Standard, fails within ~1 minute
- [ ] **B) 5 retries** - More persistent, fails within ~5 minutes (exponential backoff)
- [ ] **C) 10 retries** - Very persistent, keeps trying longer

**Recommendation:** Option A (3 retries) - quick feedback if API is down.

Use an increasingly growing backofftime

After max retries, what status should the report have?

- [x] **A) Stay "Submitted"** - AI fields remain NULL, supervisor sees no assessment
- [ ] **B) Mark as "Failed"** - New status indicating AI couldn't process

**Recommendation:** Option A - simpler, supervisor can still approve without AI.

**Fabian's Comment:**

---

### Q6: AI Assessment Output Structure

The AI will analyze the photo and return structured data. Confirming the fields from the roadmap:

| Field | Type | Example Values |
|-------|------|----------------|
| `ai_severity` | string | minor, moderate, severe |
| `ai_damage_type` | string | crushed, wet, torn, punctured, etc. |
| `ai_value_impact` | string | low, medium, high, total loss |
| `ai_liability` | string | carrier, sender, recipient, unknown |

Is this structure correct? Any fields to add or change?

**Fabian's Comment:**

OK

---

### Q7: Form UX Details

Going back to the form questions from Message 1:

**Photo Upload:**
- [x] **Simple file input** (click to upload)
- [ ] **Drag-and-drop zone** (more polished but more complex)

**Location Field:**
- [x] **Free text input** (simple)
- [ ] **Address autocomplete** (Google Places - adds complexity)

**Draft Editing:**
- [x] **Yes** - Drivers can edit their drafts from dashboard
- [ ] **No** - Drafts are one-time, must delete and recreate

**Recommendation:** Simple options for MVP.

**Fabian's Comment:**

---

## Message 5: Final Confirmation

### Summary of What We're Building

**Report Creation Form:**
- Simple file input for photo upload (single image, max 5MB, jpg/png/webp)
- Required: Package ID, Location (free text)
- Optional: Description/Notes
- Auto: Driver name, Date/Time
- Actions: Save Draft, Submit Report
- Edit draft functionality from dashboard

**AI Assessment (triggered on Submit):**
- Asynchronous queue job dispatched on submission
- OpenRouter API with `openai/gpt-5-mini` model
- 3 retries with exponential backoff
- On success: populates ai_severity, ai_damage_type, ai_value_impact, ai_liability
- On failure: report stays "Submitted", AI fields remain NULL

**Dashboard Updates:**
- Show "Pending" badge when AI fields are NULL on submitted reports
- Update report card to show AI results when available

**Architecture:**
```
Driver submits report
       │
       ▼
┌──────────────────┐
│ CreateReport     │──► Report saved (status: Submitted)
│ Livewire         │──► AnalyzeDamageJob dispatched
└──────────────────┘
       │
       ▼
┌──────────────────┐
│ AnalyzeDamageJob │──► OpenRouter API call
│ (Queue)          │──► Update report with AI results
└──────────────────┘
```

**Ready to generate the technical specification?**

- [ ] Yes, proceed with specification
- [x] No, I have more questions/changes

**Fabian's Comment:**

How should we update the UI? Would we use polling or streaming? What do you recommend?

---

## Message 6: Real-time UI Updates

### Q8: How to Update Dashboard When AI Completes

When the AI job finishes in the background, how should the dashboard reflect the new AI results?

- [ ] **A) Polling** - Livewire `wire:poll.5s` on pending reports, checks every 5 seconds
  - Pros: Simple, no extra infrastructure
  - Cons: Slight delay, unnecessary requests if no pending reports

- [ ] **B) Real-time (Laravel Echo + Reverb/Pusher)** - WebSocket broadcasts `AiAnalysisCompleted` event
  - Pros: Instant updates, elegant UX
  - Cons: Requires broadcasting setup (Reverb or Pusher)

- [ ] **C) Manual refresh** - User refreshes page or navigates to see updates
  - Pros: Simplest implementation
  - Cons: Poor UX, user doesn't know when AI is done

- [x] **D) Smart polling** - Only poll when there are pending reports, stop when all complete
  - Pros: Balance of simplicity and efficiency
  - Cons: Still some unnecessary requests

**My Recommendation:**

For MVP, I recommend **Option D (Smart polling)** with `wire:poll.3s`:
- Simple to implement - just add `wire:poll.3s` conditionally when `$hasPendingReports`
- No extra infrastructure needed
- Reasonably responsive (3 second delay max)
- Stops polling when no pending reports

If you want a more polished experience later, we can upgrade to Option B (Real-time) - Laravel Reverb is free and built-in as of Laravel 11.

**Fabian's Comment:**

---
