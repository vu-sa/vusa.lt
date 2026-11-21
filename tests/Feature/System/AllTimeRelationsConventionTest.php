<?php

/**
 * All-time term relation convention tests.
 *
 * Reading historical assignments as current membership let former duty holders through
 * authorization checks and included them in notification audiences. Ratchet the remaining
 * references so each new use has to account for the dates it needs.
 */

use Illuminate\Support\Str;

const ALL_TIME_RELATION_DIRECTORIES = [
    'app/Actions',
    'app/Services',
    'app/Policies',
    'app/Http/Requests',
    'app/Http/Middleware',
    'app/Notifications',
    'app/Tasks',
];

const ALL_TIME_RELATION_NEEDLES = [
    '->tenants',
    "'tenants'",
    '->users',
    "'users'",
    '->duties',
    "'duties",
    'usersThroughDuties',
];

/** @var array<string, array<string, int>> */
const ALL_TIME_RELATION_ALLOWLIST = [
    // Checks whether the institution has representative duties configured.
    'app/Actions/ActivityRequests/SendInstitutionActivityRequests.php' => ["'duties" => 1],
    // Reservation participants and the actor's resource-management tenant scope.
    'app/Actions/ApplyReservationIndexFilters.php' => ['->tenants' => 1, "'users'" => 2],
    // Duty ID qualifiers for data quality filters.
    'app/Actions/BuildDutyIndexQuery.php' => ["'duties" => 2],
    // Direct role users are checked alongside current duty holders.
    'app/Actions/BuildSupportRequestCollection.php' => ["'users'" => 1],
    // Explicit task assignees and the actor's task-read tenant scope.
    'app/Actions/BuildTaskIndexQuery.php' => ['->tenants' => 1, "'users'" => 3],
    // Member history includes past units and terms, with date filters for current and scheduled terms.
    'app/Actions/BuildUserIndexQuery.php' => ['->tenants' => 1, "'tenants'" => 1, "'duties" => 12],
    // Cache key for ex officio integrity findings.
    'app/Actions/Dutiables/CheckExOfficioIntegrity.php' => ["'duties" => 1],
    // Duty table aliases for the source and target of an ex officio term.
    'app/Actions/Dutiables/FindSuspectExOfficioDutiables.php' => ["'duties" => 2],
    // Duty ID qualifier on the ex officio target query.
    'app/Actions/Dutiables/PlanExOfficioReview.php' => ["'duties" => 1],
    // Institution duty catalog and duty ID qualifiers for ex officio rules.
    'app/Actions/Dutiables/ReconcileExOfficioSeats.php' => ['->duties' => 1, "'duties" => 2],
    // Lineage includes holders' terms that began before the handover.
    'app/Actions/Duties/BuildDutyLineage.php' => ['->users' => 1, "'users'" => 1, "'duties" => 2],
    // Duty creation and update scopes restrict selectable ex officio targets.
    'app/Actions/Duties/GetAssignableExOfficioDuties.php' => ['->tenants' => 1, "'duties" => 3],
    // Assignment error payload and duty attach.
    'app/Actions/Duties/SyncUserDuties.php' => ['->duties' => 1, "'duties" => 1],
    // Merge candidates show tenants and counts from all terms.
    'app/Actions/FindMergeableUserDuplicates.php' => ['->tenants' => 1, "'tenants'" => 1, '->duties' => 1, "'duties" => 1],
    // Responsibility resolver duties are loaded with current holders.
    'app/Actions/GetInstitutionCoordinators.php' => ['->duties' => 1],
    // Responsibility resolver supplies the coordination audience for this institution.
    'app/Actions/GetInstitutionManagers.php' => ['->users' => 1],
    // The duties query checks both ends of the term against the requested date.
    'app/Actions/GetInstitutionMembers.php' => ["'duties" => 2],
    // The representative duties query checks both ends of the term against the requested date.
    'app/Actions/GetInstitutionRepresentatives.php' => ["'duties" => 2],
    // Permission-scoped tenants for duty assignment and form options.
    'app/Actions/GetPermissableTenants.php' => ['->tenants' => 1],
    // Meeting tenant relation and duty record route.
    'app/Actions/GetRecentlyEditedRecords.php' => ["'tenants'" => 1, "'duties" => 1],
    // Responsibility resolver selects the duties that handle reservations for this tenant.
    'app/Actions/GetResourceManagers.php' => ['->duties' => 1],
    // ModelAuthorizer supplies the tenants for the upsert permission.
    'app/Actions/GetTenantsForUpserts.php' => ['->tenants' => 1],
    // Duty record route.
    'app/Actions/GetUserAccessSummary.php' => ["'duties" => 1],
    // Duty merge audit event and log message.
    'app/Actions/MergeDuties.php' => ["'duties" => 2],
    // Excludes the reservation's explicit participants.
    'app/Actions/NotifyAffectedReservationDrafts.php' => ['->users' => 1],
    // Purges access caches for anyone linked to a removed permission through a duty.
    'app/Actions/Permissions/SyncPermissionCatalog.php' => ["'duties" => 2],
    // Duty table joins and columns for projecting responsibility grants.
    'app/Actions/ProjectDutyResponsibilities.php' => ["'duties" => 8],
    // Selects source duties before collecting holders with activeOn().
    'app/Actions/ResolveMailingListMembers.php' => ['->duties' => 1],
    // Task assignees come from task_user.
    'app/Actions/ResolveTaskAudience.php' => ['->users' => 1, "'users'" => 1],
    // Direct role users and role duties intersected with the actor's current duties.
    'app/Actions/Responsibilities/GrantableResponsibilities.php' => ["'users'" => 1, "'duties" => 3],
    // Writes date-scoped assignees to task_user.
    'app/Actions/ResyncTaskAssigneesForCadence.php' => ['->users' => 2],
    // Eager-loads explicit task assignees from task_user.
    'app/Actions/Schedulable/TaskNotifier.php' => ["'users'" => 1],
    // Reservation participants and the actor's resource-management tenant scope.
    'app/Actions/SerializeReservationsForTable.php' => ['->tenants' => 1, '->users' => 2, "'users'" => 1],
    // Eager-loads explicit reservation participants for the view policy.
    'app/Actions/SerializeResourceAvailability.php' => ["'users'" => 1],
    // Permission key for validating an institution change.
    'app/Actions/UpdateDuty.php' => ["'duties" => 1],
    // Tenant catalog and the shared prop backed by current_or_upcoming_tenants().
    'app/Http/Middleware/HandleInertiaRequests.php' => ["'tenants'" => 2],
    // Reservation participants payload field.
    'app/Http/Requests/AddReservationUsersRequest.php' => ["'users'" => 1],
    // Duty table used for timeline filter validation.
    'app/Http/Requests/Api/Admin/DutiableTimelineRequest.php' => ["'duties" => 1],
    // Resource names in the merge candidate type allowlist.
    'app/Http/Requests/Api/Admin/MergeCandidatesRequest.php' => ["'users'" => 1, "'duties" => 1],
    // Resource name in the record abilities model map.
    'app/Http/Requests/Api/Admin/RecordAbilitiesRequest.php' => ["'users'" => 1],
    // User table used to validate the impersonation target.
    'app/Http/Requests/Api/Admin/StartImpersonationRequest.php' => ["'users'" => 1],
    // User table used to validate the support request assignee.
    'app/Http/Requests/AssignSupportRequestRequest.php' => ["'users'" => 1],
    // ModelAuthorizer tenant scope, duty permission keys, and user table validation.
    'app/Http/Requests/BatchUpdateDutyUsersRequest.php' => ['->tenants' => 1, "'users'" => 1, "'duties" => 2],
    // User table used to validate a content snapshot author.
    'app/Http/Requests/Concerns/ValidatesContentParts.php' => ["'users'" => 1],
    // Duty table used to validate a learning audience selector.
    'app/Http/Requests/Concerns/ValidatesLearningAudiences.php' => ["'duties" => 1],
    // Tenant and duty tables used to validate goal fields.
    'app/Http/Requests/Goals/GoalRequest.php' => ["'tenants'" => 1, "'duties" => 1],
    // User table used to validate step performers.
    'app/Http/Requests/Goals/StepRequest.php' => ["'users'" => 1],
    // Duty collection key for the index page.
    'app/Http/Requests/IndexDutyRequest.php' => ["'duties" => 1],
    // Duty table used to validate a mailing list source selector.
    'app/Http/Requests/MailingLists/StoreMailingListSourceRequest.php' => ["'duties" => 1],
    // Duty table used to validate merge inputs.
    'app/Http/Requests/MergeDutiesRequest.php' => ["'duties" => 2],
    // User table used to validate merge inputs.
    'app/Http/Requests/MergeUsersRequest.php' => ["'users'" => 2],
    // User table used to validate the person responsible for a problem.
    'app/Http/Requests/ProblemRequest.php' => ["'users'" => 1],
    // Duty reorder payload keys and table validation.
    'app/Http/Requests/ReorderDutiesRequest.php' => ["'duties" => 5],
    // Tenant filter payload field.
    'app/Http/Requests/ShowRepresentativesRequest.php' => ["'tenants'" => 1],
    // ModelAuthorizer tenant scope, duty permission key, and duty and user table validation.
    'app/Http/Requests/StoreDutiableRequest.php' => ['->tenants' => 1, "'users'" => 2, "'duties" => 2],
    // ModelAuthorizer tenant scope, duty permission keys, and duty and user table validation.
    'app/Http/Requests/StoreDutyRequest.php' => ['->tenants' => 2, "'users'" => 1, "'duties" => 4],
    // Duty permission keys for the responsibility's authorized scope.
    'app/Http/Requests/StoreDutyResponsibilityRequest.php' => ["'duties" => 2],
    // Duty table used to validate the role grant target.
    'app/Http/Requests/StoreRoleGrantRequest.php' => ["'duties" => 1],
    // User table used to validate the people involved in a support request.
    'app/Http/Requests/StoreSupportRequestRequest.php' => ["'users'" => 1],
    // User and duty tables used to validate new user attributes.
    'app/Http/Requests/StoreUserRequest.php' => ["'users'" => 1, "'duties" => 1],
    // User table used to validate changes to the people involved in a support request.
    'app/Http/Requests/SyncSupportRequestInvolvedUsersRequest.php' => ["'users'" => 1],
    // DutyType catalog duties, the duty ID column, and the Duty tenant relation.
    'app/Http/Requests/SyncTypeModelsRequest.php' => ["'tenants'" => 1, '->duties' => 1, "'duties" => 2],
    // Duty permission keys and duty and user table validation.
    'app/Http/Requests/UpdateDutyRequest.php' => ["'users'" => 1, "'duties" => 3],
    // User table used to validate nominated secretaries.
    'app/Http/Requests/UpdateInstitutionSecretariesRequest.php' => ["'users'" => 1],
    // Translation placeholder for blocked tenants and validation against the user and duty tables.
    'app/Http/Requests/UpdateUserRequest.php' => ["'tenants'" => 1, "'users'" => 1, "'duties" => 1],
    // Duty record route.
    'app/Notifications/DutyExpiringNotification.php' => ["'duties" => 1],
    // Reservation participants come from reservation_user.
    'app/Notifications/ReservationStatusChangeBatch.php' => ['->users' => 1],
    // ModelAuthorizer tenant scope and owning tenants of allowlisted commentable models.
    'app/Policies/CommentPolicy.php' => ['->tenants' => 2, "'tenants'" => 1],
    // Assigning seats reads the ModelAuthorizer scope.
    'app/Policies/DutyPolicy.php' => ['->tenants' => 1, "'duties" => 1],
    // PermissionScope tenants for directory authorization.
    'app/Policies/FilePolicy.php' => ['->tenants' => 1],
    // ModelAuthorizer tenants for the goal creation permission.
    'app/Policies/GoalPolicy.php' => ['->tenants' => 1],
    // Duty column qualifier on the non-ended term relation.
    'app/Policies/InstitutionCheckInPolicy.php' => ["'duties" => 1],
    // ModelAuthorizer duties, a meeting-date membership window, and Meeting tenant relation.
    'app/Policies/MeetingPolicy.php' => ["'tenants'" => 1, '->duties' => 2, "'duties" => 1],
    // Reservation participants come from reservation_user.
    'app/Policies/ReservationPolicy.php' => ['->users' => 4],
    // Task assignees come from task_user; tenantsFor() reads the task's or its subject's owning units.
    'app/Policies/TaskPolicy.php' => ['->tenants' => 2, "'tenants'" => 2, '->users' => 2],
    // PermissionScope and record tenant reads; a User record keeps all-time tenants on purpose (alumni management).
    'app/Policies/Traits/HasCommonChecks.php' => ['->tenants' => 2, "'tenants'" => 2, '->duties' => 1],
    // Fail-closed historical containment, blocking tenant names, and the actor's ModelAuthorizer scope.
    'app/Policies/UserPolicy.php' => ['->tenants' => 7, "'tenants'" => 1],
    // Duty route names and route patterns for admin navigation.
    'app/Services/AdminNavigation/AdminNavigationCatalog.php' => ["'duties" => 4],
    // Agenda item tenants are the meeting's institution tenants.
    'app/Services/AgendaItemPresenter.php' => ['->tenants' => 1, "'tenants'" => 2],
    // Gantt shows all terms and reuses the meeting's institution tenants for its agenda.
    'app/Services/AtstovavimasDashboardService.php' => ['->tenants' => 1, "'tenants'" => 2, '->users' => 2, "'users'" => 3, '->duties' => 2, "'duties" => 4],
    // Reads tenants from an already resolved permission scope.
    'app/Services/Authorization/PermissionScope.php' => ['->tenants' => 2],
    // Legacy reservation-resource comments use reservation participants after curated audiences are checked.
    'app/Services/CommentRecipientResolver.php' => ["'users'" => 2],
    // Reservation participants come from reservation_user.
    'app/Services/CommentableMentionResolver.php' => ['->users' => 1],
    // Contact payload groups duties already loaded with current holders.
    'app/Services/ContactPresentationService.php' => ["'duties" => 3],
    // Refreshes search documents for past and present holders and their duties.
    'app/Services/ContactSearchIndexSynchronizer.php' => ['->users' => 1, '->duties' => 1],
    // Duty record, list and search intentionally include duties the actor has held.
    'app/Services/DutyAccessService.php' => ['->tenants' => 1, "'tenants'" => 1, '->users' => 1, "'users'" => 1, '->duties' => 1, "'duties" => 5],
    // Audited insert of new assignment rows into dutiables.
    'app/Services/DutyAssignmentService.php' => ["'users'" => 1],
    // Sibling duties load only current holders.
    'app/Services/DutyPanelPresenter.php' => ['->duties' => 1],
    // Duty record shows every term, and duty rows include the home tenant.
    'app/Services/DutyPresenter.php' => ['->tenants' => 1, "'tenants'" => 3, '->users' => 1, "'users'" => 1],
    // Duty-update permission key and its resolved tenant options.
    'app/Services/DutyResponsibilityPresenter.php' => ["'tenants'" => 1, "'duties" => 1],
    // Reads permission-scoped tenants for file directories.
    'app/Services/FileStorageService.php' => ['->tenants' => 2],
    // Duty and user table names and duty record routes.
    'app/Services/FileUsageScanner.php' => ["'users'" => 1, "'duties" => 3],
    // ModelAuthorizer supplies the form-read tenant scope.
    'app/Services/FormAccessService.php' => ['->tenants' => 1],
    // Registration options check vacancies through duties.current_users.
    'app/Services/FormOptionResolver.php' => ["'duties" => 1],
    // Institution duty counts and duty lists with current holders.
    'app/Services/InstitutionPresenter.php' => ['->duties' => 4, "'duties" => 3],
    // Related institutions load term history for the Gantt and join the tenant table for graph edges.
    'app/Services/InstitutionRelationService.php' => ["'tenants'" => 1, "'duties" => 2],
    // Term history feeds Gantt coverage.
    'app/Services/Institutions/AtstovavimasInstitutionPresenter.php' => ['->duties' => 1, '->users' => 1],
    // Duty permission scopes, full term history for Gantt coverage, and trimming those member rows.
    'app/Services/Institutions/InstitutionQueries.php' => ['->tenants' => 1, "'duties" => 10, "'users'" => 1, '->duties' => 1, '->users' => 1],
    // Tenant options come from the section's upsert permission.
    'app/Services/KnowledgeSectionOptions.php' => ["'tenants'" => 1],
    // User table lookup for profile photos.
    'app/Services/Learning/SystemSteps/ProfilePhotoDetector.php' => ["'users'" => 1],
    // Chair duties load current holders for learning contacts.
    'app/Services/LearningInstitutionContactsPresenter.php' => ['->duties' => 1, "'duties" => 1],
    // Meeting tenants, permission-scoped duties and historical terms bounded to meeting dates.
    'app/Services/MeetingAccessService.php' => ['->tenants' => 1, '->duties' => 2, "'duties" => 1],
    // Both duty queries restrict terms to the meeting date.
    'app/Services/MeetingRepresentativeResolver.php' => ["'duties" => 4],
    // PermissionScope results, computed tenant sets and the cache of non-ended duties.
    'app/Services/ModelAuthorizer.php' => ['->tenants' => 3, '->duties' => 3, "'duties" => 1],
    // Duty email column on the current duties relation.
    'app/Services/NotificationRouter.php' => ["'duties" => 1],
    // ModelAuthorizer supplies tenants for the requested permission.
    'app/Services/PermissionService.php' => ['->tenants' => 1],
    // Role duties come from the responsibility projection.
    'app/Services/Permissions/ResponsibilityPermissionGaps.php' => ['->duties' => 1],
    // Institution duty catalog and a count of duties with current holders.
    'app/Services/PublicInstitutionSearchIndexBuilder.php' => ['->duties' => 1, "'duties" => 2],
    // Duty catalog queries and responsibility lookups with date-scoped holders.
    'app/Services/ResponsibilityResolver.php' => ['->users' => 1, '->duties' => 1, "'duties" => 9],
    // User table updates scrub all staging accounts.
    'app/Services/StagingAccountScrubber.php' => ["'users'" => 3],
    // Queue statistics field for waiting users.
    'app/Services/SystemMonitorService.php' => ['->users' => 1],
    // Relation name checks qualify the tenant ID in scoped table queries.
    'app/Services/TanstackTableService.php' => ["'tenants'" => 2],
    // Typesense collection names and a duty-read permission key.
    'app/Services/Typesense/TypesenseCollectionConfig.php' => ["'users'" => 1, "'duties" => 2],
    // Includes past holders in key invalidation and uses scoped tenants for search.
    'app/Services/Typesense/TypesenseScopedKeyService.php' => ['->tenants' => 1, "'tenants'" => 1, '->users' => 3, "'users'" => 2, '->duties' => 1, "'duties" => 2],
    // Duplicate candidates count terms across the whole record.
    'app/Services/UserSimilarityFinder.php' => ["'duties" => 1],
    // Task assignees come from task_user.
    'app/Tasks/Handlers/AgendaCompletionTaskHandler.php' => ['->users' => 1, "'users'" => 1],
    // Task assignees come from task_user.
    'app/Tasks/Handlers/AgendaCreationTaskHandler.php' => ["'users'" => 1],
    // Explicit task input assignees and the task_user relation.
    'app/Tasks/Handlers/ApprovalTaskHandler.php' => ['->users' => 1, "'users'" => 1],
    // Syncs the explicit task input assignees to task_user.
    'app/Tasks/Handlers/BaseTaskHandler.php' => ['->users' => 2],
    // Explicit task input assignees.
    'app/Tasks/Handlers/ManualTaskHandler.php' => ['->users' => 1],
    // Task assignees come from task_user.
    'app/Tasks/Handlers/PeriodicityGapTaskHandler.php' => ['->users' => 1, "'users'" => 1],
    // Task assignees come from task_user.
    'app/Tasks/Handlers/PickupTaskHandler.php' => ["'users'" => 1],
    // Task assignees come from task_user.
    'app/Tasks/Handlers/ReturnTaskHandler.php' => ["'users'" => 1],
    // Task assignees come from task_user.
    'app/Tasks/Synchronizers/ReservationReviewTaskSynchronizer.php' => ['->users' => 3, "'users'" => 1],
    // Syncs reservation participants from reservation_user to task assignees in task_user.
    'app/Tasks/Synchronizers/ReservationTaskSynchronizer.php' => ['->users' => 4, "'users'" => 2],
];

