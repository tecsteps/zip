# Roadmap - Q&A Session

**Date:** 2025-12-11
**Status:** Ready for Specification

---

## Decisions Summary

| Topic | Decision |
|-------|----------|
| **App Name** | zip |
| **User Roles** | Drivers (upload photos) + Supervisors/Managers (review/approve reports) |
| **Interface** | Web only (mobile-responsive), file upload only (no camera) |
| **AI Assessment** | Full analysis: severity categories, damage type, value impact, liability |
| **AI Provider** | OpenRouter (model configurable via env) |
| **Output** | Web page report (not PDF) with dummy SVG logo |
| **Package ID** | Manual entry (shipment ID, tracking number, or description) |
| **Photos** | Single photo per report |
| **Supervisor Role** | Review & approve workflow (reports finalized after approval) |
| **Tenancy** | Single company |
| **Authentication** | Email/password |
| **Workflow States** | Simple: Draft, Submitted, Approved |
| **Required Fields** | Package/Shipment ID, Location/address |
| **Optional Fields** | Description/notes |
| **Auto Fields** | Driver name (from account), Date/time (auto) |

---

## Message 1: Understanding Your Vision

### The Feature

**My Current Understanding:**
Based on the name "roadmap," I'm thinking this could be a feature that allows users to visualize and track progress toward goals, milestones, or project deliverables. This might be a visual timeline or kanban-style board showing planned features, releases, or objectives - either for internal team use or as a public-facing product roadmap.

**Questions:**
1. What problem does this feature solve? (e.g., tracking product development, showing customers what's coming, managing personal/team goals?)
2. Who will use this feature? (Internal team, customers, both?)
3. What does success look like? (How will you know the roadmap feature is working well?)

**Fabian's Comment:**

Let's build a roadmap for the zip tool.

What We're Building

A small app for damage reporting for courier operations. Drivers upload photos of damaged packages, and the system automatically assesses severity and generates professional reports.

The result of this is not coding. The results of this will be a rope map file which we then use to build the
individual features.

---

## Message 2: Understanding the Damage Reporting App

### Core Workflow

**My Current Understanding:**
You're building "zip" - a damage reporting app for courier operations. The core flow is:
1. Driver encounters a damaged package
2. Driver uploads photos of the damage
3. System automatically assesses severity (likely via AI/image analysis)
4. System generates a professional damage report

This sounds like a focused, practical tool that streamlines what's probably a manual, inconsistent process today.

**Questions:**

1. **User Roles:** Who are the different users of this system?
   [x] Drivers (uploading damage photos)
   [x] Supervisors/managers (reviewing reports?)
   [ ] Customers/recipients (receiving reports?)
   [ ] Insurance/claims teams?

2. **Photo Upload Flow:** How do drivers upload photos?
   [ ] Mobile app (PWA or native)?
   [x] Web interface?
   [ ] Both?

3. **AI Assessment:** What should the automatic severity assessment determine?
   [x] Damage categories (minor, moderate, severe)?
   [x] Type of damage (crushed, wet, torn, etc.)?
   [x] Estimated value impact?
   [x] Liability assessment?

We will use AI to automatically analyze the image and assess the damage

4. **Report Output:** What does a "professional report" include?
   [x] PDF document?
   [ ] Email notification?
   [ ] Integration with existing courier systems?

---

## Message 3: Diving Deeper

### Driver Workflow

**My Current Understanding:**
A driver uses the web app (likely on their phone browser) to:
1. Open the app when they encounter damaged package
2. Take/upload photos
3. Add any notes
4. Submit for AI analysis
5. Review/confirm the generated assessment
6. Download or share the PDF report

Note: Just upload. We will not use the camera for today.



**Questions:**

### Q1: Package Identification

How does a driver identify which package is damaged?

- [x] **A) Manual entry** - Driver types shipment ID, tracking number, or description
- [ ] **B) Barcode/QR scan** - Scan package barcode via camera
- [ ] **C) Both options** - Manual entry with optional barcode scan
- [ ] **D) No package ID** - Just photos and notes, package identified separately

**Recommendation:** Option C gives flexibility - barcode scan for speed, manual entry as fallback.

**Fabian's Comment:**

---

### Q2: Photo Requirements

How many photos should drivers upload per damage report?

- [x] **A) Single photo** - One photo per report
- [ ] **B) Multiple required** - Minimum 2-3 photos required (different angles)
- [ ] **C) Multiple optional** - 1 required, additional optional (up to X)
- [ ] **D) Flexible** - No limits, as many as needed

