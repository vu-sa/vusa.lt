---
title: Platform Updates
lastUpdated: true
---

# Platform Updates

Here you'll find all changes and improvements to the mano.vusa.lt platform.

## v1.19 — Faster ViSAK Timeline and New Content Blocks (2026-07-28) {#v1-19}

### ViSAK tenant timeline

- ✨ **The timeline loads several times faster** — meetings load only for the visible period and stream in as you scroll, and only the rows on screen are drawn, so scrolling through dozens of tenants stays smooth
- ✨ **Visible meetings-loading indicator** — loading rows show a pulsing placeholder, and the toolbar displays a loading badge
- ✨ **"Keep one" button** — next to "All" in the tenant picker, quickly narrows the view to a single tenant
- 🔧 **Only the selected tenants' institutions are shown** — related institutions from other tenants are no longer duplicated, so the list matches the status summary counts
- 🔧 **Fixed missing meetings** — an empty response was being cached, so newly created meetings no longer look like they don't exist

### New content blocks and page settings

- ⭐ **Three new content blocks** — a link list (news, pages, or manually entered links), an event list (filterable, groupable by unit), and a person quote with photo and title
- ⭐ **"Spacer" block** — controls the vertical gap between any two blocks when the default spacing doesn't fit; five sizes from extra small to huge
- ⭐ **"Section" block** — groups the blocks that follow it into one section with a shared title, background and rounded corners, up to the next section block
- ⭐ **Table of contents (sidebar) toggle** in the page settings
- ⭐ **Hide the page title and last-updated time** — when the heading is already shown inside the content

### Easier editing

- ⭐ **Edit and preview side by side** — any block can open a dialog with live editing and a reactive preview together; the preview width is picked from the block's allowed widths and is saved
- ✨ **New block picker dialog** — blocks grouped by category, searchable by name, with a live preview of the highlighted type; the hero shows a preview for each variant
- ✨ **Blocks can be collapsed** — large blocks no longer take over the screen, and dragging to reorder is much easier
- ✨ **Per-block width control** — text, content, wide or full width, depending on the block type
- ✨ **Easier image grid and gallery editing** — images are managed directly on the grid with a focal point, and alt text is asked only once when picking an image
- 🔧 **The image picker no longer lists PDFs and other unsupported files**
- 🔧 **Removed the redundant "Editing / Preview" toggle** — only "Preview all" remains

### Block appearance and styles

- ✨ **New hero variants** — alongside the classic two columns: centered, banner and panel styles, picked from schematic previews; hero buttons can carry an icon
- ⭐ **Headings can have a size, color accent, alignment and top spacing** — independent of their H2/H3/H4 level
- ⭐ **New dot-style "tag" mark in the rich text editor** — filled or plain, in four colors, like the badges on the membership page
- ✨ **Title, subtitle and background on every section-type block** — accordion, card stack, carousel, gallery, stats and content grid (including a subtle gradient and plain white)
- ✨ **Rounded corners, background and padding for the hero block too**, including the banner style
- ✨ **Redesigned card block** — the decorative icon is gone, color shows as a subtle side accent, and the card title is slightly larger and spaced from the body
- ✨ **Grid images support a focal point, overlay content and decorative accents**, and overlay content can be pinned to a corner of the image instead of protruding past its edge
- ✨ **The link list's "with photos" style supports images for manually entered links**
- ✨ **Link and event list grids adapt to the item count** — 1 or 2 items no longer stretch across the full width
- ✨ **Content grid columns can be vertically aligned, and the header left-aligned** — shorter text no longer stretches to full height
- ✨ **Reduced default top spacing of headings** — it can be increased, tightened or removed via the heading style picker
- 🔧 **Heading size, color accent, and top spacing changes are now visible while editing** — previously they only appeared in preview mode

### Fixes

