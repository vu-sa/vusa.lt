<?php

namespace App\Http\Resources;

use App\Models\Step;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * A goals-pilot step on an admin record (goal, problem, agenda item). A referenced agenda item or
 * document is only described to someone who may open it; the others see that the step has none.
 *
 * Expects `createdBy`, `performers`, `agendaItem.meeting.institutions`, `document`, `goal` and
 * `problem` to be loaded — see self::RELATIONS.
 *
 * @mixin Step
 */
class StepResource extends JsonResource
{
    public const array RELATIONS = [
        'createdBy:id,name',
        'performers:id,name',
        'agendaItem.meeting.institutions:id,name',
        'document:id,title,anonymous_url',
        'goal:id,title',
        'problem:id,title',
    ];

    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $agendaItem = $this->agendaItem;
        $meeting = $agendaItem?->meeting;
        $document = $this->document;

        return [
            'id' => $this->id,
            'title' => $this->getTranslations('title'),
            'description' => $this->getTranslations('description'),
            'happened_on' => $this->happened_on->toDateString(),
            'goal_id' => $this->goal_id,
            'problem_id' => $this->problem_id,
            'url' => $this->url,
            // Ids stay even when the reference is hidden, so editing the step does not drop it.
            'agenda_item_id' => $this->agenda_item_id,
            'document_id' => $this->document_id,
            'goal' => $this->goal?->only(['id', 'title']),
            'problem' => $this->problem?->only(['id', 'title']),
            'recorder' => $this->createdBy?->only(['id', 'name']),
            'performers' => $this->performers->map(fn ($user) => $user->only(['id', 'name']))->values(),
            'agenda_item' => $agendaItem !== null && $meeting !== null && Gate::allows('viewSummary', $agendaItem) ? [
                'id' => $agendaItem->id,
                'title' => $agendaItem->title,
                'start_time' => $meeting->start_time->toISOString(),
                'institutions' => $meeting->institutions->pluck('name')->values(),
            ] : null,
            'document' => $document !== null && Gate::allows('view', $document) ? $document->only(['id', 'title']) : null,
        ];
    }
}
