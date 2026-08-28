# Project Rules Index

Before planning or editing, read every row whose globs match the file's path. Enforced rules live
in `.ai/rules/*.md`; rows pointing at a `CLAUDE.md` are reference/convention docs for that area —
both are worth reading, and a path can match more than one row.

| Applies to | Rule / reference file |
| --- | --- |
| app/Services/Typesense/**, app/Console/Commands/GenerateTypesenseSearchKey.php | .ai/rules/commands.md |
| app/Console/Commands/DocsCoverageCommand.php, app/Support/Docs/** | .ai/rules/commands.md |
| app/Models/Cadence.php,app/Policies/CadencePolicy.php,app/Actions/Cadences/**,app/Http/Requests/Cadences/**,resources/js/Components/Cadences/** | .ai/rules/cadences.md |
| config/vusa.php | .ai/rules/config.md |
| app/Http/Controllers/** | .ai/rules/controllers.md |
| app/Enums/** | .ai/rules/enums.md |
| resources/js/**/*.test.ts | .ai/rules/js.md |
| resources/js/**/__tests__/** | resources/js/CLAUDE.md |
| resources/js/Components/** | resources/js/Components/CLAUDE.md |
| resources/js/Components/Tables/** | resources/js/Components/Tables/CLAUDE.md |
| resources/js/Composables/** | resources/js/Composables/CLAUDE.md |
| .storybook/** | .storybook/CLAUDE.md |
| lang/** | .ai/rules/lang.md |
| resources/js/Pages/Admin/People/Show*.vue,app/Http/Middleware/HandleInertiaRequests.php | .ai/rules/middleware.md |
| app/Models/**, app/Models/Tenant.php, app/Models/Calendar.php | .ai/rules/models.md |
| app/Providers/** | .ai/rules/providers.md |
| app/Http/Requests/** | .ai/rules/requests.md |
| routes/api.php | .ai/rules/routes.md |
| app/Services/** | .ai/rules/services.md |
| resources/js/Pages/Admin/Settings/**, app/Settings/** | .ai/rules/settings.md |
| app/Support/MorphMap.php, app/Support/LocalizedRouteSlugs.php | .ai/rules/support.md |
| tests/** | .ai/rules/tests.md |
| tests/** | tests/CLAUDE.md |