- 🔧 **The content grid no longer wipes its content** every time it's reopened for editing, and a grid card without an image no longer shows an empty placeholder
- 🔧 **The event list no longer crashes when previewed before saving**, and groups by unit are sorted alphabetically and can show short unit names (e.g. "VU EVAF")
- 🔧 **The hero block no longer freezes when switched to the two-column style** when it has no overlay content; empty overlay content is no longer shown, and the banner style no longer shows settings that don't apply to it
- 🔧 **The card stack's background is no longer see-through** behind the front card, and card block content no longer disappears when previewing all blocks
- 🔧 **Fixed a layout bug that pushed page content to the right** on screens between 768 and 1024 px wide
- 🔧 **The page's last-updated date moved to the bottom of the page** — with a clearer label and always a precise date (no more "3 days ago")
- 🔧 **The table of contents no longer has a mobile variant** — it duplicated another floating button
- 🔧 **The image dialog in the admin interface shows translated labels again** — alt text, title and selection-step translations were only bundled for the public site, so the admin dialog displayed raw translation keys

### Document search

- 🔧 **Documents that are really links to other websites (e.g. annual reports) now open that website directly** — previously they opened an empty SharePoint preview
- ✨ **Such entries are marked with a "Link" badge**, the Download button is hidden for them, and copying the link copies the website address

## v1.18 — Refreshed Event and Camp Pages (2026-07-27) {#v1-18}

- ⭐ **Map of the event location** — when an event has a location, a map with a marker is shown next to it; the Google Maps link stays
- ✨ **Freshmen camps page redesigned** — camps appear right after a short intro, with dates and locations; if a unit runs more than one camp, all of them are listed
- ✨ **Event timing reads fluently** — multi-day events are written as a single range (e.g. "25–27 August 2026"), and all-day events no longer show midnight
- ✨ **Less urgency and repetition on event pages** — the "Next", "Approaching" and "Soon" badges are gone, leaving only the facts: "Happening now" and "Event has passed"; share buttons are no longer duplicated
- 🔧 **Event descriptions render properly again** — headings, lists and paragraphs no longer run together as one block of text
- 🔧 **The registration button works** — a registration link set on an event was previously never shown

## v1.17 — Managing Deleted Records and Easier Lists (2026-07-27) {#v1-17}

- ⭐ **Deleted records can be restored** — banners, calendar events, quick links, navigation items, categories, tags, trainings, study programmes and study sets are no longer lost immediately
- ⭐ **Permanent deletion** — from the separate deleted-records view, confirmed by typing the record's name; it needs its own permission, grantable in roles without changing the normal delete permission
- ⭐ **Member and student rep registrations in the sidebar** — both forms are available under "Website", and the people handling them can open the forms list without form-editing permission
- ⭐ **Excel export for registrations** — the download button works again, alongside the total registration count and the date of the latest one
- ✨ **Active/deleted view toggle** — it is clear which view you are in and how many records are deleted; the table also shows when each was deleted
- ✨ **Actions are visible directly in lists** — view, edit and restore are no longer hidden behind the "⋯" button; deletion stays in a separate menu so it cannot be clicked by accident
- ✨ **The form URL is generated automatically** — derived from the form's name; changing an existing form's link now warns that the old one will stop working
- ✨ **Institution activity statuses and reminders** — clearer labels show when activity is up to date and when reporting is approaching; a no-meeting report now counts as an activity update
- ✨ **Unit status summary in ViSAK** — select several units above the timeline, review their institution status counts, and use search and pagination
- 🔧 **List messages are translated** — actions, search, column and pagination labels and empty states no longer appear in English in the Lithuanian admin
- 🔧 **Permanently deleting a duty no longer errors** — you get an explanation of why a duty with membership history cannot be removed; the history is preserved
- 🔧 **Student rep registrations are isolated by unit** — coordinators see only registrations submitted for institutions in their own unit
- 🔧 **Form field descriptions no longer disappear** — editing a form that already had registrations overwrote every description with its field type
- 🔧 **Sidebar quick actions work again** — "New meeting", "New news" and "New reservation" no longer throw an error
- 🔧 **More accurate empty institution contacts state** — institutions without public contacts no longer show a misleading message about student representatives
- 🔧 **Deleting no longer loses related data** — restoring a meeting brings back its agenda and votes, restoring a tag brings back its articles, and deleting a navigation item no longer strands its sub-items
- 🔧 **Deleted records no longer block URLs, emails and language pairs** — the language pair is released automatically, and for a URL or email you are told that a deleted record holds it and what to do about it
- 🔧 **Permanent deletion no longer throws errors** — when a record cannot be removed because of related data, the action is disabled up front and says what is holding it
- ✨ **Delete dialogs explain the consequences** — the confirmation now says what will happen, e.g. that a duty's membership history is preserved
- 🔧 **File usage checks see deleted records** — a file used only by a deleted record is no longer marked safe to delete

