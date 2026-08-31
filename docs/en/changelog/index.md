---
title: Platform Updates
lastUpdated: true
---

# Platform Updates

Here you'll find all changes and improvements to the mano.vusa.lt platform.

## v1.36 — Events and meetings (2026-08-31) {#v1-36}

- ⭐ **A meeting can be created straight from an event** — the events list now offers "Create a meeting from this event" on any calendar entry not already standing for one. The date comes from the event, so the action window only asks which institution and what is on the agenda, and the new meeting is linked to that same event
- ⭐ **The action window can reach other institutions** — anyone who may create meetings across their tenant, or across the whole organisation, now gets a search over every institution they are entitled to, not only the ones they hold a duty in
- ✨ **On a phone, the event page leads with the date and place** — they used to sit below the whole description and agenda, where they were rarely seen
- ✨ **"Other events" shows what is coming up** — the list read the whole calendar newest-first, so it offered events a year out. It now shows the soonest upcoming ones, as compact rows instead of full cards
- 🔧 **A past meeting says "the meeting has taken place"** — an event page announcing a meeting no longer calls it an event that has passed
- 🔧 **The action window states when an institution last met** — it showed a day count that skips vacation periods, so it matched no calendar; it now shows the date

## v1.35 — Administrators and mail (2026-08-31) {#v1-35}

- 🔧 **A reopened task no longer falls back to the old holders** — when a meeting's agenda task had already completed and an administrator was nominated for the term afterwards, reopening it (by changing an item's type, say) handed it back to everyone who held a seat at the time — and mailed all of them. The holders are now resolved again on the way back
- 🔧 **Moving a cadence's dates re-staffs its tasks** — extending or shortening a term moves meetings in and out of it, but their tasks stayed with whoever held them before

## v1.34 — The member profile (2026-08-30) {#v1-34}

- ✨ **The member page overview has been rearranged** — instead of an "Activity" card of counters that repeated what the duty lists already said, the overview now shows current and previous duties, with contacts moved into a sidebar list. Previous duties are dimmed so the current ones stay dominant
- ✨ **Duty cards no longer carry institution statistics** — the "Fully occupied" badge and the "1 / 1 occupied" line are gone from a member profile: they describe the duty, not the person. The tenure period and the duty's email address are shown instead. Who else holds the same duty is still in the "Duties" tab
- ✨ **The header shows the photo and the primary duty** — the member's photo (or their initials) replaces the generic icon, and their primary current duty reads under their name. Dates are formatted in the chosen language
- 🔧 **The delete button only shows for someone who may delete** — the "…" menu used to offer deletion to anyone who could see the page at all

## v1.33 — The institution overview (2026-08-30) {#v1-33}

- ✨ **The institution overview has been rearranged** — the first tab no longer repeats what already has a tab of its own: tasks and related institutions are gone from it, and the upcoming-meeting banner and the separate last-meeting line are merged into a single card in the sidebar. The description and meetings sit on the left, status, members and the discussion on the right
- ✨ **The meeting list shows the agenda** — instead of the time, each row lists the first three agenda items and how many remain, with the meeting marked "Upcoming" or "Past". A member's duty now reads under their name
- ✨ **The short name is gone from the header** — it repeated what the institution's name already said

## v1.32 — The agenda item page (2026-08-30) {#v1-32}

- ✨ **The agenda item page has been reworked** — the status badge now reads before the title, the item type, its time slot and the "raised by representatives" flag moved into a card of their own, and the description and discussion are framed so it is clear where one block ends and the next begins
- ⭐ **Voting questions collapse and reorder** — a fully recorded vote reads as a single line with its outcome, while an unfinished one opens by itself. Drag to reorder them, and pick the main vote by hand with the star
- ✨ **Admin pages no longer stretch across the whole screen** — on a wide monitor the content is now centred and capped at a comfortable width
- 🔧 **The amber dot no longer marks empty representative notes** — merely opening an item used to save an empty notes document, so nearly every agenda item looked annotated. It is now marked only when the notes actually contain something, and with a neutral icon instead of a coloured dot

## v1.31 — Task deletion and the mail queue (2026-08-30) {#v1-31}

