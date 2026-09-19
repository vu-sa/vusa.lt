import { describe, expect, it } from 'vitest';

import { primaryWorkspace, resolveActive, type AdminSection, type AdminWorkspace } from '@/Composables/useAdminNavigation';

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
    section('posedziai', 'meetings.index', { matches: ['meetings.*', 'agendaItems.*'] }),
    section('darbotvarkes_klausimai', 'search.index', { routeParams: { tab: 'agenda-items' } }),
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
    expect(resolveActive(catalog, 'agendaItems.edit').section?.key).toBe('posedziai');
  });

  it('lets an exact route name beat a sibling wildcard', () => {
    expect(resolveActive(catalog, 'duties.updateUsersWizard').section?.key).toBe('pareigybiu_atnaujinimas');
    expect(resolveActive(catalog, 'duties.edit').section?.key).toBe('pareigybes');
  });

  it('only lets a section with routeParams claim a route when the params agree', () => {
    expect(resolveActive(catalog, 'forms.show', { form: 'abc' }).section?.key).toBe('registracija_nariai');
    expect(resolveActive(catalog, 'forms.show', { form: 'other' }).section?.key).toBe('formos');
    expect(resolveActive(catalog, 'search.index', { tab: 'agenda-items' }).section?.key).toBe('darbotvarkes_klausimai');
    expect(resolveActive(catalog, 'search.index').workspace).toBeUndefined();
  });

  it('resolves nothing for an unknown or missing route', () => {
    expect(resolveActive(catalog, 'profile')).toEqual({ workspace: undefined, section: undefined });
    expect(resolveActive(catalog, undefined)).toEqual({ workspace: undefined, section: undefined });
  });
});

describe('primaryWorkspace', () => {
  const many = (key: string, count: number) => workspace(key, Array.from({ length: count }, (_, index) => section(`s${index}`, `${key}${index}.index`)));

  it('is the workspace holding the most sections, never Pradžia', () => {
    expect(primaryWorkspace([many('pradzia', 9), many('atstovavimas', 3), many('svetaine', 7)])?.key).toBe('svetaine');
  });

  it('breaks a tie in favour of ViSAK', () => {
    expect(primaryWorkspace([many('pradzia', 3), many('svetaine', 4), many('atstovavimas', 4)])?.key).toBe('atstovavimas');
  });

  it('is undefined when the user has only Pradžia', () => {
    expect(primaryWorkspace([many('pradzia', 3)])).toBeUndefined();
  });
});
