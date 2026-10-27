---
paths:
  - 'resources/js/Components/AdminForms/**'
---

# Admin Forms

## Admin forms edit attributes; relations live on the record
- A form edits the record's own attributes. Composition (parts authored with the parent: form fields, navigation items, vote options) is edited in the parent's editor. Associations (person ↔ duty, duty ↔ institution, institution ↔ secretaries, user ↔ roles) are managed on the record page, never in a form. No `TransferList` in forms; people are added with the Priskirti sheet (`AssignDutyUserSheet`).
- Create asks only what the record needs to exist. Sections are questions ("Kas tai?", "Kur tai rodoma?"), with at most ~6 fields each. Rare fields go under "Papildomi nustatymai". Single column; two fields side by side only for real pairs.
- Where text goes: label 1–4 words; hint under the field (1 line); section intro above the fields (1 sentence); background goes in a "Plačiau" disclosure; a consequence that applies now goes in a callout at the field. Never a placeholder as the only hint.
- Controls fit the choice: 2–5 options → visible radio/segmented; a switch applies immediately; a checkbox saves with the form.
- Server-only rules go through Precognition (`useForm().withPrecognition()`, validate on change/blur); the Form Request is the one source of validation.
- Save bar holds status + Išsaugoti; delete lives in ⋯ or a final "Pavojinga zona"; no autosave switch. Form-level LT | EN switch. Fields shown publicly carry "Matoma vusa.lt".
- Order is a mode: "Keisti tvarką" with ↑/↓ buttons (plus drag), then Išsaugoti tvarką / Atšaukti. Never always-on drag.

## Use field hints and one required-field note
Put concise field guidance in FormFieldWrapper’s `hint` prop, as in PageForm.vue; do not turn field hints into standalone lead paragraphs or repeat context in section headings, badges and descriptions. Mark required fields with an asterisk and show one translated note near the start: “Žvaigždute (*) pažymėti laukai yra privalomi; kitus gali praleisti.” Do not append “(neprivaloma)” to optional field labels. Follow ProblemForm.vue only for the required-field note styling.

## Rare optional form sections start as dashed fill-in buttons
A rarely used, optional field group (e.g. DutyForm's former names, "Padaliniai, galintys skirti atstovus") is not rendered while empty. Instead it is offered as a tier-3 fill-in at the end of the main column: `FormFillActions` (Patterns) renders `FillActionButton`s, the same dashed `border-muted-foreground/50`, muted, `+`/icon button as `RecordNextActions`' fill tier on records (ShowAgendaItem). Clicking reveals the section and focuses its first control; once revealed it stays until save even if emptied, and an existing value always shows the section. Read-only viewers get no buttons. Never gate such a group with an "enable" checkbox. Reference: `DutyForm.vue`.