- 🔧 **A task can finally be deleted** — deletion failed at the database level for any task with someone assigned to it, which is to say almost all of them
- 🔧 **Deleting a meeting now deletes its tasks** — they used to linger without a meeting, still nagging people about something they could no longer open. The same goes for institutions and reservations. Restoring a meeting from the trash does not bring them back; `tasks:repopulate` recreates the automatic ones
- ⭐ **Super admins can delete automatic tasks** — the button now appears in the task table. Some automatic tasks can no longer be completed, and there has to be a way out
- ⭐ **A mail queue page** — the "Mail queue" card on the system status page now opens a page showing what has not been sent: every recipient and the lines their digest will contain. Super admins can drop a single line, a recipient's whole email, or empty the queue

## v1.30 — Task notifications and progress (2026-08-29) {#v1-30}

- 🔧 **A VU SR body's meeting task now counts its progress correctly** — Parlamentas, Taryba and the other VU SR bodies never record a student position or student benefit, yet the task still demanded them, so the percentage sat at 0 %. A decision alone is now enough for such an item
- 🔧 **Automatic tasks stop mailing people outside the term** — entering a meeting that happened long ago no longer notifies people who have left the institution, nor today's members who were not yet serving back then. Manually assigned tasks and reservation tasks are untouched

## v1.29 — Institution administrators (2026-08-29) {#v1-29}

- ⭐ **An institution can name its administrators** — the institution edit page now lets you assign specific people to each cadence, right beside the cadences themselves. They are named person by person rather than through a duty, and people already in the body are offered as one-tap suggestions
- ⭐ **With administrators named, the meeting tasks go to them alone** — every other member stops receiving them. With no administrators the tasks go, as before, to the representatives active at the time
- 🔧 **Tasks and reminders no longer reach former members** — meeting reminders used to go to everyone who had ever held a duty in the institution, with no regard for dates. Now only the people actually serving at the time are notified
- 🔧 **Comment and mention audiences are date-scoped too** — mentioning an institution or a meeting in a comment no longer notifies people who left years earlier
- ✨ **Administrators are shown on the meeting and institution pages** — separately from members and leaders, since an administrator need not hold a duty in the body at all
- ✨ **Administered institutions appear on your dashboard** — listed among your own with a distinct marker, so it stays clear that this is not membership
- ✨ **Changing the administrators reassigns the open tasks immediately** — no waiting for anything to be re-run

## v1.28 — The action window (2026-08-29) {#v1-28}

- ⭐ **One window for the most common actions** — a "Quick actions" button in the sidebar and on the home page opens a guided window. It asks which hat you are wearing today (student representative, VU SR member or coordinator), then what you want to do. Only the actions you are actually allowed to take are listed
- ⭐ **Report a meeting in a few taps** — the institution, the meeting format, the date and the time are each asked on their own screen, as large buttons. The agenda can be filled in right away or skipped. On a phone the window fills the screen; on a computer it opens as a normal dialog
- ⭐ **Report that there will be no meetings for a while** — previously only reachable from the ViSAK timeline; now available from any page
- ⭐ **Fill in a meeting that already happened** — the list puts the meetings missing the most first and says what is missing: the agenda or the decisions
- ✨ **The institution list shows which one needs attention** — each one says how long ago its last meeting was, or how long the no-meetings notice is valid
- ✨ **Creating a meeting looks the same everywhere** — the old creation dialog was replaced by the new window in every place it appeared: the sidebar, the home page, tasks, ViSAK, institution and search pages
- ⭐ **Only VU SR bodies are announced in the calendar** — announcing a meeting is offered only for VU SR's own bodies; for bodies VU SR merely delegates representatives into the option is gone from both meeting creation and the meeting page
- ✨ **The sidebar's "Quick actions" list is now one button** — a single always-visible button opens the action window instead. The setting for which quick actions to show went with it: the command palette (Ctrl+K) now lists every one you are allowed to use
- ✨ **"Change" on the review returns to the review** — amending one answer no longer walks you through every remaining step again
- ⭐ **The meeting time is suggested from this institution's own history** — the next two dates on the weekday this body has been meeting on, at the same time. If it has never met, nothing is guessed and the calendar opens straight away
- ⭐ **You can now pick the time** — choosing another date asks for the hour on its own screen: this institution's usual time first, then the common hours, or an exact time
- ✨ **Agenda questions are a plain list** — one line each, Enter opens the next, blank lines are dropped. This used to be the full meeting-page editor, which did not fit on a phone
- ✨ **You can see which action you are in** — the window header shows that action's icon next to the steps
- ✨ **A clearer institution list** — each status (overdue, approaching, marked as having no meeting, meeting scheduled) has its own icon and colour
- 🔧 **Dates render in Lithuanian** — the action window formatted dates using the browser's language rather than the app's
- 🔧 **The meeting time is saved correctly** — creating a meeting through the action window sent the time in UTC, so the saved meeting drifted by a few hours
- 🔧 **The "+" button in the phone bottom bar works** — it previously led to a page that does not exist
- ⭐ **You can paste the whole agenda at once** — a third choice in the action window: once the meeting is created, the meeting page opens the bulk dialog straight away, where the entire list of questions can be pasted in one go
- ✨ **The institution list leads with the upcoming meeting** — when a body has both a scheduled meeting and a no-meetings notice, both are shown
- ✨ **The bulk agenda dialog is reachable from an empty agenda** — it previously opened only once at least one item existed
- ✨ **A wider agenda questions dialog** — a pasted list of meeting questions is no longer squeezed into a narrow column
- ✨ **Clearer meeting page actions** — the "Edit meeting" button now carries its label, and "Add institution" moved into the actions menu
- 🔧 **The first agenda line's focus outline is visible** — writing questions in the action window sheared the outline off the top row
- 🔧 **No time is offered for an email meeting** — voting by email now suggests days without an hour and stores the date as a 23:59 deadline, including when the type is changed after a time was already picked; changing the type away from email on the review asks for a real hour instead of keeping the marker
- 🔧 **Agenda questions can be pasted on the meeting page too** — "Load from text" used to work only while creating a meeting; it is now available when adding questions, and pasted questions are appended to what is listed rather than replacing it
- 🔧 **"Manage duty periods" opens the timeline** — this action window entry led to the cadence settings and was shown only to settings managers; it now opens the duty-period timeline and is offered to everyone who may read duties

