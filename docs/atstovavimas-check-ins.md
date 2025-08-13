# Atstovavimas: Student‑Centric Check‑ins (Design Plan)

This document proposes a student‑centric “check‑in” backbone for Atstovavimas that avoids guessing cadence, removes persistent red states, and gives administrators clear visibility without notifications. It aligns with Laravel conventions, uses laravel-model-states (as with `Reservation.php`), and integrates into ShowDashboard and the Atstovavimas page.

## Goals
- Students can declare “no meetings expected until <date>” per institution (a blackout check‑in), visible to peers and admins.
- Dashboard urgency is suppressed while a valid check‑in is active; urgency returns when the check‑in expires or is invalidated by a newly scheduled meeting.
- No notifications. Focus on clarity, control, and shared visibility.
- Use Laravel patterns (Form Requests, Policies, Services), translations, Shadcn Vue components, and existing conventions.

## Scope and Non‑Goals

In scope (MVP):
- Student‑filed blackout check‑ins that suppress urgency until a specified date.
- Heads‑up mode (meeting expected soon) that does not suppress urgency.
- Confidence selection with clear guidance and visibility to peers/administrators.
- Hardcoded duration options: 7, 14, 28, 60 days (with warnings at 28/60).
- Atstovavimas integrations (chips with actions, dialog to add check‑in).
- Admin visibility and non‑destructive controls: dispute resolution and suppress/unsuppress (no deletes).

Out of scope (MVP):
- Email/push notifications. Only in‑app indicators are considered (see Optional Notifications).
- Institution cadence guessing or auto‑policy inference.
- Complicated approval workflows. Check‑ins are per‑user claims with optional peer confirmations.

---

## Domain Model

### Entities
- InstitutionCheckIn (new)
- InstitutionCheckInVerification (new)

### Relationships
- Institution hasMany InstitutionCheckIn
- InstitutionCheckIn belongsTo Institution, belongsTo User (author), optional belongsTo Meeting (invalidated_by)
- InstitutionCheckIn hasMany InstitutionCheckInVerification
- InstitutionCheckInVerification belongsTo InstitutionCheckIn, belongsTo User

### State Machine (laravel-model-states)
Leverage model states (like `Reservation.php`) for `InstitutionCheckIn.state`:
- Active: valid until `until_date` (future). Default on create.
- Expired: `until_date` passed.
- Invalidated: a meeting was created/updated before `until_date`.
 - Withdrawn: author withdrew the claim.
 - Disputed: a peer (member of the same institution) disputes the claim; blackout is NOT in effect.
 - AdminSuppressed: an admin suppressed the claim’s effect without deleting it; blackout is NOT in effect.

Transitions:
- Active → Expired (time passes)
- Active → Invalidated (Meeting <= until_date)
 - Active → Withdrawn (author action)
 - Active → Disputed (peer dispute)
 - Active → AdminSuppressed (admin suppression)
 - Disputed → Active (author resolves/update or peer retracts; optional)
 - Disputed → Withdrawn (author action)
 - Disputed → AdminSuppressed (admin suppression)
 - AdminSuppressed → Active (admin unsuppresses after review)
- Expired → (no transitions)
- Invalidated → (no transitions)
- Withdrawn → Active (admin re‑open within same dates; optional)

Guards & Handlers:
- Guard create with window cap (hardcoded choices: 7, 14, 28, 60 days ahead) and member duty check.
- On Meeting saved, invalidate relevant Active check‑ins.
- A daily job (or computed on read) moves stale Active to Expired (no notifications).
 - Disputed/AdminSuppressed do not suppress urgency. They remain visible with reason and actor.

State classes live under `App\\States\\InstitutionCheckIns\\{Active,Expired,Invalidated,Withdrawn,Disputed,AdminSuppressed}` with a `CheckInState` base (like Reservation).

---

## Database Schema

### institution_check_ins
- id (PK)
- tenant_id (FK → tenants) nullable (supports global and padalinys scoping)
- institution_id (FK → institutions, indexed)
- user_id (FK → users, indexed) — author
- until_date (date, required)
- checked_at (datetime, default now)
- confidence (enum: low|medium|high, default medium)
- note (text, nullable)
- visibility (enum: institution) — reserved for future access modes
- state (string) — laravel-model-states
- invalidated_by_meeting_id (FK → meetings, nullable)
- verified_count (int, default 0)
 - mode (enum: blackout|heads_up, default blackout)
 - disputed_by_user_id (FK → users, nullable)
 - disputed_at (datetime, nullable)
 - suppressed_by_user_id (FK → users, nullable)
 - suppressed_reason (string, nullable)
 - suppressed_at (datetime, nullable)
