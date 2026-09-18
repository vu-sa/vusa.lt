# Admin Route Inventory (GET /mano/*)

> **Phase 0 Appendix (PR 0.3)** · Generated 2026-09-17 from live Laravel routes.
> Total GET routes under `/mano`: **134**.

## 1. Resolution of Workspace Questions

The draft workspace map in `rules/navigation.md` flagged five routes with `?`. Their confirmed placement is:

| Route | Confirmed Workspace | Confirmed Section | Rationale |
|---|---|---|---|
| `tasks.summary` | **ViSAK** | Užduočių suvestinė | Oversees rep agenda completion and periodicity gap tasks across institutions. Target audience is student rep coordinators. |
| `studySets.index` | **Svetainė** | Studijų rinkiniai | Public-facing study materials and program set configuration, managed alongside public study program content. |
| `systemStatus` | **Sistema** | Sistemos būsena | Server environment diagnostics, Pulse/Telescope, queue worker health. Dedicated to super admins. |
| `mailQueue` | **Sistema** | Laiškų eilė | Email transport and delivery backlog diagnostic. Purely operational super-admin tool. |
| `sharepointFiles.index` | **Sistema** | Sharepoint failai | SharePoint drive mapping and synchronization settings. System-level integration configuration. |

## 2. Workspace Summary

| Workspace | Routes | Primary Audience | Key Sections |
|---|---|---|---|
| **Pradžia** | 3 | All users | Apžvalga (attention queue), Užduotys, Pranešimai |
| **ViSAK** | 18 | Student representatives, coordinators | Posėdžiai, Institucijos, Darbotvarkės klausimai, Problemos, Laikotarpiai |
| **Rezervacijos** | 12 | Resource managers, borrowers | Rezervacijos, Ištekliai, Kategorijos |
| **Svetainė** | 33 | Comms coordinators, editors | Puslapiai, Naujienos, Kalendorius, Baneriai, Navigacija, Greitosios nuorodos, Dokumentai |
| **Organizacija** | 26 | Unit coordinators, HR, admins | Nariai, Pareigybės, Padaliniai, Studijų programos, Formos, Registracijos |
| **Sistema** | 42 | Super admins, developers | Rolės, Leidimai, Tipai, Ryšiai, Nustatymai, Sistemos būsena, Laiškų eilė, Pagalba |

## 3. Full Inventory of GET Routes