## v1.27 — Cadences, the duty-period timeline and meeting announcements (2026-08-28) {#v1-27}

### Duty period timeline

- ⭐ **Duty periods on one timeline** — every duty in an institution is drawn as a bar you can drag, with a dock underneath showing the selected row, the suggested fixes and any unsaved changes. Reachable from the People area and from duty, member and institution pages
- ⭐ **The timeline editor is also reachable from the sidebar** — it joins the quick actions, and opens on an institution you hold duties in
- ⭐ **Suggested fixes now say what they would change** — overlapping periods, open-ended rows whose cadence has ended, dates drifting off a cadence boundary and unfilled places are listed together with the dates they would be given. Several can be ticked and applied at once, after previewing the whole list
- ⭐ **Cadences** — the shared term dates live in Settings, while an institution's own exceptions are managed on the institution itself. A cadence is named after its dates, so there is no name to type
- ⭐ **Selecting and merging several periods** — a checkbox on every row and on each duty. With several ticked you can set a start or end date across all of them at once; with two stints of one person on one duty you can merge them into one
- ✨ **Rows that carry more than dates now say so** — an overridden email, study programme, photo or description is marked with an icon and a tooltip (an uploaded photo is shown), and merging warns first that some of it will be lost
- ✨ **A more readable timeline** — current and ended periods are coloured differently, consecutive cadences alternate shade, names are links, and the zoom level is remembered. Rows can be filtered by cadence — an assignment belongs to every cadence its period covers, and the selected one stands out in the chart — and by unit; ended ones can be hidden and duties collapsed, with a collapsed duty saying how many periods it hides and their combined span
- ✨ **Study programmes are shown** — the programme name is written out rather than hinted at by an icon, and rows can be sorted by it
- ⭐ **How long a period ran is visible** — each row shows its length ("2y 4mo"), falling back to months or days for short stints
- ✨ **A clearer institution switcher** — the button names the institution on screen, and the menu offers yours first. The institution name above the chart is now a link
- ⭐ **A guided tour** — first-time visitors are shown how to read and edit the bars; the help button replays it afterwards
- ✨ **A period can be removed from the timeline itself** — this used to mean opening a separate page
- 🔧 **Fixed the duty and member page buttons** — "Manage", "Assign a member" and "Edit" rendered for nobody, even with the right permissions

### Cadences, governance and the meetings chart

- ⭐ **A cadence boundary can be linked to a meeting** — on the institution form, a term's start or end can be taken from a meeting (e.g. the reporting-and-election conference). Any meeting you have access to can be picked, including another institution's (e.g. the unit conference) — the boundary then says whose sitting it is. The date comes from the meeting and moves with it
- ⭐ **A group or note field beside the study programme** — curators in large programmes can record e.g. "Group 1". It shows in the public contacts next to the study programme
- ✨ **Governance scope is visible on the institution page and form** — a badge says whether this is a VU SR body or an external one, and the form explains why some fields are hidden
- ⭐ **The meetings chart shows every meeting** — VU SR's own bodies are no longer hidden. A new "Hide VU SR's own bodies" option in the display settings hides them (off by default); a green icon by a meeting means it is announced in the calendar, amber means a draft

