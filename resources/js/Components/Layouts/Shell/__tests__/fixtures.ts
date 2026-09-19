import type { AdminSection, AdminWorkspace } from '@/Composables/useAdminNavigation';

export const section = (key: string, routeName: string): AdminSection => ({
  key,
  label: `shell.sections.${key}`,
  routeName,
  routeParams: {},
  entityType: null,
  collectionActions: [],
  matches: [routeName],
});

export const workspace = (key: string, sections: AdminSection[], createActions: AdminWorkspace['createActions'] = []): AdminWorkspace => ({
  key,
  label: `shell.workspaces.${key}.title`,
  description: `shell.workspaces.${key}.description`,
  sections,
  createActions,
});

export const pradzia = workspace('pradzia', [
  section('apzvalga', 'dashboard'),
  section('uzduotys', 'userTasks'),
  section('pranesimai', 'notifications.index'),
]);

export const atstovavimas = workspace('atstovavimas', [
  section('apzvalga', 'dashboard.atstovavimas'),
  section('posedziai', 'meetings.index'),
]);

export const rezervacijos = workspace('rezervacijos', [section('rezervacijos', 'reservations.index')]);
