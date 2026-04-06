---
title: Platform Updates
lastUpdated: true
---

# Platform Updates

Here you'll find all changes and improvements to the mano.vusa.lt platform.

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