### Meetings in the calendar and in documents

- ⭐ **A meeting can be announced in the calendar** — "Announce in calendar" now sits in the meeting's action menu. It creates a draft event carrying the meeting's date and tenant, or links one already entered within a week of the meeting. Publishing that event makes the agenda and the linked documents publicly visible — including for a meeting that has not happened yet; the meeting page and its search entry stay private, opened only by the institution type setting
- ⭐ **Agenda and documents on the event page** — a calendar event standing for a meeting shows the agenda, the per-item times and the linked nutarimai and protokolai, with a link through to the meeting page
- ✨ **A compact public agenda** — instead of stacked cards the agenda reads as one list: status dot, title, time and decision on a single line, with the description and the full vote breakdown expanding on click
- ✨ **Time fields are always 24-hour** — a browser set to English used to show AM/PM. The time is now picked from hour and minute lists, with an ✕ to clear it
- ✨ **The meeting owns its event's timing** — the date fields on a calendar event that announces a meeting are locked and point at the meeting, so the time is never entered in two places
- 🔧 **The main vote can be removed** — deleting the last vote used to be blocked with no explanation. Removing the main one promotes the next automatically
- ✨ **VU SR bodies are no longer asked for the student vote, benefit or stated position** — a VU SR Parliament, Board or Audit Commission meeting records only the decision, because there the representatives *are* the organisation; these sections are not shown publicly either. Such meetings used to stay permanently "incomplete"
- 🔧 **A VU SR body's vote no longer sticks at "Not discussed"** — an agenda item whose decision was recorded without consensus (e.g. "Approved") now shows that status on the item's edit page, in the item navigator and on the calendar event's agenda, and the meeting page's "votes discussed" count includes such decisions
- 🔧 **Editor-facing statuses stay out of public view** — "Unclassified" is never shown publicly, and "Not discussed" is hidden on the agenda of a meeting that has not happened yet, where it says nothing
- ⭐ **Documents link to a meeting** — VU SR bodies' meetings get a "Documents" tab: nutarimai and protokolai can be found by search or uploaded straight from SharePoint, without registering them in the documents area first. The search now covers the whole tenant's documents, not just the body's own — e.g. VU SR Parliament's resolutions are registered under the central VU SR institution rather than the Parliament itself
- ✨ **The calendar event says it announces a meeting** — editing such an event shows a notice linking to the meeting and explaining what publishing it does
- ⭐ **Agenda items can carry a start and end time** — each question gets its slot already in the creation wizard's agenda step, and the times also show on the public agenda, so the running order of a longer meeting can be published in advance and people can turn up for the part that concerns them
- ⭐ **Institution types carry a governance scope** — VU SR body, VU body, national or international body. The value is inherited from the parent type, so a new sub-type needs no setting of its own
- 🔧 **The meeting list's completion filter works again** — it queried fields that had since moved onto votes and returned an error

### Remote events and vertical cards

- ⭐ **Mark an event as remote** — the event form now has a "Remote event" toggle. When on, the page shows a join link instead of an address and a map, and no location lookup is attempted
- ✨ **Vertical event cards** — the event list and an event page's "Other events" section now show vertical cards with the photo on top, instead of narrow rows
- ✨ **"Other events" moved below the description** — on an event page this section now sits in the main column, after the agenda and images, instead of the sidebar
- ⭐ **Previous/next meeting links for the same institution** — an event announcing a meeting now links to the nearest earlier and later published announcement for the same institution
- ⭐ **Announce in the calendar right from the meeting wizard** — a checkbox on the review step creates a draft calendar event alongside the meeting
- ✨ **A new item's start time is suggested automatically** — if the previous agenda item has an end time and the new one doesn't have its own yet, the start time is pre-filled from it (once, with no ongoing sync)

### Timetable content block

- ⭐ **New "Timetable" content block** — pages, news and the homepage can now embed a timetable card with times and titles. Rows can be entered manually or imported from a meeting's agenda

### Event page hero styles

