---
paths:
  - 'app/Actions/**'
  - 'app/Services/**'
  - 'app/Support/**'
  - 'app/ValueObjects/**'
  - 'app/Helpers/**'
---

# Where backend code lives

Pick the folder by what the class *is*, not by what its neighbours are called. Group by domain
inside each folder (`Actions/Dutiables/`, `Services/Typesense/`, `Support/Permissions/`).

## Action: `app/Actions/<Domain>/`
It changes state, or it answers one named question (`Get*`, `Resolve*`, `Find*`, `Build*`).
- Default shape: `public static function execute(...)`, with every input as an explicit argument.
  Several named static methods are fine when they answer the same question for different subjects
  (`ResolveTaskAssignees::forMeeting()` / `forInstitution()`).
- If it needs an injected collaborator (`MailmanClient`, an upload writer), it is resolved from the
  container and exposes an instance `execute()`. Don't turn it into a Service just because of that.
- It takes the acting `User` as an argument and never a Request, so a command, job or test can call
  it. `tests/Feature/System/BackendLayoutConventionTest.php` ratchets the remaining exceptions.

## Service: `app/Services/<Domain>/`
It is injected and holds a dependency, a client or a cache: `ModelAuthorizer`, `UmamiClient`,
`InstitutionRelationService`. Two static families also live here:
- `*Presenter`: page payloads (`payloads.md`).
- `*Queries`: a model's read queries for one actor, e.g. `InstitutionQueries`.

A model whose visibility is more than tenant plus permission gets one `*AccessService` (`services.md`).
A `*Resolver` or `*Finder` belongs here only if it holds a dependency or a cache. Otherwise it is
an Action, and it moves the next time you touch it.

## Support: `app/Support/<Domain>/`
Value objects, static registries and pure helpers: `MorphMap`, `PermissionCatalog`, `Support/Dutiables/*`.
`app/ValueObjects/` and `app/Helpers/` are frozen. New classes go to `Support/`, and old ones move when touched.

## The actor is read at the boundary
Controllers, Form Requests and middleware read the current user. Policies receive it. Everything
below them takes `User $actor` next to `ModelAuthorizer`. This is the reason the authorizer
takes the user explicitly (`services.md`).

## Move on touch
When you edit a top-level `app/Services/*.php` that belongs to a domain (Duties, Institutions,
Meetings, Files, Sharepoint), move it into that domain's folder in the same change, and fix any rule glob naming its old path
(`tests/Feature/System/RuleReferencesTest.php` fails on a glob matching no file).

"Resource" names `App\Models\Resource`, API resources and the first permission segment; don't use
it for a folder.

Elsewhere: `app/Tasks/README.md` (its own subsystem), `audiences.md` (who receives what).
