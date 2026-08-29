---
paths:
  - 'resources/js/Pages/Admin/People/Show*.vue,app/Http/Middleware/HandleInertiaRequests.php'
---

# Middleware

## auth.can holds no flat permission names — pass per-record capabilities
`HandleInertiaRequests` exposes only `auth.can.{index,create,forceDelete,manageSettings,accessAdministration}`. A lookup like `page.props.auth?.can?.['duties.update.padalinys']` is always `undefined`, so any button gated on it renders for nobody — this silently hid "Valdyti", "Priskirti narį" and "Redaguoti" on ShowDuty/ShowUser.

Never add flat permission names to that map either: `*.padalinys` permissions are tenant-scoped, so one global boolean is wrong for every cross-tenant case. Instead have the `show()` controller pass a `can` prop computed from the policy for that record (`DutyController::show`, `UserController::show`), and read `props.can.*` in the page.
