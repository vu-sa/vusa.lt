---
paths:
  - 'resources/js/Pages/Admin/Settings/**, app/Settings/**'
---

# Settings

## Search-backed pickers are tenant-scoped for non-super-admins
`CollectionSelectDialog` / Typesense selectors inherit the user's scoped API key: non-super-admins only see records from their own tenant(s), even for global settings (e.g. the privacy-page pickers in EditSiteSettings). Super admins (the default settings managers) see everything. If a setting ever must offer cross-tenant records to a non-super-admin role, that needs an explicit unscoped key decision — don't assume the old "list all rows in a dropdown" behavior.
