<?php

namespace App\Http\Requests\Meetings;

use App\Models\Document;
use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Link a nutarimas / protokolas to the meeting that produced it.
 */
class StoreMeetingDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->can('update', $this->meeting())
            && $user->can('update', $this->resolveDocument());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document_id' => ['required', 'integer'],
        ];
    }

    /**
     * Resolved through the meeting's own institutions — or any institution of their tenants.
     * Deliberately lax: internal bodies (Parliament, Board) have their paperwork filed under
     * the central institution of the same tenant, not under the body itself.
     */
    public function resolveDocument(): Document
    {
        $meeting = $this->meeting();
        $meeting->loadMissing('institutions');

        $institutionIds = $meeting->institutions->pluck('id');
        $tenantIds = $meeting->institutions->pluck('tenant_id')->filter()->unique();

        $document = Document::query()
            ->where(function (Builder $query) use ($institutionIds, $tenantIds): void {
                $query->whereIn('institution_id', $institutionIds)
                    ->orWhereHas('institution', fn ($institution) => $institution->whereIn('tenant_id', $tenantIds));
            })
            ->find($this->input('document_id'));

        abort_if($document === null, 403, 'Document does not belong to this meeting\'s institutions or tenants.');

        return $document;
    }

    public function meeting(): Meeting
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $meeting;
    }
}
