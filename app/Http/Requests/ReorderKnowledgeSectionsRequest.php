<?php

namespace App\Http\Requests;

use App\Models\KnowledgeSection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class ReorderKnowledgeSectionsRequest extends FormRequest
{
    /** @var Collection<int, KnowledgeSection>|null */
    private ?Collection $resolvedSections = null;

    /** Every section whose position is sent must be one the user may update. */
    public function authorize(): bool
    {
        $sections = $this->sections();

        return $sections->isNotEmpty()
            && $sections->count() === collect($this->input('sections', []))->pluck('id')->unique()->count()
            && $sections->every(fn (KnowledgeSection $section): bool => $this->user()->can('update', $section));
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'sections' => ['required', 'array', 'max:200'],
            'sections.*.id' => ['required', 'string', 'distinct'],
            'sections.*.order' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return Collection<int, KnowledgeSection> */
    public function sections(): Collection
    {
        $ids = collect($this->input('sections', []))->pluck('id')->filter(fn (mixed $id): bool => is_string($id))->unique()->values();

        return $this->resolvedSections ??= KnowledgeSection::query()->whereKey($ids)->get();
    }
}
