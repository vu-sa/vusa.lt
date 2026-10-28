---
paths:
  - 'resources/js/Components/AdminForms/**'
---

# Admin Forms

## Admin forms edit attributes; relations live on the record
- A form edits the record's own attributes. Composition (parts authored with the parent: form fields, navigation items, vote options) is edited in the parent's editor. Associations (person ↔ duty, duty ↔ institution, institution ↔ secretaries, user ↔ roles) are managed on the record page, never in a form. No `TransferList` in forms; people are added with the Priskirti sheet (`AssignDutyUserSheet`).
- Create asks only what the record needs to exist. Avoid artificial filler sections like "Kas tai?" for primary attributes; group distinct field subsets into natural sections (e.g. "Kur tai rodoma?"), with at most ~6 fields each. Rare fields go under "Papildomi nustatymai". Single column; two fields side by side only for real pairs.
- Where text goes: label 1–4 words; hint under the field (1 line, only when there is genuine guidance, constraint, or non-obvious consequence — never a tautology restating the label); section intro above the fields (1 sentence); background goes in a "Plačiau" disclosure; a consequence that applies now goes in a callout at the field. Never a placeholder as the only hint. Omit `lead` on `FormPage` if the page title is already self-explanatory.
- Controls fit the choice: 2–5 options → visible radio/segmented; a switch applies immediately (or inside `FormToggleRow` in the `#aside` column); a checkbox saves with the form.
- Server-only rules go through Precognition (`useForm().withPrecognition()`, validate on change/blur); the Form Request is the one source of validation.
- Save bar holds status + Išsaugoti; delete lives in ⋯ or a final "Pavojinga zona"; no autosave switch. Form-level LT | EN switch (do not append "(LT)" or "(EN)" suffixes to field labels when the form has a form-level switch). Fields shown publicly carry "Matoma vusa.lt".
- `FormPanel` heading defaults to `text-brand` (no manual `title-class="text-brand"`). `FormPanel` is primarily for the `#aside` column, but is also allowed in the main canvas for framed, titled panel groups (e.g. notification categories).
- Relations live on the record page, never in a form (e.g. no associated news lists in `TagForm`). Exception: `UserForm` on creation requires selecting at least one initial duty because user profiles cannot exist without an association.
- Order is a mode: "Keisti tvarką" with ↑/↓ buttons (plus drag), then Išsaugoti tvarką / Atšaukti. Never always-on drag.

## Use field hints and one required-field note
Put concise field guidance in FormFieldWrapper’s `hint` prop (`helperText` is deprecated); do not add hints that merely repeat the label, turn field hints into standalone lead paragraphs, or repeat context in section headings, badges and descriptions. Mark required fields with an asterisk and show one translated note near the start via `FormPage`'s `requiredNote` prop (key `forms.required_note`: “Žvaigždute (*) pažymėti laukai yra privalomi; kitus gali praleisti.”). Only show the note on forms having both required and optional fields. Never append “(neprivaloma)” or "(optional)" to optional field labels or placeholders.

## Rare optional form sections start as dashed fill-in buttons
A rarely used, optional field group (e.g. DutyForm's former names, "Padaliniai, galintys skirti atstovus") is not rendered while empty. Instead it is offered as a tier-3 fill-in at the end of the main column: `FormFillActions` (Patterns) renders `FillActionButton`s, the same dashed `border-muted-foreground/50`, muted, `+`/icon button as `RecordNextActions`' fill tier on records (ShowAgendaItem). Clicking reveals the section and focuses its first control; once revealed it stays until save even if emptied, and an existing value always shows the section. Read-only viewers get no buttons. Never gate such a group with an "enable" checkbox. Reference: `DutyForm.vue`.