test('default-named term relation references stay within the justified allowance', function (): void {
    $files = [];

    foreach (ALL_TIME_RELATION_DIRECTORIES as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory))) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }

    $counts = [];
    foreach ($files as $file) {
        $relative = Str::after($file, base_path().'/');
        $contents = php_strip_whitespace($file);

        foreach (ALL_TIME_RELATION_NEEDLES as $needle) {
            $count = substr_count($contents, $needle);
            if ($count > 0) {
                $counts[$relative][$needle] = $count;
            }
        }
    }

    $offenders = [];
    foreach (array_unique([...array_keys($counts), ...array_keys(ALL_TIME_RELATION_ALLOWLIST)]) as $relative) {
        foreach (ALL_TIME_RELATION_NEEDLES as $needle) {
            $actual = $counts[$relative][$needle] ?? 0;
            $allowed = ALL_TIME_RELATION_ALLOWLIST[$relative][$needle] ?? 0;

            if ($actual > $allowed) {
                $offenders[] = "{$relative} matches {$needle} {$actual} times with an allowance of {$allowed}.";
            } elseif ($actual < $allowed) {
                $offenders[] = "{$relative} matches {$needle} {$actual} times with an allowance of {$allowed}. Lower the count.";
            }
        }
    }

    sort($offenders);

    expect($offenders)->toBeEmpty(
        'Default-named relations (User::duties/tenants, Duty/Institution/Meeting::users) include every term ever held. '
        .'Use current_users / current_or_upcoming_duties / current_or_upcoming_tenants / GetInstitutionMembers / ModelAuthorizer::scope, '
        ."or add a justified allowlist entry.\n".implode("\n", $offenders)
    );
});

test('the removed authorization duties name stays out of application source', function (): void {
    $offenders = [];

    foreach (['app', 'resources/js', 'routes', 'database'] as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory))) as $file) {
            if ($file->isFile() && str_contains(file_get_contents($file->getPathname()), 'authorization_duties')) {
                $offenders[] = Str::after($file->getPathname(), base_path().'/');
            }
        }
    }

    sort($offenders);

    expect($offenders)->toBeEmpty(
        "Use current_or_upcoming_duties in place of authorization_duties.\n".implode("\n", $offenders)
    );
});
