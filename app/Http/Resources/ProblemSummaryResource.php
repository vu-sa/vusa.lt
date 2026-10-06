<?php

namespace App\Http\Resources;

use App\Models\Problem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A problem as a line on another record (institution tab, agenda item): enough to recognise and open it.
 *
 * @mixin Problem
 */
class ProblemSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'occurred_at' => $this->occurred_at->toDateString(),
            'resolved_at' => $this->resolved_at?->toDateString(),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant->only(['id', 'shortname'])),
            'responsible_user' => $this->whenLoaded('responsibleUser', fn () => $this->responsibleUser?->only(['id', 'name'])),
        ];
    }
}