- created_at, updated_at

Constraints/Indexes:
- index (institution_id, state)
- index (until_date)
- unique partial index not required; allow multiple historical check‑ins
 - index (mode)

### institution_check_in_verifications
- id (PK)
- check_in_id (FK → institution_check_ins, indexed)
- user_id (FK → users, indexed)
- created_at

Constraints:
- unique (check_in_id, user_id)

---

## Backend Contracts (Laravel way)

### Routes
- POST /institutions/{institution}/check-ins → store (Create)
- POST /check-ins/{checkIn}/confirm → store verification
 - POST /check-ins/{checkIn}/withdraw → update state → Withdrawn (author only)
 - POST /check-ins/{checkIn}/flag → attach an admin/user note (non‑destructive, optional)
- POST /check-ins/{checkIn}/dispute → mark as Disputed (peer in same institution)
- POST /check-ins/{checkIn}/resolve → resolve dispute (author); transition to Active or Withdrawn
- POST /check-ins/{checkIn}/suppress → AdminSuppressed (admin padalinys/global)
- POST /check-ins/{checkIn}/unsuppress → back to Active (admin)
- GET  /institutions/{institution}/check-ins (optional admin/history)

Authorize via Policies (see below).

### Form Requests
- StoreInstitutionCheckInRequest: validates `until_date` (required, date, after:today, before_or_equal:+60 days), `note` (nullable, string, max:1000), `confidence` (in:low,medium,high)
- ConfirmInstitutionCheckInRequest: no body; user must be member
 - WithdrawInstitutionCheckInRequest: author only
 - FlagInstitutionCheckInRequest (optional): note (string, max:1000)
 - DisputeInstitutionCheckInRequest: reason (string, max:500) optional; user must be member of the same institution
 - ResolveInstitutionCheckInRequest: author action; choose resolution: keep Active (update note/dates) or Withdrawn
 - SuppressInstitutionCheckInRequest: reason (string, max:500) required; admin only
 - UnsuppressInstitutionCheckInRequest: admin only

### Policies
- InstitutionCheckInPolicy
  - create: user has a current duty in institution
  - confirm: same
   - withdraw: author only (admins cannot delete/withdraw)
   - flag: admins (global/padalinys), optionally members
  - dispute: members of the same institution
  - resolve: author of the check‑in
  - suppress/unsuppress: admin (padalinys/global)
  - view: members + admins

### Service Layer
- CheckInService
  - create(user, institution, until_date, note?, confidence?): InstitutionCheckIn
    - Guards: membership, cap, overlapping existing meeting warning (not a blocker)
    - Sets state=Active
  - confirm(user, checkIn): idempotent increment of verified_count via verifications table
  - withdraw(user, checkIn): state → Withdrawn (author only)
  - flag(user, checkIn, note): attach note (activity log or related model) — optional
  - dispute(user, checkIn, reason?): state → Disputed; set disputed_by_user_id/at
  - resolve(user, checkIn, resolution): author resolves dispute → Active (optionally update note/date) or Withdrawn
  - suppress(admin, checkIn, reason): state → AdminSuppressed; set suppressed_by_user_id/reason/at
  - unsuppress(admin, checkIn): AdminSuppressed → Active
  - invalidateByMeeting(meeting): set state → Invalidated for any Active check‑ins where meeting.start_time <= until_date and same institution
  - expireStale(now): mark Active check‑ins with until_date < today as Expired (optional: run daily)
  - latestActiveFor(institution): returns most recent Active by checked_at/created_at

### Observers / Listeners
- MeetingSaved listener → CheckInService.invalidateByMeeting
- Optional: Schedule `expireStale` daily (no notifications)

### Eloquent Helpers
- Institution model:
  - checkIns(): HasMany
  - activeCheckIn(): hasOne latest Active (scope with state)
- InstitutionCheckIn model:
  - verifications(): HasMany
  - scopeActive()

### Confidence Taxonomy & Guidance
Confidence affects suggested durations and chip styling (UX) but not state logic:

- Low (Tentative): use for uncertain info; suggest 7–14 days; lighter chip; warn against long windows.
- Medium (Likely): based on patterns/internal comms; suggest 14–28 days.
- High (Confirmed): official info; suggest 28–60 days; show responsibility warning.

