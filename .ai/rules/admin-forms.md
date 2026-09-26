---
paths:
  - 'resources/js/Components/AdminForms/**'
---

# Admin Forms

## Admin forms edit attributes; relations live on the record
- A form edits the record's own attributes. Composition (parts authored with the parent: form fields, navigation items, vote options) is edited in the parent's editor. Associations (person ↔ duty, duty ↔ institution, institution ↔ secretaries, user ↔ roles) are managed on the record page, never in a form. No `TransferList` in forms; people are added with the Priskirti sheet (`AssignDutyUserSheet`).
- Create asks only what the record needs to exist. Sections are questions ("Kas tai?", "Kur tai rodoma?"), with at most ~6 fields each. Rare fields go under "Papildomi nustatymai". Single column; two fields side by side only for real pairs.
- Where text goes: label 1–4 words; hint under the field (1 line); section intro above the fields (1 sentence); background goes in a "Plačiau" disclosure; a consequence that applies now goes in a callout at the field. Never a placeholder as the only hint.
- If most fields are required, mark the optional ones "(neprivaloma)". Controls fit the choice: 2–5 options → visible radio/segmented; a switch applies immediately; a checkbox saves with the form.
- Server-only rules go through Precognition (`useForm().withPrecognition()`, validate on change/blur); the Form Request is the one source of validation.
- Save bar holds status + Išsaugoti; delete lives in ⋯ or a final "Pavojinga zona"; no autosave switch. Form-level LT | EN switch. Fields shown publicly carry "Matoma vusa.lt".
- Order is a mode: "Keisti tvarką" with ↑/↓ buttons (plus drag), then Išsaugoti tvarką / Atšaukti. Never always-on drag.
