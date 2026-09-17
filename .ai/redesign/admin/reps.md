# Student representatives — the main group

> **Admin redesign.** Read when: you are touching anything a rep uses: Pradžia, meetings, agenda items, tasks, reminders.
> Index: [README.md](README.md) · Messages: [messages.md](messages.md)

## Student representatives — the main group

### What the data says (2026-09-17)

| Measure | Value |
|---|---|
| Users holding an active *Studentų atstovas* duty | 307 |
| … active in the last 7 / 30 / 90 days (`users.last_action`, tracked since 2023) | 44 (14%) / 83 (27%) / 128 (42%) |
| … never | **95 (31%)** |
| Past meetings, last 12 months | 634 |
| … recorded before the meeting / ≤ 24h after / ≤ 7 days / ≤ 30 days / **> 30 days after** | 27% / 7% / 14% / 17% / **35%** |
| … with no agenda at all | 87 (14%) |
| Agenda items without any vote information | **1,115 of 2,065 (54%)** |
| People who recorded a meeting (activity log) | 123; the top 10 recorded 40% |
| *Užpildyti darbotvarkę* tasks (agenda completion) | 73% completed, avg 4.1 days |
| *Periodicity gap* tasks ("no meeting recorded lately") | **45% completed, avg 40 days**, 219 open overdue |
| Reservation approval / pickup / return tasks | 99% / 80% / 88% completed, 4–5 days |

Also: `config/session.php` sets `expire_on_close => true`, Microsoft login sends
`prompt=select_account`, and `Auth::login()` is called without "remember". Unless production
overrides it, **every visit from an email link starts with a Microsoft round trip and an account
picker.** The `sessions` table is stale (Redis driver), so nobody knows the phone/desktop split.

Caveat: September is the start of the academic year; summer inactivity inflates the 7/30-day
numbers. The recording delay and missing votes are year-round.

### What it means

The rep's problem is **not navigation**. It is the *obligation loop*: a meeting happens offline; the
app has to get the record — agenda, how students voted, the outcome — out of the rep's head while it
is still there. Today a third of meetings are recorded a month or more later (often in batches, often
by someone else), half the agenda items carry no vote information, and the "no meeting lately" nudge
is mostly ignored. A beautiful shell does not move those numbers; a shorter loop does.

### What a rep expects

1. **"Tell me when I have to do something, and let me do it right there."** Email/push → one tap
   → the exact screen, already logged in.
2. **"Recording should take the time of a coffee."** Agenda items from the invite or a photo/paste,
   votes as big taps (už / prieš / susilaikė / nebalsuota), outcome, done.
3. **"Show me that it mattered."** Their record appears on the public meeting page; their unit sees it.
4. **"Don't make me learn an admin system."** Two or three tabs, one button, no jargon.
5. **"Who do I ask?"** Their coordinator, visible, one tap away.

### Principles for rep-facing screens

- **R-a. Answerable notifications.** Every reminder carries its answer as buttons: *"Ar vyko
  posėdis nuo 09-01?"* → **Taip, fiksuoti** / **Ne, nevyko**. Each deep-links into the ActionWindow
  at the right screen with the institution pre-filled.
- **R-b. Staying logged in.** Remember-me on Microsoft login, no forced account picker for a
  returning user, `intended` redirect preserved through login (it already is).
- **R-c. Record in minutes, complete later.** A meeting can be saved with just institution + date;
  what is missing becomes a visible, one-tap "Papildyk" list — never a blocker.
- **R-d. Paste intake.** Agenda items from pasted invitation text into editable rows (the bulk
  agenda form already parses lines). A photo of the invitation is a later idea, only if cheap.
- **R-e. Votes as taps.** Large segmented controls per agenda item on the phone, one item per
  screen with ‹ › (the focused editor pattern).
- **R-f. Visible impact.** After recording: "Matoma vusa.lt" + link to the public meeting page;
  on Pradžia, a quiet line such as "Šiais metais užfiksavai 6 posėdžius".
- **R-g. A named coordinator.** Pradžia and every rep screen show "Tavo koordinatorius: Vardas
  Pavardė" with a contact action — the human answer to "I'm stuck".
- **R-h. Rep home is small.** Attention (tasks) → upcoming meetings → my institutions. Nothing else
  unless it applies.

### Measuring the rep experience

Outcome metrics computed **from data the app already stores** — no tracking scripts, no personal
browsing data. A small report (command or Sistema page) with a monthly trend:

| Metric | Source | Why |
|---|---|---|
| Meeting recorded within 7 days (%) | `meetings.start_time` vs `created_at` | the core loop's speed |
| Agenda items with vote information (%) | `votes` | completeness |
| Task completion rate + median days, per action type | `tasks` | are nudges working |
| Reps active in the last 30 days (%) | `users.last_action` + duties | reach |
| Share recorded by the rep vs by someone else | `activity_log` causer vs duty holder | ownership |

Plus **live sessions** (U16 is replaced by these): each pilot wave is tried by 3–5 reps on their own
phones, with one realistic task ("užfiksuok vakarykštį posėdį") while someone watches and takes notes.

### Light usage analytics — include or not?

- **No clickstream analytics in the admin** (no Umami script, no event tracking). Five metrics
  from existing data answer the questions that matter, and nobody has to explain tracking
  inside an internal work tool.
- **One exception, settled (U26): a coarse device split — server-side, not Umami** (Umami stays
  public-only). A listener on the `Login` event increments a daily counter per device class
  (phone / tablet / desktop, parsed from the user agent), and PWA launches are counted via a marker on
  the manifest `start_url`. **No user id is stored.** Shown as a small table in Sistema. It answers the
  one question the database cannot, and D11's phone/tablet priorities depend on it.
