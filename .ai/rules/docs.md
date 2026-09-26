---
paths:
  - 'docs/**'
---

# Docs

## Guide pages name roles, not permissions
In "Kas ką gali" and the rest of a guide page, say which role allows an action (the Lithuanian production name, e.g. „Komunikacijos koordinatorius“, „Studentų atstovų koordinatorius“), never a permission string like `duties.update.padalinys`. Permission strings appear only under "Techninė informacija". Check each named role against database/seeders/Role*Seeder.php; if a role the page names has no seeder, add one mirroring production so tests exercise the same role.