- ⭐ **Pickable event page hero styles** — every event can now choose one of three hero layouts: a large card with the photo behind the text, a card with the photo beside the text, or a minimal layout with no photo at all. The style is picked on the event form
- ✨ **Calmer default event hero** — the hero is now compact, the title much smaller, and the actions (registration, sharing) keep a sensible layout on every screen. The full-bleed hero that broke the page's flow is gone
- 🔧 **Fixed event date texts** — long Lithuanian dates no longer clip in the date picker, and the "All-day event" switch has a consistently visible row

### Consistent admin cards

- ✨ **Settings cards now match the administration page style** — the settings index page now shows the same cards as the administration page: icon, title and description, with a subtle ring highlight on hover
- ✨ **Removed the featured "Problem" card** — the "Latest tools" section with the "New"-badged problem card no longer appears on the administration page; problems remain available under the "Representation" section
- 🔧 **Representation settings page is translated again** — a mismatch in the translation files caused raw translation keys to be shown; Lithuanian and English texts now display correctly

## v1.26 — Content editor images, the file manager and search filters (2026-08-28) {#v1-26}

### Images in the content editor

- 🔧 **Images can be inserted from the content editor again** — picking an image did nothing (the editor threw an error), so images could only be added by dragging a file onto the editor
- ⭐ **Image size can be dragged** — select an image and a handle appears on its right edge; the width in pixels is shown while dragging. The preset menu (300 / 500 / 800 px, full width) is still there for quick sizing
- 🔧 **The chosen width and alignment now show on the public page** — images used to be stretched to the full width regardless of the size picked, and left/right alignment was lost
- ✨ **A shorter image insert form** — the long blue block explaining alt text now sits behind a "Why is this required?" toggle, with a character counter next to the field (matching the edit dialog)
- ✨ **Images can be marked decorative when inserting** — alt text is then no longer required, matching the edit dialog
- ✨ **Image controls appear next to the image** — selecting one now raises alignment, size, alt text and remove buttons right above it, instead of far away in the toolbar
- 🔧 **Selecting an image no longer offers bold and underline** — the text formatting bubble is replaced by the image menu
- 🔧 **No more duplicate alignment buttons** — selecting an image used to show two alignment rows in the toolbar, only one of which worked

### File manager

- 🔧 **The file manager no longer crashes on `/mano/files`** — the page failed with "allowedTypes.extensions is undefined" and the upload panel froze on "Loading...". This also restores the supported-formats list, the 50 MB browser-side check, and the file-type filter in the picker
- 🔧 **The upload button no longer spins forever** — uploading through the editor's image dialog stored the file but left the button spinning, because another action on the page (an autosaving form, for instance) cancelled the upload mid-flight. Uploads no longer depend on page navigation and always finish
- ✨ **Folders no longer bury the files** — the ~50 folders in the root filled the entire first page, so files only started on page two. Folders now sit in their own collapsible strip with a filter, and the file grid starts immediately
- ⭐ **Search across all folders** — tick "In all folders" to search subfolders too, with each result showing which folder it lives in. Previously a file was only findable if you already knew which of ~50 folders held it
- ✨ **Folders and files are sorted by name** — the order used to come straight from the filesystem and looked random
- ✨ **A single click opens a folder** — it previously took a double click, and a single click did nothing
- 🔧 **Non-image files dropped into the editor download again** — they were written to the wrong location, so the inserted link returned a 404
- ⭐ **Hover preview in the file manager** — hovering a photo in the grid shows a larger version, so you no longer have to guess from a small square
- ✨ **The file grid loads much faster** — each tile used to download the full-size original (a folder of 50 photos meant hundreds of megabytes); the server now builds and caches downscaled copies, and off-screen images are only fetched once scrolled into view
- ✨ **The file manager speaks English on the English site** — its buttons and messages were hardcoded in Lithuanian (and in places English)

### Search filters on narrower screens

- ✨ **Search filters are collapsible at every screen width** — on 1024–1280 px screens the documents, meetings, contacts and global search pages used to show the filters expanded above the results with no way to collapse them; they now hide behind a filters button, just like on phones
- 🔧 **Fixed the filter chevron alignment** — the expand arrow on filter sections sat shifted toward the top instead of centered with the label, most visibly on sections with a description; it is now always vertically centered

## v1.25 — Hero carousel, English URLs and authorization clean-up (2026-08-20) {#v1-25}

