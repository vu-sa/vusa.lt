<?php

namespace App\Services\AdminNavigation;

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\Duty;
use App\Models\EventType;
use App\Models\Form;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Problem;
use App\Models\QuickLink;
use App\Models\Relationship;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Role;
use App\Models\SharepointFile;
use App\Models\StudyProgram;
use App\Models\StudySet;
use App\Models\SupportRequest;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Settings\FormSettings;
use Illuminate\Support\Facades\Cache;

/**
 * The single, server-side definition of "which admin pages does this user get".
 *
 * The one place a new admin destination is registered; the shell, palette, + Sukurti and Visi
 * skyriai read `for(User): array` instead of repeating the gate (.ai/rules/shell.md).
 */
class AdminNavigationCatalog
{
    /** Bump the suffix when the payload shape changes, so a deploy never serves the old shape from cache. */
    public const string CACHE_PREFIX = 'admin-navigation-v4-';

    private const int CACHE_TTL = 1800;

    /**
     * The resolved, cached payload for the Inertia prop: only the workspaces, sections and
     * create actions this user may actually see, in the order they should render.
     *
     * @return array{workspaces: list<array<string, mixed>>}
     */
    public function for(User $user): array
    {
        return Cache::remember(self::CACHE_PREFIX.$user->id, self::CACHE_TTL, fn () => $this->resolve($user));
    }

    /**
     * Whether the user has any section of a workspace. An overview controller asks this instead of
     * re-deriving the gate, so the page and its tab can never disagree.
     */
    public function opensWorkspace(User $user, string $workspaceKey): bool
    {
        return collect($this->for($user)['workspaces'])->contains(fn (array $workspace): bool => $workspace['key'] === $workspaceKey);
    }

    /**
     * @return array{workspaces: list<array<string, mixed>>}
     */
    private function resolve(User $user): array
    {
        $workspaces = array_filter([
            $this->resolveWorkspace($this->pradziaWorkspace(), $user),
            $this->resolveWorkspace($this->atstovavimasWorkspace(), $user),
            $this->resolveWorkspace($this->rezervacijosWorkspace(), $user),
            $this->resolveWorkspace($this->svetaineWorkspace(), $user),
            $this->resolveOrganizacijaWorkspace($user),
            $this->resolveWorkspace($this->sistemaWorkspace(), $user),
        ]);

        return ['workspaces' => array_values($workspaces)];
    }

