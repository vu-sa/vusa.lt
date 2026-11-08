<?php

namespace App\Services;

use App\Enums\AgendaItemType;
use App\Enums\MeetingKind;
use App\Enums\MeetingType;
use App\Enums\TenantType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ResourceServices\SharepointFileService;
use App\Support\MeetingTitle;
use Closure;

class MeetingPresenter
{
    /** @return array{id: string, title: string, meeting_kind: MeetingKind|null, type: MeetingType|null, type_label: string|null, start_time: string, end_time: string|null, created_at: string, updated_at: string, description: array{lt: string, en: string}, title_noun: string, inherited_meeting_kind: MeetingKind, agenda_items: list<array{id: string, order: int, is_private: true, is_redacted: true, title: string, can: array{update: bool, delete: bool}}|array{id: string, order: int, brought_by_students: bool, type: AgendaItemType|null, start_time: string|null, end_time: string|null, title: string|null, is_private: bool, votes: list<array{is_main: bool, is_consensus: bool, student_vote: string|null, decision: string|null, student_benefit: string|null}>, comments_count: int, has_notes: bool, can: array{update: bool, delete: bool}}>, sharepointPath: string|null, sharepointFolderUrl: string|null, institutions: list<array{id: string, name: string|null, is_active: bool, tenant_id: int|null, tenant: array{shortname: string}|null}>, has_calendar_event: bool, has_protocol: bool} */
    public static function forShow(Meeting $meeting, User $user, bool $readOnly, Closure $agendaItemAbilities): array
    {
        $agendaItems = [];
        foreach ($meeting->agendaItems as $item) {
            $agendaItems[] = self::agendaItem($item, $user, $agendaItemAbilities);
        }

        $institutions = [];
        foreach ($meeting->institutions as $institution) {
            $institutions[] = [
                'id' => $institution->id,
                'name' => $institution->getRawOriginal('name') === null ? null : (string) $institution->name,
                'is_active' => (bool) $institution->is_active,
                'tenant_id' => $institution->tenant_id,
                'tenant' => $institution->tenant === null ? null : ['shortname' => $institution->tenant->shortname],
            ];
        }

        $description = $meeting->getTranslations('description');

        return [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'meeting_kind' => $meeting->meeting_kind,
            'type' => $meeting->type,
            'type_label' => $meeting->type?->label(app()->getLocale()),
            'start_time' => $meeting->start_time->toJSON(),
            'end_time' => $meeting->end_time?->toJSON(),
            'created_at' => $meeting->created_at->toJSON(),
            'updated_at' => $meeting->updated_at->toJSON(),
            'description' => ['lt' => $description['lt'] ?? '', 'en' => $description['en'] ?? ''],
            'title_noun' => MeetingTitle::noun($meeting->type, app()->getLocale(), $meeting->meetingKind(), joint: $meeting->is_joint),
            'inherited_meeting_kind' => $meeting->institutions->first()?->resolvedMeetingKind() ?? MeetingKind::Sitting,
            'agenda_items' => $agendaItems,
            'institutions' => $institutions,
            'has_calendar_event' => (bool) $meeting->getAttribute('calendar_event_exists'),
            'has_protocol' => (bool) $meeting->getAttribute('protocol_exists'),
            'sharepointPath' => $readOnly ? null : SharepointFileService::pathOrNull($meeting),
            'sharepointFolderUrl' => $readOnly ? null : SharepointFileService::folderUrlOrNull($meeting),
        ];
    }

    /** @return array{id: string, order: int, is_private: true, is_redacted: true, title: string, can: array{update: bool, delete: bool}}|array{id: string, order: int, brought_by_students: bool, type: AgendaItemType|null, start_time: string|null, end_time: string|null, title: string|null, is_private: bool, votes: list<array{is_main: bool, is_consensus: bool, student_vote: string|null, decision: string|null, student_benefit: string|null}>, comments_count: int, has_notes: bool, can: array{update: bool, delete: bool}} */
    private static function agendaItem(AgendaItem $item, User $user, Closure $abilities): array
    {
        $payload = AgendaItemPresenter::forMeetingList($item, $user);
        $payload['can'] = $abilities()->get((string) $item->getKey());

        return $payload;
    }

    /** @return array{id: int, type: TenantType|null, fullname: string, shortname: string, alias: string, phone: string|null, email: string|null, address: string|null, shortname_vu: string|null, primary_institution_id: string|null, goals_enabled: bool, created_at: string|null, updated_at: string|null} */
    public static function tenant(Tenant $tenant): array
    {
        $attributes = $tenant->attributesToArray();

        return [
            'id' => $attributes['id'],
            'type' => $tenant->type,
            'fullname' => $attributes['fullname'],
            'shortname' => $attributes['shortname'],
            'alias' => $attributes['alias'],
            'phone' => $attributes['phone'],
            'email' => $attributes['email'],
            'address' => $attributes['address'],
            'shortname_vu' => $attributes['shortname_vu'],
            'primary_institution_id' => $attributes['primary_institution_id'],
            'goals_enabled' => $attributes['goals_enabled'],
            'created_at' => $attributes['created_at'],
            'updated_at' => $attributes['updated_at'],
        ];
    }

    /** @return array{update: bool, delete: bool, discard: bool, createAgendaItems: bool, reorderAgendaItems: bool, attachInstitution: bool} */
    public static function abilities(bool $update, bool $delete, bool $discard, bool $createAgendaItems): array
    {
        return [
            'update' => $update,
            'delete' => $delete,
            'discard' => $discard,
            'createAgendaItems' => $createAgendaItems,
            'reorderAgendaItems' => $update,
            'attachInstitution' => $update,
        ];
    }

    /** @return array{mode: 'public_page'|'calendar_event'|'calendar_draft'|'internal', url: string|null} */
    public static function publicVisibility(Meeting $meeting, string $locale): array
    {
        if (! $meeting->relationLoaded('calendarEvent')) {
            $meeting->setRelation('calendarEvent', $meeting->calendarEvent()->first(['id', 'is_draft', 'date', 'permalink']));
        }

        return $meeting->publicVisibility($locale);
    }

    /** @return array{status: 'complete'|'incomplete'|'no_items'|null, missingActions: list<array{type: 'agenda_missing'}|array{type: 'agenda_item_type_missing', agenda_item_id: string, title: string, position: int}|array{type: 'agenda_item_vote_missing', agenda_item_id: string, title: string, position: int, missing_fields: list<'decision'|'student_vote'|'student_benefit'>}>} */
    public static function completion(?string $status, array $missingActions): array
    {
        return ['status' => $status, 'missingActions' => $missingActions];
    }
}