## v1.16 — New Website Analytics (2026-07-26) {#v1-16}

- ⭐ **Traffic statistics on the Svetainė dashboard** — page views, visitors, a views-over-time chart and top pages for the selected unit, right inside the admin interface. Only that unit's site data is shown, so no separate analytics account is needed
- ⭐ **Views for an individual news item or page** — opening a news item or page shows how many times it has been viewed and by how many visitors. If it was published before data collection began, the card says so
- ✨ **Site search terms are now recorded** — what visitors search for on vusa.lt and how many results they got. This shows which content people look for but fail to find. Only the search text and the result count are stored, with no visitor data attached

## v1.15 — Meeting Periodicity & Notifications Fixes (2026-07-14) {#v1-15}

- ✨ **Vacations no longer count toward meeting periodicity** — summer, winter, late January and Easter vacation days are excluded when measuring how long an institution has gone without a meeting. Tasks and reminders are no longer raised during a break, and task deadlines no longer land inside one
- 🔧 **"Institutions needing attention" works again** — a calculation error meant this dashboard block never surfaced overdue institutions

## v1.14 — Search, Discussions & Reservations Console (2026-07-13) {#v1-14}

- ⭐ **Reservations console** — the cards and modals are replaced by a KPI strip (awaiting review / lent / unresolved / returned) and a table with "My reservations" and "Administered" tabs. You can approve, hand over and mark items returned straight from the list, act on several reservations at once, and close out a stale reservation in one action. Each status now has its own colour and icon, and reservations with an active item past its window are flagged in amber
- ⭐ **Proper admin search page** — search now has a page of its own, showing previews of the records it finds. Several edit forms also use a new, more comfortable picker built on the same search
- ⭐ **Resource reservation history in previews** — resource search previews and the resource picker dialog when creating a reservation now show active reservations (with times and status) and up to three previous reservations.
- ⭐ **Discussions instead of comments** — comments were reworked into a discussion panel, now available on meetings and agenda items as well as institutions. Discussions can also carry polls
- ⭐ **Standalone public search page** — public search opens on its own page instead of in a dialog
- ⭐ **Mixcloud embeds** — Mixcloud players can now be embedded in content
- ✨ **Unit filter on the reservation page** — when a reservation spans resources from several units, you can narrow the list down to one unit
- ✨ **Refined institution graph** — and search previews now show the institutions and duties related to a record
- ✨ **Self-lockout warning** — changing a duty's members now warns you if the change would remove your own access
- ✨ **Smarter default sorting** on index pages
- ✨ **Improved translations** across the platform
- ✨ **Clearer cookie consent**
- ✨ **Many styling fixes** — across various cards and pages
- 🔧 **Form fixes** — including the "is reservable" select in the resource form
- 🔧 **Fixed opening the resource category list**
- ✨ **More flexible resource availability** — active reservations whose time window has already ended no longer block free units, but they are flagged so they can be closed out
- 🔧 **Table pagination** — pagination controls were not appearing on tables that filter their data in the browser
- 🔧 **Notification emails are being sent again** — digest emails had not gone out since early April. They work again, and stale digests from that period will not be delivered. If a digest fails to send, its notifications are no longer lost — they stay queued for the next attempt
- ⭐ **Test email** — settings now have a button next to your notification email addresses that sends a test digest, so you can confirm it reaches your inbox
- ✨ **Longer vote titles** — vote titles can now be up to 200 characters (was 125)
- 🔧 **More reliable navigation and news saving** — fixed validation so saving a navigation divider without a name or creating news without an assigned tenant shows a clear error instead of throwing a SQL exception

## v1.13 — Problem Registry Improvements (2026-06-12) {#v1-13}

- ⭐ **Unit filter in the problem list** — problems can now be filtered by unit, not just by institutions
- ⭐ **Quick filters "My unit's problems" and "Problems I created"** — show only your unit's or your own registered problems with one click
- ✨ **Search in filter lists** — the unit and institution filters now have a search box for quickly finding the right entry
- ✨ **Explanations in the problem form** — every form field now has an info icon explaining what to enter, and category options show their descriptions
- 🔧 **Long problem titles** — long titles in the list now wrap to the next line instead of overlapping other table elements

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
