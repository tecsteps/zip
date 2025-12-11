# User Authentication - Q&A Session

**Date:** 2025-12-11
**Status:** Ready for Specification

---

## Decisions Summary

| Topic | Decision |
|-------|----------|
| **Driver Registration** | Self-registration via public form |
| **Supervisor Creation** | Seeded via database seeder |
| **Password Requirements** | Laravel defaults (min 8 characters) |
| **Role Assignment** | Auto-assign Driver role on registration |
| **Post-Login Redirect** | Single `/dashboard` route, content varies by role |
| **Auth Pages** | Login + Registration only (password reset is Laravel default) |
| **Role Implementation** | Simple `role` enum column on users table |
| **Supervisor Credentials** | Fixed: `supervisor@zip.test` / `password` |
| **Route Protection** | Custom middleware for role-based route groups |
| **Data Ownership** | Drivers can only see their own data |
| **Supervisor Access** | Supervisors see ALL reports across all drivers |

---

## Message 1: Understanding Your Vision

### The Feature

**My Current Understanding:**
Based on the roadmap, User Authentication is the foundation feature for zip. It provides email/password login for two user roles: Drivers (who upload damage photos) and Supervisors (who review/approve reports). The project already has Laravel Fortify installed, which handles the backend authentication logic.

**Questions:**
1. For driver registration - should drivers self-register, or should supervisors invite/create driver accounts?

Self

2. Should supervisors also be created via self-registration, or is there a different process (e.g., seeded admin account, manual database entry)?

Let's set up one supervisor in the seeding system

3. Are there any specific password requirements beyond Laravel's defaults (min 8 characters)?

I'll keep it simple

**Fabian's Comment:**

---

## Message 2: Role Assignment & Post-Login Flow

### Q1: New User Role Assignment

When a driver self-registers, they should automatically become a Driver. Just confirming this is correct?

- [x] **A) Auto-assign Driver role** - All self-registered users are Drivers
- [ ] **B) Choose during registration** - User selects their role

**Recommendation:** Option A - keeps registration simple, supervisors are seeded only.

**Fabian's Comment:**

---

### Q2: Post-Login Redirect

Where should users go after logging in?

- [ ] **A) Role-based redirect** - Drivers go to Driver Dashboard, Supervisors go to Supervisor Dashboard
- [x] **B) Single dashboard** - Everyone goes to `/dashboard`, content varies by role
- [ ] **C) Home page** - Everyone goes to `/` which shows role-appropriate content

**Recommendation:** Option A - cleaner separation, easier to implement with Fortify.

**Fabian's Comment:**

---

### Q3: Auth Pages Needed

Which authentication pages should we build? (Fortify provides the backend, we provide the views)

- [x] Login page
- [x] Registration page (for drivers)
- [ ] Password reset request page
- [ ] Password reset form page
- [ ] Email verification page
- [ ] Two-factor authentication setup

**Recommendation:** The checked items cover the basics from the roadmap. Email verification and 2FA are out of scope per the roadmap.

**Fabian's Comment:**

Morse is already existing by Laravel out of the box but we do not have role permission system right now

---

## Message 3: Role Implementation

### Q4: Role Storage Approach

How should we implement the role system?

- [x] **A) Simple `role` column** - Add a `role` enum column to users table (`driver`, `supervisor`)
- [ ] **B) Spatie Permission package** - Full-featured roles/permissions package
- [ ] **C) Separate `roles` table** - Many-to-many relationship for future flexibility

**Recommendation:** Option A - For just 2 fixed roles with no granular permissions needed, a simple enum column is the cleanest approach. We can add helper methods like `isDriver()` and `isSupervisor()` to the User model.

**Fabian's Comment:**

---

## Message 4: Final Details

### Q5: Seeded Supervisor Credentials

What credentials should the seeded supervisor have?

- [x] **A) Fixed credentials** - e.g., `supervisor@zip.test` / `password`
- [ ] **B) Environment variables** - Read from `.env` file
- [ ] **C) Random password** - Generated and displayed during seeding

**Recommendation:** Option A for simplicity during development. The seeder can create `supervisor@zip.test` with password `password`.

**Fabian's Comment:**

---

### Q6: Authorization Middleware

How should we protect routes?

- [x] **A) Custom middleware** - Create `EnsureUserIsDriver` and `EnsureUserIsSupervisor` middleware
- [ ] **B) Gate/Policy** - Use Laravel Gates: `Gate::define('driver', ...)`
- [ ] **C) Inline checks** - Check role in controllers/components directly

**Recommendation:** Option A - Middleware is clean for route-level protection. We can group routes by role.

**Fabian's Comment:**

It's not only about the role driver; it's also that a driver cannot see the data of another driver

---

## Message 5: Data Ownership Clarification

### Q7: Supervisor Data Access

Good point about data ownership. So drivers only see their own reports. Confirming for supervisors:

- [x] **A) Supervisors see ALL reports** - Full visibility across all drivers
- [ ] **B) Supervisors see assigned drivers only** - Supervisor-driver assignment system

**Recommendation:** Option A per the roadmap ("Review all reports across all drivers").

**Fabian's Comment:**

---

### Summary Check

I believe we have all the information needed. Here's the complete picture:

**Authentication:**
- Login + Registration pages (Livewire/Volt + Flux UI)
- Laravel Fortify handles backend
- Password reset uses Laravel defaults

**Roles:**
- `role` enum column: `driver`, `supervisor`
- `isDriver()` / `isSupervisor()` helper methods on User model
- New users auto-assigned `driver` role
- One supervisor seeded: `supervisor@zip.test` / `password`

**Authorization:**
- Middleware for route protection by role
- Drivers scoped to own data (enforced in queries/policies)
- Supervisors see all data

**Routes:**
- `/dashboard` - unified dashboard, content varies by role
- Auth routes via Fortify (`/login`, `/register`, `/logout`)

Does this cover everything, or is there anything else we should address?

**Fabian's Comment:**

It's all good. Let's write the technical specification

---