Heads‑up (meeting expected soon):
- Represent as mode=heads_up (NOT a blackout), separate from confidence.
- Max horizon: 7 days, auto‑expires; does not suppress urgency.
- UI shows amber chip “Heads‑up: meeting expected” with optional note; CTA: “Convert to meeting” or “Convert to blackout” once date known.

Responsibility & visibility text (always shown in dialog):
“By adding a check‑in you affirm that no meetings are expected until the selected date. This information is visible to other members and administrators.”

### Casting & Types
- Use enums for confidence if preferred; otherwise string backed enum.
- Dates cast with `protected $casts = ['until_date' => 'date', 'checked_at' => 'datetime']`.

---

## Frontend UX (Student‑first)

### ShowDashboard.vue (first‑run clarity)
- Add a compact “Active Check‑ins” widget:
  - Shows up to 5 institutions with soonest expiring or missing check‑ins.
  - Chips: “Covered until 2025‑08‑28 by J.K. (2 confirmations)”.
  - CTAs: “Add check‑in”, “Confirm”, “Schedule meeting”.
  - Link to Atstovavimas page for full view.

### ShowAtstovavimas.vue (deep view)
- Institutions card/table:
  - Add a column/section showing check‑in status per institution:
    - Neutral: Covered until DATE (avatar of author, verified_count)
    - Amber: Expires in ≤7 days
    - Red: No active check‑in or expired (fallback to existing meeting urgency)
    - Small badge if invalidated: “Invalidated on YYYY‑MM‑DD by meeting”
  - Inline buttons:
    - Add check‑in (Dialog with presets: +1 week, +1 month, Custom, capped at 60d)
    - Confirm (idempotent)
    - Withdraw (author/admin)
  - Admin actions (visible only to admins):
    - Dispute, Resolve (keep active / withdraw), Suppress, Unsuppress.
    - Disputed or Suppressed states do not suppress urgency.
    - Reasons for dispute/suppress are captured via small prompt (MVP).

- Calendar quick‑add:
  - @dayclick opens NewMeetingModal with `defaultInstitutionId` (if row context) and `defaultStartTime`.
  - Popover shows “Add meeting” when day is empty.

- Safety fixes (apply):
  - Non‑mutating sorts (`[...arr].sort(...)`), optional chaining, de‑dupe meetings by id before tables/calendars.

### Chip Legend (current UI mapping)
- Active (blackout):
  - “Neplanuoja iki YYYY‑MM‑DD” | badge: amber/neutral
- Active (heads_up):
  - “Heads‑up iki YYYY‑MM‑DD” | badge: amber; does not suppress urgency
- Expired:
  - “Nebegalioja” | badge: gray
- Invalidated:
  - “Nebegalioja (yra posėdis)” | badge: rose
- Withdrawn:
  - “Atšaukta” | badge: gray
- Disputed:
  - “Ginčijama” | badge: amber; urgency not suppressed
- AdminSuppressed:
  - “Slopinama” | badge: orange; urgency not suppressed

### Components
- AddCheckInDialog.vue
  - Props: institutionId, maxDate (today+60d), presets (7/14/28/60), onSuccess callback
  - Fields: mode (blackout|heads_up). If blackout: until_date choices 7/14/28/60; if heads_up: fixed max 7 days.
  - Fields: note, confidence (for blackout only; heads-up can omit or default to Medium)
  - Shows responsibility text and warnings at 28/60
- ConfirmCheckInButton.vue (single‑click)
- CheckInChip.vue — visual chip with author avatar, until, confidence, verifications
 - DisputeCheckInButton.vue — visible to peers; opens small form (optional reason)
 - SuppressCheckInButton.vue — admin‑only action with reason

All strings via lang files (PHP or JSON) with dot keys (e.g., `checkins.covered_until`).

---

## Admin Experience (Padalinys and Global)

- Admin Check‑ins Index (padalinys/global scope)
  - Filters: Tenant/Padalinys, Institution, State (Active/Expired/Invalidated/Withdrawn), Expires before, Author, Verified count range
  - Actions: View, Flag/Comment (non‑destructive), Suppress/Unsuppress (non‑destructive), Export CSV
  - Insights: Top institutions without check‑ins, average horizon by tenant, invalidation rates, disputes over time, suppressed counts, heads‑ups usage

- Institution detail (admin)
  - Timeline of check‑ins (activitylog), current Active with author and verifications
  - Add note/flag; no delete/withdraw

- Governance
  - Cap max until_date at 60 days (hardcoded choices: 7/14/28/60)
  - Global guidance text in dialog (no per‑tenant customization needed)

---