| Route Name | URI | Controller@Action | Workspace | Section | Page Type | Default View | Backend | Gate / Scope | Audience | Reach |
|---|---|---|---|---|---|---|---|---|---|---|
| `forms.index` | `/mano/forms` | FormController@index | Organizacija | Formos | Collection | table | database | forms.* | HR, administrators, coordinators | — |
| `forms.show` | `/mano/forms/{form}` | FormController@show | Organizacija | Formos | Record | — | — | forms.* | HR, administrators, coordinators | 12 |
| `forms.edit` | `/mano/forms/{form}/edit` | FormController@edit | Organizacija | Formos | Form | — | — | forms.* | HR, administrators, coordinators | — |
| `forms.export` | `/mano/forms/{form}/export` | FormController@export | Organizacija | Formos | Record | — | — | forms.* | HR, administrators, coordinators | — |
| `forms.create` | `/mano/forms/create` | FormController@create | Organizacija | Formos | Form | — | — | forms.* | HR, administrators, coordinators | — |
| `users.index` | `/mano/users` | UserController@index | Organizacija | Nariai | Collection | table | Typesense | users.* | HR, administrators, coordinators | 44 |
| `users.show` | `/mano/users/{user}` | UserController@show | Organizacija | Nariai | Record | — | — | users.* | HR, administrators, coordinators | 28 |
| `users.edit` | `/mano/users/{user}/edit` | UserController@edit | Organizacija | Nariai | Form | — | — | users.* | HR, administrators, coordinators | 92 |
| `users.create` | `/mano/users/create` | UserController@create | Organizacija | Nariai | Form | — | — | users.* | HR, administrators, coordinators | 16 |
| `users.merge` | `/mano/users/merge` | UserController@merge | Organizacija | Nariai | Record | — | — | users.* | HR, administrators, coordinators | — |
| `tenants.index` | `/mano/tenants` | TenantController@index | Organizacija | Padaliniai | Collection | table | database | tenants.* | HR, administrators, coordinators | — |
| `tenants.show` | `/mano/tenants/{tenant}` | TenantController@show | Organizacija | Padaliniai | Record | — | — | tenants.* | HR, administrators, coordinators | — |
| `tenants.edit` | `/mano/tenants/{tenant}/edit` | TenantController@edit | Organizacija | Padaliniai | Form | — | — | tenants.* | HR, administrators, coordinators | — |
| `tenants.editMainPage` | `/mano/tenants/{tenant}/main-page/edit` | TenantController@editMainPage | Organizacija | Padaliniai | Record | — | — | tenants.* | HR, administrators, coordinators | — |
| `tenants.editQuickLink` | `/mano/tenants/{tenant}/quick-links/edit` | TenantController@editQuickLink | Organizacija | Padaliniai | Record | — | — | tenants.* | HR, administrators, coordinators | — |
| `tenants.create` | `/mano/tenants/create` | TenantController@create | Organizacija | Padaliniai | Form | — | — | tenants.* | HR, administrators, coordinators | — |
| `duties.index` | `/mano/duties` | DutyController@index | Organizacija | Pareigybės | Collection | table | Typesense | duties.* | HR, administrators, coordinators | 33 |
| `duties.show` | `/mano/duties/{duty}` | DutyController@show | Organizacija | Pareigybės | Record | — | — | duties.* | HR, administrators, coordinators | 87 |
| `duties.edit` | `/mano/duties/{duty}/edit` | DutyController@edit | Organizacija | Pareigybės | Form | — | — | duties.* | HR, administrators, coordinators | 62 |
| `duties.create` | `/mano/duties/create` | DutyController@create | Organizacija | Pareigybės | Form | — | — | duties.* | HR, administrators, coordinators | 4 |
| `duties.merge` | `/mano/duties/merge` | DutyController@merge | Organizacija | Pareigybės | Record | — | — | duties.* | HR, administrators, coordinators | — |
| `duties.updateUsersWizard` | `/mano/duties-update-users` | DutyController@updateUsersWizard | Organizacija | Pareigybių atnaujinimas | Guided flow | — | — | create Duty | HR, administrators, coordinators | 45 |
| `studyPrograms.index` | `/mano/studyPrograms` | StudyProgramController@index | Organizacija | Studijų programos | Collection | table | database | studyPrograms.* | HR, administrators, coordinators | — |
| `studyPrograms.edit` | `/mano/studyPrograms/{studyProgram}/edit` | StudyProgramController@edit | Organizacija | Studijų programos | Form | — | — | studyPrograms.* | HR, administrators, coordinators | — |
| `studyPrograms.create` | `/mano/studyPrograms/create` | StudyProgramController@create | Organizacija | Studijų programos | Form | — | — | studyPrograms.* | HR, administrators, coordinators | — |
| `studyPrograms.merge` | `/mano/studyPrograms/merge` | StudyProgramController@merge | Organizacija | Studijų programos | Record | — | — | studyPrograms.* | HR, administrators, coordinators | — |
| `dashboard` | `/mano` | DashboardController@index | Pradžia | Apžvalga | Overview | — | — | auth | All users | — |
| `notifications.index` | `/mano/notifications` | UserNotificationsController@index | Pradžia | Pranešimai | Collection | rows | database | auth | All users | 50 |
| `userTasks` | `/mano/tasks` | TaskController@index | Pradžia | Užduotys | Collection | rows | database | auth | All users with tasks | 57 |
| `dashboard.reservations` | `/mano/dashboard/reservations` | ReservationsDashboardController@reservations | Rezervacijos | Apžvalga | Overview | — | — | auth | Resource managers, borrowers | 100 |
| `resources.index` | `/mano/resources` | ResourceController@index | Rezervacijos | Ištekliai | Collection | rows | Typesense | resources.* | Resource managers, borrowers | 20 |
| `resources.show` | `/mano/resources/{resource}` | ResourceController@show | Rezervacijos | Ištekliai | Record | — | — | resources.* | Resource managers, borrowers | — |
| `resources.edit` | `/mano/resources/{resource}/edit` | ResourceController@edit | Rezervacijos | Ištekliai | Form | — | — | resources.* | Resource managers, borrowers | 35 |
| `resources.create` | `/mano/resources/create` | ResourceController@create | Rezervacijos | Ištekliai | Form | — | — | resources.* | Resource managers, borrowers | 3 |
| `resourceCategories.index` | `/mano/resourceCategories` | ResourceCategoryController@index | Rezervacijos | Kategorijos | Collection | table | database | resources.* | Resource managers, borrowers | 1 |
| `resourceCategories.show` | `/mano/resourceCategories/{resourceCategory}` | ResourceCategoryController@show | Rezervacijos | Kategorijos | Record | — | — | resources.* | Resource managers, borrowers | — |
| `resourceCategories.edit` | `/mano/resourceCategories/{resourceCategory}/edit` | ResourceCategoryController@edit | Rezervacijos | Kategorijos | Sheet form | — | — | resources.* | Resource managers, borrowers | — |
| `resourceCategories.create` | `/mano/resourceCategories/create` | ResourceCategoryController@create | Rezervacijos | Kategorijos | Sheet form | — | — | resources.* | Resource managers, borrowers | — |
| `reservations.index` | `/mano/reservations` | ReservationController@index | Rezervacijos | Rezervacijos | Collection | table | database | reservations.* | Resource managers, borrowers | 9 |
| `reservations.show` | `/mano/reservations/{reservation}` | ReservationController@show | Rezervacijos | Rezervacijos | Record | — | — | reservations.* | Resource managers, borrowers | 59 |
| `reservations.create` | `/mano/reservations/create` | ReservationController@create | Rezervacijos | Rezervacijos | Form | — | — | reservations.* | Resource managers, borrowers | 33 |
| `approvals.history` | `/mano/approvals/history` | ApprovalController@history | Sistema |  | Record | — | — | auth | Super admins, developers | — |
| `dutiables.edit` | `/mano/dutiables/{dutiable}/edit` | DutiableController@edit | Sistema |  | Record | — | — | auth | Super admins, developers | 32 |
| `mySupportRequests.index` | `/mano/my-support-requests` | MySupportRequestController@index | Sistema |  | Record | — | — | auth | Super admins, developers | 8 |
| `mySupportRequests.create` | `/mano/my-support-requests/create` | MySupportRequestController@create | Sistema |  | Record | — | — | auth | Super admins, developers | 3 |
| `push-subscription.index` | `/mano/push-subscription` | PushSubscriptionController@index | Sistema |  | Record | — | — | auth | Super admins, developers | — |
| `reservationResources.show` | `/mano/reservationResources/{reservationResource}` | ReservationResourceController@show | Sistema |  | Record | — | — | auth | Super admins, developers | — |
| `search.agendaItems` | `/mano/search/agenda-items` | SearchController@agendaItems | Sistema |  | Record | — | — | auth | Super admins, developers | — |
| `search.institutions` | `/mano/search/institutions` | SearchController@institutions | Sistema |  | Record | — | — | auth | Super admins, developers | — |
| `search.meetings` | `/mano/search/meetings` | SearchController@meetings | Sistema |  | Record | — | — | auth | Super admins, developers | — |
| `search.resources` | `/mano/search/resources` | SearchController@resources | Sistema |  | Record | — | — | auth | Super admins, developers | — |
| `sharepoint.getDriveItemPublicLink` | `/mano/sharepoint/{id}/permissions` | SharepointFileController@getDriveItemPublicLink | Sistema |  | Record | — | — | auth | Super admins, developers | — |
| `sharepoint.getTypesDriveItems` | `/mano/sharepoint/{type}/{id}` | SharepointFileController@getTypesDriveItems | Sistema |  | Record | — | — | auth | Super admins, developers | — |
| `sharepointFiles.index` | `/mano/sharepointFiles` | SharepointFileController@index | Sistema |  | Record | — | — | auth | Super admins, developers | 3 |
| `mailQueue` | `/mano/mail-queue` | MailQueueController@index | Sistema | Laiškų eilė | Collection | table | database | super-admin | Super admins, developers | — |
| `permissions.index` | `/mano/permissions` | PermissionController@index | Sistema | Leidimai | Collection | table | database | permissions.* | Super admins, developers | — |
| `settings.index` | `/mano/settings` | SettingsController@index | Sistema | Nustatymai | Form | — | — | manage-settings | Super admins, developers | 1 |
| `settings.atstovavimas.edit` | `/mano/settings/atstovavimas` | SettingsController@editAtstovavimasSettings | Sistema | Nustatymai | Form | — | — | manage-settings | Super admins, developers | 1 |
| `settings.authorization.edit` | `/mano/settings/authorization` | SettingsController@editAuthorization | Sistema | Nustatymai | Form | — | — | manage-settings | Super admins, developers | 1 |
| `settings.cadences.index` | `/mano/settings/cadences` | CadenceController@index | Sistema | Nustatymai | Form | — | — | manage-settings | Super admins, developers | 1 |
| `settings.documents.edit` | `/mano/settings/documents` | SettingsController@editDocumentSettings | Sistema | Nustatymai | Form | — | — | manage-settings | Super admins, developers | 1 |
| `settings.forms.edit` | `/mano/settings/forms` | SettingsController@editFormSettings | Sistema | Nustatymai | Form | — | — | manage-settings | Super admins, developers | 1 |
| `settings.meetings.edit` | `/mano/settings/meetings` | SettingsController@editMeetingSettings | Sistema | Nustatymai | Form | — | — | manage-settings | Super admins, developers | 1 |
| `settings.site.edit` | `/mano/settings/site` | SettingsController@editSiteSettings | Sistema | Nustatymai | Form | — | — | manage-settings | Super admins, developers | — |
| `supportRequests.index` | `/mano/support-requests` | SupportRequestController@index | Sistema | Pagalbos užklausos | Collection | rows | database | supportRequests.* | Super admins, developers | — |
| `supportRequests.show` | `/mano/support-requests/{support_request}` | SupportRequestController@show | Sistema | Pagalbos užklausos | Record | — | — | supportRequests.* | Super admins, developers | 1 |
| `supportRequests.edit` | `/mano/support-requests/{support_request}/edit` | SupportRequestController@edit | Sistema | Pagalbos užklausos | Record | — | — | supportRequests.* | Super admins, developers | — |
| `search.index` | `/mano/search` | SearchController@index | Sistema | Paieška | Workbench | — | — | auth | All users | 74 |
| `profile` | `/mano/profile` | ProfileController@userSettings | Sistema | Paskyra | Form | — | — | auth | All users | 93 |
| `roles.index` | `/mano/roles` | RoleController@index | Sistema | Rolės | Collection | table | database | roles.* | Super admins, developers | 1 |
| `roles.show` | `/mano/roles/{role}` | RoleController@show | Sistema | Rolės | Record | — | — | roles.* | Super admins, developers | — |
| `roles.edit` | `/mano/roles/{role}/edit` | RoleController@edit | Sistema | Rolės | Form | — | — | roles.* | Super admins, developers | 1 |
| `roles.create` | `/mano/roles/create` | RoleController@create | Sistema | Rolės | Form | — | — | roles.* | Super admins, developers | 1 |
| `relationships.index` | `/mano/relationships` | RelationshipController@index | Sistema | Ryšiai | Collection | table | database | relationships.* | Super admins, developers | — |
| `relationships.show` | `/mano/relationships/{relationship}` | RelationshipController@show | Sistema | Ryšiai | Record | — | — | relationships.* | Super admins, developers | — |
| `relationships.edit` | `/mano/relationships/{relationship}/edit` | RelationshipController@edit | Sistema | Ryšiai | Sheet form | — | — | relationships.* | Super admins, developers | — |
| `relationships.create` | `/mano/relationships/create` | RelationshipController@create | Sistema | Ryšiai | Sheet form | — | — | relationships.* | Super admins, developers | — |
| `systemStatus` | `/mano/system-status` | SystemStatusController@index | Sistema | Sistemos būsena | Overview | — | — | super-admin | Super admins, developers | — |
| `types.index` | `/mano/types` | TypeController@index | Sistema | Tipai | Collection | table | database | types.* | Super admins, developers | — |
| `types.show` | `/mano/types/{type}` | TypeController@show | Sistema | Tipai | Record | — | — | types.* | Super admins, developers | — |
| `types.edit` | `/mano/types/{type}/edit` | TypeController@edit | Sistema | Tipai | Sheet form | — | — | types.* | Super admins, developers | — |
| `types.create` | `/mano/types/create` | TypeController@create | Sistema | Tipai | Sheet form | — | — | types.* | Super admins, developers | — |
| `administration` | `/mano/administration` | Controller@invoke | Sistema | Visi skyriai | Overview | — | — | can:access-administration | All unit coordinators | 106 |
| `dashboard.svetaine` | `/mano/dashboard/svetaine` | SvetaineDashboardController@svetaine | Svetainė | Apžvalga | Overview | — | — | viewAny Page | Content editors, comms coordinators | 22 |
| `banners.index` | `/mano/banners` | BannerController@index | Svetainė | Baneriai | Collection | table | database | banners.* | Content editors, comms coordinators | 3 |
| `banners.edit` | `/mano/banners/{banner}/edit` | BannerController@edit | Svetainė | Baneriai | Form | — | — | banners.* | Content editors, comms coordinators | — |
| `banners.create` | `/mano/banners/create` | BannerController@create | Svetainė | Baneriai | Form | — | — | banners.* | Content editors, comms coordinators | 1 |
| `documents.index` | `/mano/documents` | DocumentController@index | Svetainė | Dokumentai | Collection | rows | Typesense | documents.* | Content editors, comms coordinators | 11 |
| `documents.show` | `/mano/documents/{document}` | DocumentController@show | Svetainė | Dokumentai | Record | — | — | documents.* | Content editors, comms coordinators | — |
| `files.index` | `/mano/files` | FilesController@index | Svetainė | Failai | Workbench | rows | database | files.* | Content editors, comms coordinators | 6 |
| `quickLinks.index` | `/mano/quickLinks` | QuickLinkController@index | Svetainė | Greitosios nuorodos | Collection | table | database | quickLinks.* | Content editors, comms coordinators | 5 |
| `quickLinks.edit` | `/mano/quickLinks/{quickLink}/edit` | QuickLinkController@edit | Svetainė | Greitosios nuorodos | Form | — | — | quickLinks.* | Content editors, comms coordinators | — |
| `quickLinks.create` | `/mano/quickLinks/create` | QuickLinkController@create | Svetainė | Greitosios nuorodos | Form | — | — | quickLinks.* | Content editors, comms coordinators | — |
| `calendar.index` | `/mano/calendar` | CalendarController@index | Svetainė | Kalendorius | Collection | rows | Typesense | calendar.* | Content editors, comms coordinators | 17 |
| `calendar.view` | `/mano/calendar/{calendar}` | CalendarController@show | Svetainė | Kalendorius | Record | — | — | calendar.* | Content editors, comms coordinators | — |
| `calendar.edit` | `/mano/calendar/{calendar}/edit` | CalendarController@edit | Svetainė | Kalendorius | Form | — | — | calendar.* | Content editors, comms coordinators | — |
| `calendar.create` | `/mano/calendar/create` | CalendarController@create | Svetainė | Kalendorius | Form | — | — | calendar.* | Content editors, comms coordinators | 11 |
| `news.index` | `/mano/news` | NewsController@index | Svetainė | Naujienos | Collection | rows | Typesense | news.* | Content editors, comms coordinators | 11 |
| `news.edit` | `/mano/news/{news}/edit` | NewsController@edit | Svetainė | Naujienos | Workbench | — | — | news.* | Content editors, comms coordinators | — |
| `news.create` | `/mano/news/create` | NewsController@create | Svetainė | Naujienos | Workbench | — | — | news.* | Content editors, comms coordinators | 8 |
| `navigation.index` | `/mano/navigation` | NavigationController@index | Svetainė | Navigacija | Collection | table | database | navigation.* | Content editors, comms coordinators | 1 |
| `navigation.edit` | `/mano/navigation/{navigation}/edit` | NavigationController@edit | Svetainė | Navigacija | Workbench | — | — | navigation.* | Content editors, comms coordinators | — |
| `navigation.create` | `/mano/navigation/create` | NavigationController@create | Svetainė | Navigacija | Workbench | — | — | navigation.* | Content editors, comms coordinators | — |
| `pages.index` | `/mano/pages` | PageController@index | Svetainė | Puslapiai | Collection | rows | Typesense | pages.* | Content editors, comms coordinators | 14 |
| `pages.edit` | `/mano/pages/{page}/edit` | PageController@edit | Svetainė | Puslapiai | Workbench | — | — | pages.* | Content editors, comms coordinators | — |
| `pages.create` | `/mano/pages/create` | PageController@create | Svetainė | Puslapiai | Workbench | — | — | pages.* | Content editors, comms coordinators | 1 |
| `eventTypes.index` | `/mano/eventTypes` | EventTypeController@index | Svetainė | Renginių tipai | Collection | table | database | eventTypes.* | Content editors, comms coordinators | — |
| `eventTypes.edit` | `/mano/eventTypes/{eventType}/edit` | EventTypeController@edit | Svetainė | Renginių tipai | Sheet form | — | — | eventTypes.* | Content editors, comms coordinators | — |
| `eventTypes.create` | `/mano/eventTypes/create` | EventTypeController@create | Svetainė | Renginių tipai | Sheet form | — | — | eventTypes.* | Content editors, comms coordinators | — |
| `studySets.index` | `/mano/studySets` | StudySetController@index | Svetainė | Studijų rinkiniai | Collection | table | database | studySets.* | Content editors, comms coordinators | — |
| `studySets.edit` | `/mano/studySets/{studySet}/edit` | StudySetController@edit | Svetainė | Studijų rinkiniai | Form | — | — | studySets.* | Content editors, comms coordinators | — |
| `studySets.create` | `/mano/studySets/create` | StudySetController@create | Svetainė | Studijų rinkiniai | Form | — | — | studySets.* | Content editors, comms coordinators | — |
| `tags.index` | `/mano/tags` | TagController@index | Svetainė | Žymos | Collection | table | database | tags.* | Content editors, comms coordinators | — |
| `tags.edit` | `/mano/tags/{tag}/edit` | TagController@edit | Svetainė | Žymos | Sheet form | — | — | tags.* | Content editors, comms coordinators | — |
| `tags.create` | `/mano/tags/create` | TagController@create | Svetainė | Žymos | Sheet form | — | — | tags.* | Content editors, comms coordinators | — |
| `tags.merge` | `/mano/tags/merge` | TagController@mergeTags | Svetainė | Žymos | Record | — | — | tags.* | Content editors, comms coordinators | — |
| `dashboard.atstovavimas` | `/mano/dashboard/atstovavimas` | AtstovavimasDashboardController@atstovavimas | ViSAK | Apžvalga | Overview | — | — | viewAny Meeting | Student representatives, coordinators | 103 |
| `agendaItems.show` | `/mano/agendaItems/{agendaItem}` | AgendaItemController@show | ViSAK | Darbotvarkės klausimai | Record | — | — | agendaItems.* | Student representatives, coordinators | — |
| `agendaItems.edit` | `/mano/agendaItems/{agendaItem}/edit` | AgendaItemController@edit | ViSAK | Darbotvarkės klausimai | Workbench | — | — | agendaItems.* | Student representatives, coordinators | 115 |
| `institutions.index` | `/mano/institutions` | InstitutionController@index | ViSAK | Institucijos | Collection | rows | Typesense | institutions.* | Student representatives, coordinators | 16 |
| `institutions.show` | `/mano/institutions/{institution}` | InstitutionController@show | ViSAK | Institucijos | Record | — | — | institutions.* | Student representatives, coordinators | 100 |
| `institutions.edit` | `/mano/institutions/{institution}/edit` | InstitutionController@edit | ViSAK | Institucijos | Form | — | — | institutions.* | Student representatives, coordinators | 21 |
| `institutions.create` | `/mano/institutions/create` | InstitutionController@create | ViSAK | Institucijos | Form | — | — | institutions.* | Student representatives, coordinators | 1 |
| `institutionGraph` | `/mano/institutionGraph` | DashboardController@institutionGraph | ViSAK | Institucijų grafas | Workbench | — | — | viewAny Institution | Student representatives, coordinators | 2 |
| `dutiables.timeline` | `/mano/dutiables/timeline` | DutiableTimelineController@index | ViSAK | Pareigybių laikotarpiai | Workbench | — | — | viewAny Duty | Student representatives, coordinators | 1 |
| `meetings.index` | `/mano/meetings` | MeetingController@index | ViSAK | Posėdžiai | Collection | rows | Typesense | meetings.* | Student representatives, coordinators | 1 |
| `meetings.search` | `/mano/meetings-search` | MeetingController@search | ViSAK | Posėdžiai | Record | — | — | meetings.* | Student representatives, coordinators | — |
| `meetings.show` | `/mano/meetings/{meeting}` | MeetingController@show | ViSAK | Posėdžiai | Record | — | — | meetings.* | Student representatives, coordinators | 142 |
| `meetings.edit` | `/mano/meetings/{meeting}/edit` | MeetingController@edit | ViSAK | Posėdžiai | Form | — | — | meetings.* | Student representatives, coordinators | — |
| `problems.index` | `/mano/problems` | ProblemController@index | ViSAK | Problemos | Collection | preview pane | database | problems.* | Student representatives, coordinators | 14 |
| `problems.show` | `/mano/problems/{problem}` | ProblemController@show | ViSAK | Problemos | Record | — | — | problems.* | Student representatives, coordinators | 38 |
| `problems.edit` | `/mano/problems/{problem}/edit` | ProblemController@edit | ViSAK | Problemos | Form | — | — | problems.* | Student representatives, coordinators | — |
| `problems.create` | `/mano/problems/create` | ProblemController@create | ViSAK | Problemos | Form | — | — | problems.* | Student representatives, coordinators | 20 |
| `tasks.summary` | `/mano/tasks/summary` | TaskController@summary | ViSAK | Užduočių suvestinė | Overview | — | — | viewAny Task | Student representatives, coordinators | — |
