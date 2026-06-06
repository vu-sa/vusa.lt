---
title: Platform Updates
lastUpdated: true
---

# Platform Updates

Here you'll find all changes and improvements to the mano.vusa.lt platform.

## v1.15 — Polls and Notifications (2026-06-02) {#v1-15}

- ⭐ **Polls in discussions** — you can now create a poll in the comments: single- or multiple-choice options, ready-made templates (Yes / No, Approve / Reject…), and an optional voting deadline. Voting happens in place, you can see who voted for what, and a reply under a poll is badged with its author's choice — so you can see why they voted that way
- ⭐ **Mention notifications** — mentioning someone in a comment (@) now notifies them
- ✨ **More focused discussion notifications** — agenda item comments now reach the meeting's representatives, and replies notify only the thread's participants rather than the whole group

## v1.14 — Discussions (2026-06-02) {#v1-14}

- ⭐ **Discussions** — agenda items, meetings and institutions now have a comments area: threaded replies, reactions, @mentions, and the ability to mark a question as resolved. Comments appear in real time without a page reload
- ✨ **Broader agenda item access** — coordinators and related-institution representatives can now open an agenda item page in read-only mode and take part in the discussion, even without edit rights
- ✨ **Comment and note indicators in lists** — the meeting agenda list shows, per item, whether it has comments and notes

## v1.13 — Unified Search (2026-06-02) {#v1-13}

- ⭐ **Unified search** — a new search page that searches news, pages, documents, events, institutions and meetings at once; results are grouped by content type and ordered by relevance, with a "load more" per group
- ✨ **Faster search** — the search dialog was replaced by a dedicated page and the heavy search library was dropped, so search loads faster

## v1.12 — Meeting UI Refinement (2026-06-01) {#v1-12}

- ✨ Redesigned meeting view
- ⭐ **Dedicated agenda item page** — clicking an agenda item opens a separate page for editing it, including votes and decisions
- ⭐ **Real-time shared representative notes** — the agenda item page now has a private "Representatives' notes" area where several representatives can write at the same time; edits and other people's cursors appear instantly, and the notes can be opened in a larger window. 
- ⭐ **Navigation between agenda items** — the item page now has previous/next buttons and an "Item N / total" overview at the top, letting you jump across the whole meeting's items and see their statuses at a glance
- ✨ **Auto-save** — agenda item changes are saved automatically; a status indicator and the "Save" button live in a fixed bottom bar, and auto-save can be turned off
- ✨ **Clearer item editing** — the question type and votes were reorganised, votes are numbered and tagged with a "Main" marker, and fields are labelled with whether they are publicly visible

### Other

- ✨ More compact admin UI

## v1.11 — Customizable Sidebar & Recently Visited Pages (2026-06-01) {#v1-11}

### Sidebar

- ⭐ **Customizable sidebar** — a new "Customize sidebar" option in the account menu lets you hide or show optional sections (quick actions, followed institutions, START FM, help, recently visited) and drag to reorder them. The logo, account menu and main navigation stay visible. Choices are saved to your account and apply across browsers
- ⭐ **Recently visited pages** — the sidebar and the command palette (Cmd/Ctrl+K, before searching) now show the admin pages you visited most recently
- ⭐ **Pinned pages** — pin any admin page (star it in recently-visited or the command palette) to keep it in a dedicated "Pinned" section in the sidebar; choices are saved to your account
- ✨ **Compact view** — the "Customize sidebar" dialog now has a compact mode that tightens sidebar spacing

### Other

- ⭐ **Browser document viewing** — added `?web=1` parameter to document links so they open directly in the browser instead of downloading
- 🔧 **Mobile navigation close** — menus now automatically close when a link is tapped on mobile
- 🔧 Fixed file deletion in SharePoint environment
- 🔧 **Fixed filter clearing in document search** — the "Clear filters" button now properly clears all filters and shows all documents
- ✨ **Simplified date range filters** — removed redundant "3 months" and "6 months" options; kept the default "Recent" (3 months), "1 year", "Year range", and "Custom date"
- ✨ **Visible search button in admin tables** — all content tables now have a dedicated "Search" button next to the search field; filter layout spacing has also been improved

