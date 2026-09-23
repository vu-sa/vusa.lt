import { describe, expect, it } from 'vitest';

import { belowSectionTrail, resolveActive, sectionHref, type AdminSection, type AdminWorkspace } from '@/Composables/useAdminNavigation';

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

describe('belowSectionTrail', () => {
  const crumb = (label: string, routeName?: string) => ({ label, href: routeName ? route(routeName) : undefined });
  const meetings = section('posedziai', 'meetings.index');
  const outer = [route('dashboard'), route('administration')];

  const home = crumb('Pradinis', 'dashboard');
  const admin = crumb('Administravimas', 'administration');

  it('draws nothing on a section index — the tab row already says where you are', () => {
    expect(belowSectionTrail([home, admin, crumb('Posėdžiai')], meetings, outer)).toEqual([]);
    expect(belowSectionTrail([home, admin, crumb('Posėdžiai', 'meetings.index')], meetings, outer)).toEqual([]);
  });

  it('starts at the section, so it doubles as the way back', () => {
    const trail = belowSectionTrail([home, admin, crumb('Posėdžiai', 'meetings.index'), crumb('2026-09-01')], meetings, outer);

    expect(trail.map(item => item.label)).toEqual(['Posėdžiai', '2026-09-01']);
  });

  it('keeps every level below the section', () => {
    const trail = belowSectionTrail(
      [home, crumb('Posėdžiai', 'meetings.index'), crumb('Posėdis', 'meetings.show'), crumb('Klausimas')],
      meetings,
      outer,
    );

    expect(trail.map(item => item.label)).toEqual(['Posėdžiai', 'Posėdis', 'Klausimas']);
  });

  it('draws nothing when the section is missing from the trail — it is left over from another page', () => {
    expect(belowSectionTrail([home, crumb('Naujienos', 'news.index'), crumb('Nauja naujiena')], meetings, outer)).toEqual([]);
  });

  it('on a page outside every section, draws the trail once it has more than the page itself', () => {
    expect(belowSectionTrail([home, crumb('Profilis')], undefined, outer)).toEqual([]);
    expect(belowSectionTrail([home, crumb('Paskyra', 'profile'), crumb('Saugumas')], undefined, outer).map(item => item.label))
      .toEqual(['Paskyra', 'Saugumas']);
  });

  it('ignores the host and a trailing slash when matching the section', () => {
    const trail = belowSectionTrail(
      [{ label: 'Posėdžiai', href: `https://other.test${sectionHref(meetings)}/` }, crumb('Posėdis')],
      meetings,
    );

    expect(trail).toHaveLength(2);
  });
});
