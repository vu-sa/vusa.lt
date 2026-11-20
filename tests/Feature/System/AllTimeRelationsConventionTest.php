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
    // Responsibility resolver duties are loaded with current holders.
    'app/Actions/GetInstitutionCoordinators.php' => ['->duties' => 1],
    // Responsibility resolver supplies the coordination audience for this institution.
    'app/Actions/GetInstitutionManagers.php' => ['->users' => 1],
    // The duties query checks both ends of the term against the requested date.
    'app/Actions/GetInstitutionMembers.php' => ["'duties" => 2],
    // The representative duties query checks both ends of the term against the requested date.
    'app/Actions/GetInstitutionRepresentatives.php' => ["'duties" => 2],
    // Meeting tenant relation and duty record route.
    'app/Actions/GetRecentlyEditedRecords.php' => ["'tenants'" => 1, "'duties" => 1],
    // Responsibility resolver selects the duties that handle reservations for this tenant.
    'app/Actions/GetResourceManagers.php' => ['->duties' => 1],
    // ModelAuthorizer supplies the tenants for the upsert permission.
    'app/Actions/GetTenantsForUpserts.php' => ['->tenants' => 1],
    // Duty record route.
    'app/Actions/GetUserAccessSummary.php' => ["'duties" => 1],
    // Selects source duties before collecting holders with activeOn().
    'app/Actions/ResolveMailingListMembers.php' => ['->duties' => 1],
    // Task assignees come from task_user.
    'app/Actions/ResolveTaskAudience.php' => ['->users' => 1, "'users'" => 1],
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
    $files = glob(base_path('app/Actions/{Get,Resolve}*.php'), GLOB_BRACE) ?: [];

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
