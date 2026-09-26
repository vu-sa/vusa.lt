import { describe, expect, it } from 'vitest';

import { resolveActive, sectionHref, type AdminSection, type AdminWorkspace } from '@/Composables/useAdminNavigation';

const section = (key: string, routeName: string, extra: Partial<AdminSection> = {}): AdminSection => ({
  key,
  label: key,
  routeName,
  routeParams: {},
  entityType: null,
  collectionActions: [],
  matches: [routeName.endsWith('.index') ? `${routeName.slice(0, -5)}*` : routeName],
  ...extra,
});

const workspace = (key: string, sections: AdminSection[]): AdminWorkspace => ({
  key,
  label: key,
  description: '',
  sections,
  createActions: [],
});

const catalog = [
  workspace('pradzia', [section('apzvalga', 'dashboard'), section('uzduotys', 'userTasks')]),
  workspace('atstovavimas', [
    section('posedziai', 'meetings.index'),
    section('darbotvarkes_klausimai', 'agendaItems.index', { matches: ['agendaItems.*'] }),
  ]),
  workspace('organizacija', [
    section('pareigybes', 'duties.index'),
    section('pareigybiu_atnaujinimas', 'duties.updateUsersWizard'),
    section('registracija_nariai', 'forms.show', { routeParams: { form: 'abc' } }),
    section('formos', 'forms.index'),
  ]),
];

describe('resolveActive', () => {
  it('resolves a record page to the section that owns it', () => {
    const active = resolveActive(catalog, 'meetings.show');

    expect(active.workspace?.key).toBe('atstovavimas');
    expect(active.section?.key).toBe('posedziai');
  });

  it('resolves routes claimed through an explicit pattern', () => {
    expect(resolveActive(catalog, 'agendaItems.edit').section?.key).toBe('darbotvarkes_klausimai');
  });

  it('lets an exact route name beat a sibling wildcard', () => {
    expect(resolveActive(catalog, 'duties.updateUsersWizard').section?.key).toBe('pareigybiu_atnaujinimas');
    expect(resolveActive(catalog, 'duties.edit').section?.key).toBe('pareigybes');
  });

  it('only lets a section with routeParams claim a route when the params agree', () => {
    expect(resolveActive(catalog, 'forms.show', { form: 'abc' }).section?.key).toBe('registracija_nariai');
    expect(resolveActive(catalog, 'forms.show', { form: 'other' }).section?.key).toBe('formos');
    expect(resolveActive(catalog, 'agendaItems.index').section?.key).toBe('darbotvarkes_klausimai');
  });

  it('keeps a section listed in two workspaces in the preferred one', () => {
    const shared = [
      workspace('atstovavimas', [section('dokumentai', 'documents.index')]),
      workspace('svetaine', [section('dokumentai', 'documents.index')]),
    ];

    expect(resolveActive(shared, 'documents.index').workspace?.key).toBe('atstovavimas');
    expect(resolveActive(shared, 'documents.index', {}, 'svetaine').workspace?.key).toBe('svetaine');
    // A preference never outranks a better match elsewhere.
    expect(resolveActive(catalog, 'duties.updateUsersWizard', {}, 'atstovavimas').section?.key).toBe('pareigybiu_atnaujinimas');
  });

  it('resolves nothing for an unknown or missing route', () => {
    expect(resolveActive(catalog, 'profile')).toEqual({ workspace: undefined, section: undefined });
    expect(resolveActive(catalog, undefined)).toEqual({ workspace: undefined, section: undefined });
  });
});
