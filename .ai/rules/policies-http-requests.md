---
paths:
  - 'app/Http/Controllers/Admin/UserController.php,app/Policies/UserPolicy.php,app/Http/Requests/UpdateUserRequest.php'
---

# Policies Http Requests

## User administration follows duty history
Tenant-scoped user administration reaches people with a past, current, or scheduled duty in an authorized tenant; never include or authorize dutyless, roleless users through the general user index/editor. Keep global candidate discovery and assignment in the duty-user wizard. Existing names are super-admin-only; duty changes remain limited to the actor's authorized tenants.