## Optional Notifications (In‑App UI only)
No email/push. Optional subtle UI indicators:
- Dashboard chips show “Expires in X days” when ≤7 days remain.
- After expiration, an “Expired check‑ins” section appears until acted upon.
- An optional small bell popover can list expiring/expired check‑ins.

All indicators respect invalidations.

---

## Statistics & Reporting (Admins)
Provide comfortable visibility for padalinys/global admins:
- Coverage: institutions with Active check‑ins vs total; average/median blackout horizon; distribution by confidence and mode (blackout vs heads_up).
- Quality: invalidation rate; expired count; verifications per check‑in.
- Trends: weekly/monthly charts; disputes and suppressions over time; heads‑ups created/resolved; top institutions without check‑ins in last N days.
- Operations: filters (tenant, institution, state, confidence, expires before, author), CSV export, drill‑down.

---

## Risks & Mitigations
- User‑asserted truth (incorrect or stale check‑ins):
  - Mitigate with visibility (author, note), confirmations, disputes, and admin suppression.
  - Hardcoded caps and warnings for long windows reduce misuse.

- No admin withdrawal (ownership vs remediation):
  - Mitigate with AdminSuppressed state (non‑destructive), plus flag/comments for context.

- Confidence confusion:
  - Keep short, in‑context guidance; confidence does not affect logic; suggested durations align with confidence.

- “Meeting soon, no date” ambiguity:
  - Model as heads‑up (non‑blackout), expires in ≤7 days, with clear CTAs to convert.

- Data correctness (timezones/reschedules):
  - Normalize on server timezone; centralize invalidation in a listener; add robust tests for create/update/reschedule.

- Perverse incentives (gaming with long blackouts):
  - Responsibility text, warnings on 28/60, soft rate‑limits (optional), visibility to admins.

---

## Recognition & Rewards (Optional, Non‑blocking)
Encourage positive behaviors without heavy gamification:

- Contribution counters (per user, per tenant):
  - Meetings created, agendas added before T‑48h, minutes submitted within 24h, past meetings backfilled, check‑ins created/confirmed, disputes resolved.
- Display: personal progress on ShowDashboard, subtle badges; tenant leaderboard (opt‑in, low emphasis).
- Admin view: aggregate contributions per tenant/institution to spot active coordinators.
- Safeguards: avoid rewarding long blackouts; reward confirmations and timely minutes instead.

---

## Edge Cases & Rules
- Meeting exists before until_date when creating a check‑in:
  - Allow creation with a visible warning: “Meeting on 2025‑08‑20 will invalidate this check‑in.”
- Multiple check‑ins:
  - Only the latest Active per institution is considered for display; history remains for audit.
- Duty removed from author after creation:
  - Check‑in remains; label author as “(not currently assigned)” in tooltip.
- Never met:
  - Neutral state with “Add check‑in” CTA; avoid red until an explicit miss (expired check‑in or long inactivity if you keep fallback rules).
- Pauses/Breaks:
  - Students can choose any date within cap (covers breaks) without global policy changes.
- Related institutions:
  - Check‑ins are per primary institution; related institution meetings don’t change status unless explicitly linked (keep simple).

---

## Testing Strategy
- Unit (Services)
  - create/confirm/withdraw transitions
  - invalidateByMeeting picks matching Active rows and sets state/meeting link
  - expireStale moves past‑due to Expired
  - latestActiveFor handles multiple historical rows deterministically

- Feature (HTTP)
  - Store check‑in (validates cap, membership)
  - Confirm idempotency
  - Withdraw permissions (author/admin)
  - Inertia props include check‑in chips on dashboard and atstovavimas pages

- Model State Tests
  - Each state class transition validity and guards (like Reservation)

- Frontend (Vitest)
  - AddCheckInDialog validates date bounds and presets
  - Chips render states correctly; buttons call endpoints

---

