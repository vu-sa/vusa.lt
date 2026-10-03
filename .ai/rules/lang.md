---
paths:
  - 'lang/**'
---

# Lang

## Flash messages go through entityMessage(); Lithuanian gender lives in entities.php
Controllers never hold user-facing sentences. CRUD flashes come from `$this->entityMessage('created', 'news')` (trait `App\Http\Traits\TranslatesEntityMessages`, on AdminController and ApiController); anything else is a key in `lang/admin/{lt,en}/`.

- Lithuanian participles agree with gender, so `messages.{created,updated,deleted,restored}` have `f`/`m` variants and every entity in `entities.php` declares `gender`. The old trick of passing 0/1 as trans_choice's count is gone — never reintroduce a magic number. Same split for `forms.new_model` / `forms.edit_model`, used from `newEntityTitle()` / `editEntityTitle()` in `@/Utils/EntityMessages`.
- entities.php keys are camelCase (`studyProgram`, `resourceCategory`) and must match the `entityName` constant of the matching admin index page.
- Bilingual `locale === 'lt' ? '…' : '…'` ternaries in Vue are the phase-out target: extract the Lithuanian text as the `$t()` key and add the English value to `lang/en.json` (that file is Lithuanian-keyed).
- TranslationIntegrityTest and AdminEntityTranslationsTest enforce lt/en key parity, that every referenced key resolves, and that genders resolve.

## Admin voice and glossary
- Address the user as *tu* ("Pataisyk klaidas"). Buttons are verbs naming the result ("Fiksuoti posėdį", "Išsaugoti"). Source keys are Lithuanian.
- Glossary (retired words in brackets): posėdis [susitikimas]; renginys [įvykis]; narys for a person record [vartotojas, except in Sistema/auth contexts where an account is meant]; išteklius [resursas]; sekretorius = `InstitutionSecretary`, the per-term nominee who records; koordinatorius = `Institution::managers()`, who reps ask for help.
- ViSAK names the representation workspace, never the product. The product is **Mano VU SA**.
- Email-type meetings read "sprendimas el. paštu", not posėdis.
- Role names are data referenced by string in seeders and tests, so renaming one needs a migration plus a grep, not just a lang edit.
