import type { AdminSection, AdminWorkspace } from '@/Composables/useAdminNavigation';

export const section = (key: string, routeName: string, startsGroup = false): AdminSection => ({
  key,
  label: `shell.sections.${key}`,
  routeName,
  routeParams: {},
  entityType: null,
  description: key === 'apzvalga' ? null : `shell.section_descriptions.${key}`,
  collectionActions: [],
  matches: [routeName],
  startsGroup,
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
  section('uzduotys', 'tasks.index'),
  section('atsakomybes', 'profile.roles'),
]);

export const atstovavimas = workspace('atstovavimas', [
  section('apzvalga', 'dashboard.atstovavimas'),
  section('posedziai', 'meetings.index'),
]);

export const rezervacijos = workspace('rezervacijos', [section('rezervacijos', 'reservations.index')]);

export const kelias: AdminSection = {
  ...section('kelias', 'learningPath.show'),
  children: [
    { key: 'path', label: 'shell.section_children.kelias.path', routeName: 'learningPath.show' },
    { key: 'reps', label: 'shell.section_children.kelias.reps', routeName: 'learningPath.reps' },
    { key: 'library', label: 'shell.section_children.kelias.library', routeName: 'learningSteps.index' },
  ],
};

export const learning = workspace('pradzia', [section('apzvalga', 'dashboard'), kelias]);
