# Controllers

The enforced rules live in [.ai/rules/controllers.md](../../../.ai/rules/controllers.md): validation in Form Requests, reading `validated()`/`safe()` instead of raw input, check ordering, index-listing helpers and route-segment allowlists. This file shows the shape those rules produce. `Admin/NewsController.php` is the reference controller.

## The shape of an admin controller

```php
class NewsController extends AdminController
{
    use HandlesSoftDeletes;

    #[Authorize('viewAny', News::class)]
    public function index(IndexNewsRequest $request) { … }

    #[Authorize('create', News::class)]
    public function store(StoreNewsRequest $request) { … }

    #[Authorize('update', 'news')]
    public function update(UpdateNewsRequest $request, News $news) { … }

    #[Authorize('restore', 'news')]
    public function restore(News $news): RedirectResponse
    {
        return $this->restoreModel($news);
    }

    // Authorized in BulkDestroyNewsRequest: every picked article.
    public function bulkDestroy(BulkDestroyNewsRequest $request): RedirectResponse { … }
}
```

- **Every method that reads input type-hints a Form Request**, never `Illuminate\Http\Request`. A plain `Request` is fine only for `->user()`, `->session()` and headers. `ValidationConventionTest` enforces this.
- **Index requests extend `BaseIndexRequest`**. Read paging through its helpers: `getPerPage()`, `getShowDeleted()`, `getSorting()`, `getFilters()`.
- **Soft-deletable models** register their trash routes with `Route::softDeletes()` and delegate to `HandlesSoftDeletes`. `SoftDeleteRoutesConventionTest` checks both.

## Where the authorization check lives

There are exactly three places, and a reader should find the check in the first place they look:

1. **`#[Authorize]` on the method.** Use it for any check on a class or a route-bound model.
   - The second argument is a class (`News::class`), a **route parameter name** (`'news'`), or both (`[Comment::class, 'post']`).
   - It runs as `can` middleware, before the Form Request validates, so an unauthorized caller gets 403 and never 422.
2. **The Form Request's `authorize()`.** Use it only when the check needs the request body: ids in the payload, several records at once, or a merge target.
   - The controller method then says where to look: `// Authorized in MergeTagsRequest: the target and every source tag.`
   - Never leave an `authorize()` that only does `return true`: a Form Request without one is allowed by default.
3. **`$this->authorize()` in the body.** Use it only when the subject is computed there, such as a relation, a record looked up from input, or a second check after a branch.

`RouteAuthorizationCoverageTest` checks for authorization declarations and calls on admin routes. Use feature tests to verify the policy covers the affected record, the requested destination and every child.

Policies extend `ModelPolicy` and resolve permissions through `ModelAuthorizer` (see `.ai/rules/app.md`). A refused check renders a 403 page on a direct visit, a 302 with an `error` flash on an Inertia request, and 403 JSON on the API (see "Authorization responses" in `AGENTS.md`).

## Authorize the destination and save related changes together

- Refuse before the first write where practical. Save multi-step changes in one transaction. Write files outside the database last and allow orphaned files if the transaction rolls back.
- A class-level create policy grants the ability to create somewhere. `StoreDutyRequest` checks whether the selected institution belongs to a tenant where the actor can create duties. `DutyInstitutionScopeTest` exercises both allowed and refused destinations.
- `FormController::update()` checks the policy for the bound form, and `UpdateFormRequest` checks the selected tenant. The controller rechecks the locked form and resolves submitted field IDs through its own relation.
- Put the form save and all field writes in one transaction. `FormControllerTest` checks that a refused edit preserves the form, its fields, and saved responses.
- When a page and the API both create a record, both call one action (`App\Actions\Duties\CreateDuty`, `App\Actions\Institutions\CreateInstitution`) and keep only the response. The "create the same" tests send one payload to both.