## Phased Rollout
1) Core Data + Service — Implemented
  - Migrations, models with states, service, policies, form requests
  - MeetingSaved listener to invalidate
  - Implemented: database/migrations (check_ins, verifications), app/Models/InstitutionCheckIn(+Verification), app/States/InstitutionCheckIns/*, app/Services/CheckInService.php, observer registered in EventServiceProvider, optional console command checkins:expire-stale

2) Admin Index (basic) + API — Implemented (MVP)
  - Endpoints: create/confirm/withdraw/dispute/resolve/suppress/unsuppress/flag under /api/v1 (auth:api)
  - Controllers and FormRequests wired; Policy registered and uses configured indicating permission
  - Admin index page (Inertia) with filters and pagination
  - Seeded demo data: InstitutionCheckInFactory + entries in DatabaseSeeder

3) Dashboard + Atstovavimas UI — Implemented (MVP)
  - Chips and dialog integrated; confirm/withdraw wired; admin actions (dispute/resolve/suppress/unsuppress) available.

4) Polish & Insights
   - Avatar stacks, tooltips, CSV export, filters, translations coverage
   - Optional daily expireStale scheduler (no notifications)

---

## Quick Usage

- Members
  - Open Administravimas → Atstovavimas → Pažymos to review current check‑ins (read‑only list).
  - On Atstovavimas dashboard: use “Pridėti pažymą” to add a blackout or heads‑up; “Patvirtinti” to confirm a peer’s check‑in; “Atšaukti” to withdraw your own.

- Admins (padalinys/global per indicating_permission)
  - From Atstovavimas dashboard chips, you can “Ginčyti”, “Slopinti”, “Atšaukti slopinimą”, and “Palikti aktyvią/Atšaukti pažymą” (resolve disputes).
  - Use the Check‑ins index for filtering by state and mode, and for oversight.


---

Implementation status summary:
- Backend
  - Policies: done (config-driven admin check via indicating permission)
  - API routes/controllers: done
  - Web (admin) action routes: done (store/withdraw)
  - Services/Observers/States: done
- Frontend
  - Admin check-ins index page: done
  - Atstovavimas chips (quick add/withdraw): done
  - Dashboard widget and full dialog UX: pending (next)
- Data
  - Factory and seeded examples: done

---

## Quality Gates
- Laravel best practices: Policies, FormRequests, Services, Eloquent scopes, model states
- Translations: no hardcoded strings; use lang files
- UI: Shadcn Vue components, Tailwind utilities in templates; avoid @apply except essentials
- Tests: Unit + Feature + minimal front‑end tests
- Performance: eager‑load meetings where needed; de‑dup meetings; avoid N+1

---

## Open Questions (resolved for MVP)
- Cap choices: hardcoded 7/14/28/60 with responsibility/warning text (sufficient for now).
- Admin permissions: read‑only + flag/comment; cannot delete/withdraw user check‑ins.
- Guidance: single global guidance text in the dialog; no per‑tenant overrides at launch.

This plan prioritizes student control, clear visibility for peers/admins, and a calm dashboard. It uses familiar Laravel patterns and integrates cleanly with your current UI stack.

---

## Implemented Features (as of 2025‑08‑08)

Status: MVP backend and admin UI done; dashboard chips and dialog integrated; scheduler enabled; i18n added. Tests and screenshots pending.

- Backend
  - Models, states, service, observer, console command in place.
  - Daily expiry job scheduled at 03:00 (Console Kernel) to move stale Actives → Expired.
  - Policy + FormRequests enforce membership/author/admin guards; indicating_permission used for admin capabilities.
  - API and web routes registered for create/confirm/withdraw and admin actions.

- Admin web actions (session guard)
  - Dispute, Resolve (keep/withdraw), Suppress, Unsuppress are available via POST endpoints.
  - Admin Check‑ins Index has filters (state, mode) and friendly state badges.

- UI (Atstovavimas)
  - Chips show per‑institution status with colors by state and mode‑aware text:
    - Active: “Heads‑up until …” or “No meetings until …”; shows verified_count.
    - Expired/Invalidated/Withdrawn/Disputed/Suppressed: labeled accordingly.
  - Actions: Add check‑in (dialog), Confirm, Withdraw; admin‑only Dispute/Suppress/Unsuppress/Resolve.
  - TypeScript fixes and v‑calendar attribute correctness applied.

- Navigation
  - Administration → Atstovavimas → “Pažymos” opens the Check‑ins index.

- Where it lives (high‑level)
  - Routes: routes/admin.php (web), routes/api.php (API)
  - Controllers: app/Http/Controllers/Admin/CheckInActionController.php, .../Api/InstitutionCheckInController.php
  - Service: app/Services/CheckInService.php
  - States: app/States/InstitutionCheckIns/*
  - Observer/Command: app/Observers/MeetingObserver.php, app/Console/Commands/ExpireCheckIns.php; scheduled in app/Console/Kernel.php
  - UI: resources/js/Pages/Admin/Dashboard/ShowAtstovavimas.vue, resources/js/Components/CheckIns/AddCheckInDialog.vue, resources/js/Pages/Admin/CheckIns/Index.vue
  - i18n: lang/en.json, lang/lt.json