- ⭐ **New hero carousel block** — page content can now open with rotating large photos, each carrying its own title, subtitle, description and buttons. The carousel is keyboard- and screen-reader-friendly, autoplay pauses on hover and turns off entirely when the device requests reduced motion
- ⭐ **The English site now has English URLs** — documents, search, contacts, student representatives and meetings are served from `/en/documents`, `/en/search`, `/en/contacts`, `/en/meetings` and so on. Old links redirect to the new ones, so saved links keep working
- ⭐ **New site settings page** — the privacy policy page can be picked separately for Lithuanian and English, and the cookie banner's link follows the visitor's language
- ✨ **Admin and form validation messages are translated** — some previously appeared in one language only, or as a raw key such as "messages.meeting.updated"
- ✨ **Trainings and Memberships removed** — both were left unfinished and without data since late 2024, so they are gone from the admin menu

## v1.24 — Ex-officio duties are granted again (2026-08-11) {#v1-24}

- 🔧 **Ex-officio members no longer disappear** 

## v1.23 — Calmer admin tables (2026-08-11) {#v1-23}

- ✨ **Fewer tooltips in tables** 
- ✨ **View and edit buttons are real links** 
- ✨ **The institution list shows the most recent meetings** — instead of the oldest ones
- ✨ **Search runs as you type** 
- ✨ **Filters collapse behind one button** 

## v1.22 — Member administration and draft updates (2026-08-10) {#v1-22}

- 🔧 More reliable user administration
- ⭐ **Duplicate warning when creating a member** — both the user form and the duty wizard now warn if a similar profile already exists, including in a unit you cannot see. In the wizard you can pick the existing profile instead of creating a second one
- ✨ **Clearer LT duty ending genderizing**
- 🔧 **Drafts and scheduled news/pages are findable in admin search again** 
- 🔧 **Scheduled news and pages appear in public search on time** 

## v1.21 — Navigation editing, ViSAK status trend, and RSS feed (2026-08-04) {#v1-21}

### ViSAK

- ⭐ **Institution status over time** 
- ✨ **Selectable range** — view the chart over 30, 90, or 180 days; hovering shows that day's breakdown of institution statuses

### Admin

- ⭐ **Editing and preview are now separate** — edit mode shows a clean, compact list with drag handles; preview mode shows exactly how the menu will look live
- ✨ **Order saves automatically** — drag links between columns or reorder main items, and the order is saved a couple of seconds later, no separate save button
- ⭐ **The language switcher no longer depends on your admin language** — the English menu can be edited without switching the whole admin interface to English; a banner also warns when one language has noticeably fewer items than the other
- ✨ **Cleaner navigation item form** — the essential fields show up front, with image and advanced settings tucked behind collapsible sections
- ⭐ **Faster link picking** — search for a page, news item, event, institution, or document instead of typing a URL by hand; the same picker was also added to the QuickLinks form
- 🔧 **The "show publicly" toggle now actually works** — it previously had no effect

### Public navigation

- ⭐ **More control over background images** — darkening strength, blur, focal point, and gradient direction are now configurable per item
- ⭐ **New navigation item options** — heading (a non-clickable section label), featured items, badge color, opening in a new tab, and image display as a card or a small thumbnail
- 🔧 **Fixed links not opening in a new tab** — this setting silently failed in some cases before

### News RSS feed

- ⭐ **Full article body in the feed** — the RSS news feed now carries the complete article (images included) instead of just the short excerpt, so the full text shows up in your reader
- ✨ **Images render in readers** — the cover photo is now delivered via `<enclosure>` and Media RSS tags, and every link and image in the feed is rewritten to an absolute URL
- 🔧 **Fixed the cover image URL** — a malformed URL previously prevented the cover photo from displaying in the feed
- ✨ **More metadata** — news tags are now exposed as `<category>`, a `<guid>` is set, along with the author email and a link to the other-language version

## v1.20 — Improved activity log (2026-08-02) {#v1-20}

- ⭐ **New activity log panel** — meetings, institutions, duties, problems, reservations, trainings, and other content pages now show a full change history, including changes to related items (e.g. a meeting's history also shows changes to its agenda items and votes)
- ✨ **Text changes shown as a diff** — news, page content block, and problem description edits in the activity log now highlight which words changed instead of showing two identical-looking truncated excerpts
- 🔧 **Some changes were previously not recorded** — every supported model's changes are now reliably logged
- ✨ **Clearer change display** — dates, statuses, and related records (e.g. the responsible person) now show readable names instead of raw data
- 🔧 **Fixed resource management**
- 🔧 **Tried to fix a case of uploading Sharepoint documents**

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

### Page and news permalink management

- ⭐ **Page permalinks can now be edited after creation** — previously the permalink was generated once and could never be changed

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