> 🔗 [GitHub PR #569](https://github.com/vu-sa/vusa.lt/pull/569)

## v1.10 — Ex-officio Duties & Cross-tenant Representatives (2026-05-12) {#v1-10}

- ⭐ **Ex-officio duties** — on the duty edit page you can list duties that are granted automatically alongside this one (e.g. chairing a body grants a seat in its directorate). Derived assignments mirror the source's dates, and when the source ends the derived ones are end-dated too
- ⭐ **Representatives from other tenants** — a duty (owned by one tenant) can allow other tenants to assign their own members to it, each with a quota. Such duties show up in the duties list (with a filter) and in the member wizard; those tenants' admins can manage only their own tenant's representatives and only up to the quota
- ✨ **Active users in member picker** — the transfer list for assigning duty members now shows only users active in the last 12 months by default (currently holds a duty, recently held one, recently logged in, or newly created account); all others are reachable via a "Show all users" toggle

> 🔗 [GitHub PR #568](https://github.com/vu-sa/vusa.lt/pull/568)

## v1.9 — Meetings & Admin Improvements (2026-05-06) {#v1-9}

- ⭐ **Meetings with multiple institutions** — meetings can now be associated with multiple institutions instead of just one
- ✨ **Improved index tables** — better table cells with date formatting, tag lists, truncated text, and links across the admin panel
- ✨ **Quick link form optimization** — improved quick link creation and management interface
- ✨ **Clearer email handling in user forms** — user forms now more clearly explain email fields
- ✨ **Meeting display improvements** — updated meeting detail page layout
- 🔧 **Fixed 23:59 display for meetings** — meetings without specific end times no longer show "23:59" in emails and displays
- 🔧 **Fixed and optimized single select forms** — improved performance for large dropdowns across multiple admin forms

> 🔗 [GitHub PR #566](https://github.com/vu-sa/vusa.lt/pull/566)

## v1.8 — Study Sets (2026-05-05) {#v1-8}

- ⭐ **[Study sets page](https://www.vusa.lt/ind-komplektai)** — public page to browse study sets by faculty, with course listings and lecturer reviews
- ✨ **Search and filtering** — search by course or study set name, filter by semester and faculty
- ⭐ **Study set management for administrators** — create and manage study sets, courses, and lecturer reviews

> 🔗 [GitHub PR #565](https://github.com/vu-sa/vusa.lt/pull/565)

## v1.7 — Notification Improvements (2026-04-06) {#v1-7}

- 🔧 **Fixed triplicate email digest notifications** — each notification was queued three times for the email digest, causing digest emails to show triple the actual notification count.
- 🔧 **Fixed Lithuanian notification text** — some notification titles displayed raw pluralization syntax instead of properly translated Lithuanian text.
- ✨ **Read notification sync with email digest** — marking a notification as read on the platform now removes it from the pending email digest, so read notifications are no longer sent by email.

> 🔗 [GitHub PR #554](https://github.com/vu-sa/vusa.lt/pull/554)

## v1.6 — Content Navigation Improvements (2026-04-06) {#v1-6}

- 🔧 **Empty content blocks no longer show an error** — pages with an empty tiptap content block no longer display an error message.
- 🔧 **Table of contents now works on pages** — headings have ID attributes, so clicking on a table of contents link will scroll to the corresponding heading
- 🔧 **No restriction for problem title length** — you can now have longer titles for problems without truncation
- 🔧 **Calendar event redirect fix** — events with untranslated titles now automatically redirect to a language where the title exists instead of showing an error
- 🔧 **File upload fix** — the file upload component used an incorrect URL to fetch allowed file types, causing a server error.
- 🔧 **Reservation form fixes**
  - Invalid date range in the URL now returns a validation error instead of a 500.
  - Changing the date range no longer triggers the "unsaved changes" warning.
  - Selected resource name no longer disappears after closing the dropdown.
  - Clicking "Submit" no longer triggers the "unsaved changes" warning.

> 🔗 [GitHub PR #553](https://github.com/vu-sa/vusa.lt/pull/553)

## v1.5 — Calendar & Meetings Improvements (2026-04-03) {#v1-5}

- 🔧 **Past events hidden in public calendar** — shown only after clicking "Show previous" in mobile view
- 🔧 **Today's meetings shown on dashboards** — main and representation dashboards now show today's meetings even if their start time has already passed

> 🔗 [GitHub PR #550](https://github.com/vu-sa/vusa.lt/pull/550)

## v1.4 — Documentation Overhaul (2026-03-31) {#v1-4}

- ⭐ **Updates page** — documentation now includes an updates page where platform changes are announced. When there are updates, you'll see an indicator next to the "Dokumentacija" link in the admin panel

> 🔗 [GitHub PR #546](https://github.com/vu-sa/vusa.lt/pull/546)

## v1.3 — Document Improvements (2026-03-23) {#v1-3}

- ⭐ **[Document](https://www.vusa.lt/dokumentai) actions** — the document list now has open, download, and copy link buttons
- ✨ **More robust document uploads** for administrators

> 🔗 [GitHub PR #542](https://github.com/vu-sa/vusa.lt/pull/542)

## v1.2 — Text Box Content Block (2026-03-12) {#v1-2}

- ⭐ **Text box block** — a new content block that lets visitors submit responses directly on the page. Responses can be viewed and exported to Excel. Currently used in the [vusa.lt sustainability section](https://vusa.lt/tvarumas/)

> 🔗 [GitHub PR #532](https://github.com/vu-sa/vusa.lt/pull/532)

## v1.1 — Problem Tracking (2026-03-10) {#v1-1}

- ⭐ **Problem management** — a new section to register, track, and manage problems related to your unit
- ⭐ **Institution linking** — problems can be linked to specific institutions

> 🔗 [GitHub PR #531](https://github.com/vu-sa/vusa.lt/pull/531)

## v1.0 — Platform Modernization (2026-02-07) {#v1-0}

> 📰 Full write-up: [mano.vusa.lt v1.0: Platform Modernization](/blog/2026-02-07-v1-modernization)

> 🔗 [GitHub PR #504](https://github.com/vu-sa/vusa.lt/pull/504)

### Major Changes

- ⭐ **Representation management** — revamped dashboard with Gantt timeline, representative activity and check-in system
- ⭐ **Public meetings** — meetings shown publicly for transparency, with search and agenda items
- ⭐ **Guided tours** — interactive tours for each page with help icon and progress tracking
- ⭐ **User update wizard** — simple wizard to update duty users
- ⭐ **News and content updates** — 4 news and 3 page layouts, highlights, social embeds
- ⭐ **Calendar timeline** — new, clearer event timeline
- ⭐ **Contact page** — Typesense search with filters

### Improvements

- ✨ **Updated sidebar** with animations
- ✨ **View transitions** across the platform
- ⭐ **PWA and notification system** — progressive web app support with notifications
- ⭐ **Searchable agenda items** for administrators
- ✨ **New font** — Atkinson Hyperlegible Next
- ✨ **Updated reservation views** with improved layout
- 🔧 **Fixed Microsoft authentication** issues
- ✨ **Updated rich content editor** with better tools
- ✨ **Extended institution relationship system** for better data modeling
