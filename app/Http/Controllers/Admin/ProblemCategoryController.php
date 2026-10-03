<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreProblemCategoryRequest;
use App\Http\Requests\UpdateProblemCategoryRequest;
use App\Models\ProblemCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Response;

class ProblemCategoryController extends AdminController
{
    public function index(Request $request): Response
    {
        $this->handleAuthorization('viewAny', ProblemCategory::class);

        return $this->inertiaResponse('Admin/Problems/IndexProblemCategory', [
            'problemCategories' => ProblemCategory::query()
                // Trashed problems count too: restoring one would bring its categories back.
                ->withCount(['problems' => fn ($query) => $query->withTrashed()])
                ->orderBy('slug')
                ->get()
                ->map(fn (ProblemCategory $category): array => [
                    ...$category->toFullArray(),
                    'problems_count' => $category->problems_count,
                ])
                ->values(),
            'abilities' => [
                'delete' => $request->user()->can('delete', new ProblemCategory),
            ],
        ]);
    }

    public function store(StoreProblemCategoryRequest $request): RedirectResponse
    {
        ProblemCategory::create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug($request->validated('name.lt')),
        ]);

        return back()->with('success', $this->entityMessage('created', 'problemCategory'));
    }

    public function update(UpdateProblemCategoryRequest $request, ProblemCategory $problemCategory): RedirectResponse
    {
        // The slug stays put: it is the stable handle seeders and filters use.
        $problemCategory->update($request->validated());

        return back()->with('success', $this->entityMessage('updated', 'problemCategory'));
    }

    /**
     * Refused while in use: the pivot cascades, so deleting would silently untag problems.
     */
    public function destroy(ProblemCategory $problemCategory): RedirectResponse
    {
        $this->handleAuthorization('delete', $problemCategory);

        $problemsCount = $problemCategory->problems()->withTrashed()->count();

        if ($problemsCount > 0) {
            return back()->with('error', trans_choice('problems.categories.in_use', $problemsCount, ['count' => $problemsCount]));
        }

        $problemCategory->delete();

        return back()->with('success', $this->entityMessage('deleted', 'problemCategory'));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'kategorija';
        $slug = $base;

        for ($suffix = 2; ProblemCategory::query()->where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