**Recommendation:** Option C with 1 required and up to 5-10 optional gives flexibility while ensuring minimum documentation.

**Fabian's Comment:**

---

### Q3: Supervisor Dashboard

What should supervisors/managers be able to do?

- [ ] **A) View only** - See all reports, filter/search, download PDFs
- [x] **B) Review & approve** - Approve reports before they're finalized
- [ ] **C) Edit & override** - Modify AI assessments, add notes, change severity
- [ ] **D) Full admin** - All above + manage drivers, view analytics

**Fabian's Comment:**

---

### Q4: Multi-tenancy

Is this app for a single courier company or multiple?

- [x] **A) Single company** - One instance, one company, simpler setup
- [ ] **B) Multi-tenant** - Multiple courier companies, each with their own drivers/data
- [ ] **C) Future multi-tenant** - Start single, design for multi-tenant later

**Recommendation:** Option C - start simple but architect for growth.

**Fabian's Comment:**

---

## Message 4: Technical & Workflow Details

### Q5: Authentication

How should users log in?

- [x] **A) Email/password** - Traditional login
- [ ] **B) Magic link** - Email a login link (passwordless)
- [ ] **C) SSO/OAuth** - Login via Google, Microsoft, etc.
- [ ] **D) Simple PIN/code** - Driver enters a code provided by supervisor

**Recommendation:** For a courier app, option A or B keeps it simple. Magic links are nice for drivers who might forget passwords.

**Fabian's Comment:**

---

### Q6: AI Provider

Which AI service should analyze the damage photos?

- [ ] **A) OpenAI (GPT-4 Vision)** - Widely used, good image analysis
- [ ] **B) Anthropic (Claude)** - Strong reasoning, good at structured output
- [ ] **C) Google (Gemini)** - Good vision capabilities
- [ ] **D) Configurable** - Support multiple, let admin choose

**Fabian's Comment:**

I'm not sure about the model yet but we will use OpenRouter and I will provide an API key in the environment file

---

### Q7: Report Workflow States

What statuses should a damage report have?

```
Draft -> Submitted -> [AI Processing] -> Pending Review -> Approved -> PDF Generated
                                      -> Rejected (back to driver?)
```

- [x] **A) Simple** - Draft, Submitted, Approved (3 states)
- [ ] **B) Standard** - Draft, Pending Review, Approved, Rejected (4 states)
- [ ] **C) Detailed** - Draft, Processing, Pending Review, Approved, Rejected, Archived (6 states)

**Fabian's Comment:**

---

### Q8: Report Data Fields

What information should the driver provide (besides the photo)?

**Required fields:**
- [x] Package/Shipment ID
- [ ] Driver name (auto from login?)
- [ ] Date/time (auto?)
- [x] Location/address

Driver name comes from the user account. Date and time is automatically added to the database.

**Optional fields:**
- [x] Description/notes
- [ ] Sender info
- [ ] Recipient info
- [ ] Package contents (if known)
- [ ] Weather conditions

Please check which fields should be included and whether required or optional.

**Fabian's Comment:**

---

### Q9: PDF Report Content

What should the generated PDF include?

- [x] Company logo/header
- [x] Report ID and date
- [x] Package/shipment details
- [x] Driver information
- [x] Damage photo(s)
- [x] AI assessment summary
- [x] Severity rating
- [x] Damage type classification
- [x] Estimated value impact
- [x] Liability assessment
- [x] Supervisor approval signature/timestamp
- [ ] QR code linking to digital report

**Fabian's Comment:**

Let's put a nice SVG there that looks like a company logo as dummy.

The report can be a flat website. It does not need to be PDF

---

## Message 5: Tech Stack (Already Established)

### Existing Project Setup

I see this is already a Laravel 12 project with:
- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Livewire/Volt (single-file components), Flux UI components
- **Styling:** Tailwind CSS 4
- **Auth:** Laravel Fortify (already included)
- **Build:** Vite
- **Testing:** Pest

This is a solid stack - no additional decisions needed here.

### Q10: Final Check - Anything Missing?

Before I generate the roadmap, is there anything else we should consider?

- [ ] Notifications (email when report approved?)
- [ ] History/audit log
- [ ] Export functionality (CSV of all reports?)
- [ ] Search/filter for reports
- [ ] Dashboard statistics

**Fabian's Comment:**

We are all good to go. Let's create the roadmap (reminder: you should not create a specification. We're creating a 
roadmap her with scope-items(Features). For each feature write a brief description of what it is based on our brainstorming.)


---