    /**
     * @return array<string, mixed>|null null when the user may see none of its sections — a
     *                                   workspace with zero visible sections does not render.
     */
    private function resolveWorkspace(Workspace $workspace, User $user): ?array
    {
        $sections = $this->visibleSections($workspace->sections, $user);

        if ($sections === []) {
            return null;
        }

        return [
            'key' => $workspace->key,
            'label' => $workspace->labelKey,
            'description' => $workspace->descriptionKey,
            'sections' => $this->withOverview($workspace, $sections),
            'createActions' => $this->visibleCreateActions($workspace->createActions, $user),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sections  already filtered to what the user may see; never empty here
     * @return list<array<string, mixed>>
     */
    private function withOverview(Workspace $workspace, array $sections): array
    {
        return $workspace->overview === null
            ? $sections
            : [$workspace->overview->toArray(), ...$sections];
    }

    /**
     * @param  list<Section>  $sections
     * @return list<array<string, mixed>>
     */
    private function visibleSections(array $sections, User $user): array
    {
        return collect($sections)
            ->filter(fn (Section $section) => $section->visibility->allows($user))
            ->map(function (Section $section) use ($user): array {
                $data = $section->toArray();
                $data['collectionActions'] = collect($section->collectionActions)
                    ->filter(fn (CollectionAction $action) => $action->visibility->allows($user))
                    ->map(fn (CollectionAction $action) => $action->toArray())
                    ->values()
                    ->all();

                return $data;
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<CreateAction>  $actions
     * @return list<array<string, mixed>>
     */
    private function visibleCreateActions(array $actions, User $user): array
    {
        return collect($actions)
            ->filter(fn (CreateAction $action) => $action->visibility->allows($user))
            ->map(fn (CreateAction $action) => $action->toArray())
            ->values()
            ->all();
    }

    private function pradziaWorkspace(): Workspace
    {
        return new Workspace(
            key: 'pradzia',
            labelKey: 'shell.workspaces.pradzia.title',
            descriptionKey: 'shell.workspaces.pradzia.description',
            sections: [
                new Section('apzvalga', 'shell.sections.apzvalga', 'dashboard', [], null, Visibility::always()),
                new Section('uzduotys', 'shell.sections.uzduotys', 'userTasks', [], 'task', Visibility::always(), descriptionKey: 'shell.section_descriptions.uzduotys'),
                new Section('pranesimai', 'shell.sections.pranesimai', 'notifications.index', [], null, Visibility::always(), descriptionKey: 'shell.section_descriptions.pranesimai'),
            ],
        );
    }

    private function atstovavimasWorkspace(): Workspace
    {
        return new Workspace(
            key: 'atstovavimas',
            labelKey: 'shell.workspaces.atstovavimas.title',
            descriptionKey: 'shell.workspaces.atstovavimas.description',
            sections: [
                new Section('apzvalga', 'shell.sections.apzvalga', 'dashboard.atstovavimas', [], null, Visibility::can('viewAny', Meeting::class)),
                new Section('padaliniu_apzvalga', 'shell.sections.padaliniu_apzvalga', 'dashboard.atstovavimas.padaliniai', [], null, Visibility::gate('view-tenant-representation-overview'), descriptionKey: 'shell.section_descriptions.padaliniu_apzvalga'),
                new Section('uzduociu_suvestine', 'shell.sections.uzduociu_suvestine', 'tasks.summary', [], 'task', Visibility::can('viewAny', Task::class), descriptionKey: 'shell.section_descriptions.uzduociu_suvestine'),
                new Section('institucijos', 'shell.sections.institucijos', 'institutions.index', [], 'institution', Visibility::can('viewAny', Institution::class), descriptionKey: 'shell.section_descriptions.institucijos', startsGroup: true),
                new Section('posedziai', 'shell.sections.posedziai', 'meetings.index', [], 'meeting', Visibility::can('viewAny', Meeting::class), matches: ['meetings.*', 'agendaItems.*'], descriptionKey: 'shell.section_descriptions.posedziai'),
                // `search.agendaItems` is a legacy redirect to this same destination — link
                // straight to it instead (SearchController::agendaItems() docblock).
                new Section('darbotvarkes_klausimai', 'shell.sections.darbotvarkes_klausimai', 'search.index', ['tab' => 'agenda-items'], 'agenda_item', Visibility::can('viewAny', Meeting::class), descriptionKey: 'shell.section_descriptions.darbotvarkes_klausimai'),
                new Section('problemos', 'shell.sections.problemos', 'problems.index', [], 'problem', Visibility::can('viewAny', Problem::class), descriptionKey: 'shell.section_descriptions.problemos'),
                new Section('pareigybiu_laikotarpiai', 'shell.sections.pareigybiu_laikotarpiai', 'dutiables.timeline', [], 'dutiable', Visibility::can('viewAny', Duty::class), descriptionKey: 'shell.section_descriptions.pareigybiu_laikotarpiai'),
                new Section('institucijos_grafas', 'shell.sections.institucijos_grafas', 'institutionGraph', [], 'institution', Visibility::can('viewAny', Institution::class), descriptionKey: 'shell.section_descriptions.institucijos_grafas'),
            ],
            createActions: [
                CreateAction::screen('new_meeting', 'shell.actions.new_meeting.title', 'shell.actions.new_meeting.description', 'meeting', 'meeting.institution', Visibility::can('create', Meeting::class)),
                CreateAction::screen('no_meeting', 'shell.actions.no_meeting.title', 'shell.actions.no_meeting.description', 'meeting', 'checkin.institution', Visibility::can('create', Meeting::class)),
                CreateAction::screen('complete_meeting', 'shell.actions.complete_meeting.title', 'shell.actions.complete_meeting.description', 'meeting', 'meeting.pick', Visibility::can('create', Meeting::class)),
                CreateAction::route('new_problem', 'shell.actions.new_problem.title', 'shell.actions.new_problem.description', 'problem', 'problems.create', Visibility::can('create', Problem::class)),
            ],
        );
    }

    private function rezervacijosWorkspace(): Workspace
    {
        return new Workspace(
            key: 'rezervacijos',
            labelKey: 'shell.workspaces.rezervacijos.title',
            descriptionKey: 'shell.workspaces.rezervacijos.description',
            sections: [
                new Section('apzvalga', 'shell.sections.apzvalga', 'dashboard.reservations', [], null, Visibility::always()),
                new Section('rezervacijos', 'shell.sections.rezervacijos', 'reservations.index', [], 'reservation', Visibility::can('viewAny', Reservation::class), matches: ['reservations.*', 'reservationResources.*'], descriptionKey: 'shell.section_descriptions.rezervacijos'),
                new Section('istekliai', 'shell.sections.istekliai', 'resources.index', [], 'resource', Visibility::can('viewAny', Resource::class), descriptionKey: 'shell.section_descriptions.istekliai'),
                // ResourceCategory carries no permissions of its own — its policy delegates to
                // the `resources.*` ability (ResourceCategoryPolicy docblock), so there is no
                // `resource_category` ModelEnum case and no icon-registry entry for it.
                new Section('kategorijos', 'shell.sections.kategorijos', 'resourceCategories.index', [], null, Visibility::can('viewAny', ResourceCategory::class), descriptionKey: 'shell.section_descriptions.kategorijos'),
            ],
            createActions: [
                CreateAction::route('new_reservation', 'shell.actions.new_reservation.title', 'shell.actions.new_reservation.description', 'reservation', 'reservations.create', Visibility::can('create', Reservation::class)),
            ],
        );
    }

    private function svetaineWorkspace(): Workspace
    {
        return new Workspace(
            key: 'svetaine',
            labelKey: 'shell.workspaces.svetaine.title',
            descriptionKey: 'shell.workspaces.svetaine.description',
            sections: [
                new Section('apzvalga', 'shell.sections.apzvalga', 'dashboard.svetaine', [], null, Visibility::can('viewAny', Page::class)),
                new Section('puslapiai', 'shell.sections.puslapiai', 'pages.index', [], 'page', Visibility::can('viewAny', Page::class), descriptionKey: 'shell.section_descriptions.puslapiai'),
                new Section('naujienos', 'shell.sections.naujienos', 'news.index', [], 'news', Visibility::can('viewAny', News::class), descriptionKey: 'shell.section_descriptions.naujienos'),
                new Section('kalendorius', 'shell.sections.kalendorius', 'calendar.index', [], 'calendar', Visibility::can('viewAny', Calendar::class), descriptionKey: 'shell.section_descriptions.kalendorius'),
                new Section('baneriai', 'shell.sections.baneriai', 'banners.index', [], 'banner', Visibility::can('viewAny', Banner::class), descriptionKey: 'shell.section_descriptions.baneriai'),
                new Section('navigacija', 'shell.sections.navigacija', 'navigation.index', [], 'navigation', Visibility::can('viewAny', Navigation::class), descriptionKey: 'shell.section_descriptions.navigacija'),
                new Section('greitosios_nuorodos', 'shell.sections.greitosios_nuorodos', 'quickLinks.index', [], 'quick_link', Visibility::can('viewAny', QuickLink::class), descriptionKey: 'shell.section_descriptions.greitosios_nuorodos'),
                new Section('renginiu_tipai', 'shell.sections.renginiu_tipai', 'eventTypes.index', [], 'event_type', Visibility::can('viewAny', EventType::class), descriptionKey: 'shell.section_descriptions.renginiu_tipai'),
                new Section('zymos', 'shell.sections.zymos', 'tags.index', [], 'tag', Visibility::can('viewAny', Tag::class), [CollectionAction::merge(Visibility::can('viewAny', Tag::class))], descriptionKey: 'shell.section_descriptions.zymos'),
                // `File` is not in `ModelEnum` (its own docblock: "is not a model, but is used
                // for generating file permissions") and has no `viewAny` policy method — gate on
                // the raw permission the controller itself checks (`FilesController::index()`).
                new Section('failai', 'shell.sections.failai', 'files.index', [], null, Visibility::permission('files.read.padalinys'), descriptionKey: 'shell.section_descriptions.failai'),
                new Section('dokumentai', 'shell.sections.dokumentai', 'documents.index', [], 'document', Visibility::can('viewAny', Document::class), descriptionKey: 'shell.section_descriptions.dokumentai'),
                new Section('studiju_rinkiniai', 'shell.sections.studiju_rinkiniai', 'studySets.index', [], 'study_set', Visibility::can('viewAny', StudySet::class), descriptionKey: 'shell.section_descriptions.studiju_rinkiniai'),
            ],
            createActions: [
                CreateAction::route('new_news', 'shell.actions.new_news.title', 'shell.actions.new_news.description', 'news', 'news.create', Visibility::can('create', News::class)),
            ],
        );
    }

    private function organizacijaWorkspace(): Workspace
    {
        return new Workspace(
            key: 'organizacija',
            labelKey: 'shell.workspaces.organizacija.title',
            descriptionKey: 'shell.workspaces.organizacija.description',
            sections: [
                new Section('nariai', 'shell.sections.nariai', 'users.index', [], 'user', Visibility::can('viewAny', User::class), [CollectionAction::merge(Visibility::can('viewAny', User::class))], descriptionKey: 'shell.section_descriptions.nariai'),
                new Section('pareigybes', 'shell.sections.pareigybes', 'duties.index', [], 'duty', Visibility::can('viewAny', Duty::class), [CollectionAction::merge(Visibility::can('viewAny', Duty::class))], ['duties.*', 'dutiables.edit'], descriptionKey: 'shell.section_descriptions.pareigybes'),
                new Section('pareigybiu_atnaujinimas', 'shell.sections.pareigybiu_atnaujinimas', 'duties.updateUsersWizard', [], 'duty', Visibility::can('create', Duty::class), descriptionKey: 'shell.section_descriptions.pareigybiu_atnaujinimas'),
                new Section('padaliniai', 'shell.sections.padaliniai', 'tenants.index', [], 'tenant', Visibility::can('viewAny', Tenant::class), descriptionKey: 'shell.section_descriptions.padaliniai'),
                new Section('studiju_programos', 'shell.sections.studiju_programos', 'studyPrograms.index', [], 'study_program', Visibility::can('viewAny', StudyProgram::class), [CollectionAction::merge(Visibility::can('viewAny', StudyProgram::class))], descriptionKey: 'shell.section_descriptions.studiju_programos'),
            ],
            overview: new Section('apzvalga', 'shell.sections.apzvalga', 'dashboard.organizacija', [], null, Visibility::always()),
            createActions: [
                CreateAction::route('duty_update', 'shell.actions.duty_update.title', 'shell.actions.duty_update.description', 'duty', 'duties.updateUsersWizard', Visibility::can('create', Duty::class)),
                CreateAction::route('duty_periods', 'shell.actions.duty_periods.title', 'shell.actions.duty_periods.description', 'dutiable', 'dutiables.timeline', Visibility::can('viewAny', Duty::class)),
            ],
        );
    }

    /**
     * The registration forms are two ordinary `Form` records whose ids live in `FormSettings`
     * (O22 concerns the *institution* secretary; these are the member/student-rep intake forms,
     * unrelated). Their target and visibility are resolved per-user rather than declared
     * statically like every other section, because the id is data, not part of the definition,
     * and access is a per-record `FormPolicy::view()` check, not a class-level `viewAny`.
     *
     * @return list<Section>
     */
    private function registrationSections(User $user): array
    {
        $settings = app(FormSettings::class);
        $sections = [];

        if ($id = $this->viewableFormId($user, $settings->member_registration_form_id)) {
            $sections[] = new Section('registracija_nariai', 'shell.sections.registracija_nariai', 'forms.show', ['form' => $id], 'form', Visibility::always(), descriptionKey: 'shell.section_descriptions.registracija_nariai');
        }

        if ($id = $this->viewableFormId($user, $settings->student_rep_registration_form_id)) {
            $sections[] = new Section('registracija_atstovai', 'shell.sections.registracija_atstovai', 'forms.show', ['form' => $id], 'form', Visibility::always(), descriptionKey: 'shell.section_descriptions.registracija_atstovai');
        }

        return $sections;
    }

    private function viewableFormId(User $user, ?string $formId): ?string
    {
        if (! $formId) {
            return null;
        }

        $form = Form::find($formId);

        return $form && $user->can('view', $form) ? $form->id : null;
    }

    /**
     * Organizacija is the one workspace whose section list is not fully static: Registracijos
     * (see `registrationSections()`) sits between Studijų programos and Formos, so this method
     * resolves the workspace by hand instead of going through `resolveWorkspace()`.
     *
     * @return array<string, mixed>|null
     */
    private function resolveOrganizacijaWorkspace(User $user): ?array
    {
        $workspace = $this->organizacijaWorkspace();

        $sections = $this->visibleSections($workspace->sections, $user);

        foreach ($this->registrationSections($user) as $section) {
            $sections[] = $section->toArray();
        }

        $formos = new Section('formos', 'shell.sections.formos', 'forms.index', [], 'form', Visibility::can('viewAny', Form::class), descriptionKey: 'shell.section_descriptions.formos');

        if ($formos->visibility->allows($user)) {
            $sections[] = $formos->toArray();
        }

        if ($sections === []) {
            return null;
        }

        return [
            'key' => $workspace->key,
            'label' => $workspace->labelKey,
            'description' => $workspace->descriptionKey,
            'sections' => $this->withOverview($workspace, $sections),
            'createActions' => $this->visibleCreateActions($workspace->createActions, $user),
        ];
    }

    private function sistemaWorkspace(): Workspace
    {
        return new Workspace(
            key: 'sistema',
            labelKey: 'shell.workspaces.sistema.title',
            descriptionKey: 'shell.workspaces.sistema.description',
            sections: [
                new Section('roles', 'shell.sections.roles', 'roles.index', [], 'role', Visibility::can('viewAny', Role::class), descriptionKey: 'shell.section_descriptions.roles'),
                new Section('leidimai', 'shell.sections.leidimai', 'permissions.index', [], 'permission', Visibility::can('viewAny', Permission::class), descriptionKey: 'shell.section_descriptions.leidimai'),
                new Section('tipai', 'shell.sections.tipai', 'types.index', [], 'type', Visibility::can('viewAny', Type::class), descriptionKey: 'shell.section_descriptions.tipai'),
                new Section('rysiai', 'shell.sections.rysiai', 'relationships.index', [], 'relationship', Visibility::can('viewAny', Relationship::class), descriptionKey: 'shell.section_descriptions.rysiai'),
                new Section('nustatymai', 'shell.sections.nustatymai', 'settings.index', [], null, Visibility::gate('manage-settings'), descriptionKey: 'shell.section_descriptions.nustatymai'),
                // Both gate on `viewAny(Role)` in the controller (SystemStatusController,
                // MailQueueController) — not a distinct "super-admin" gate as the phase-0
                // inventory's shorthand suggested.
                new Section('sistemos_busena', 'shell.sections.sistemos_busena', 'systemStatus', [], null, Visibility::can('viewAny', Role::class), descriptionKey: 'shell.section_descriptions.sistemos_busena'),
                new Section('laisku_eile', 'shell.sections.laisku_eile', 'mailQueue', [], null, Visibility::can('viewAny', Role::class), descriptionKey: 'shell.section_descriptions.laisku_eile'),
                new Section('rep_metrics', 'shell.sections.rep_metrics', 'repMetrics', [], null, Visibility::can('viewAny', Role::class), descriptionKey: 'shell.section_descriptions.rep_metrics'),
                new Section('pagalbos_uzklausos', 'shell.sections.pagalbos_uzklausos', 'supportRequests.index', [], null, Visibility::can('viewAny', SupportRequest::class), descriptionKey: 'shell.section_descriptions.pagalbos_uzklausos'),
                new Section('sharepoint_failai', 'shell.sections.sharepoint_failai', 'sharepointFiles.index', [], 'sharepoint_file', Visibility::can('viewAny', SharepointFile::class), matches: ['sharepointFiles.*', 'sharepoint.*'], descriptionKey: 'shell.section_descriptions.sharepoint_failai'),
            ],
            overview: new Section('apzvalga', 'shell.sections.apzvalga', 'dashboard.sistema', [], null, Visibility::always()),
        );
    }
}
