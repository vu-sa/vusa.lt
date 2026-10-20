import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import PermissionMatrix from '../PermissionMatrix.vue';
import type { PermissionCatalog } from '../types';

const catalog: PermissionCatalog = {
  areas: ['atstovavimas', 'organizacija'],
  resources: [
    { key: 'meetings', model: 'meeting', area: 'atstovavimas', abilities: { read: ['padalinys', '*'], update: ['own', 'padalinys', '*'], delete: ['own', 'padalinys', '*'] }, baseline: 'Mato viešus posėdžius.', softDeletes: true },
    { key: 'mailingLists', model: 'mailing_list', area: 'organizacija', abilities: { read: ['padalinys', '*'], delete: ['padalinys', '*'] }, baseline: null, softDeletes: false },
  ],
  retired: ['meetings.read.own'],
};

function mountMatrix(granted: string[] = [], readonly = false) {
  return mount(PermissionMatrix, { props: { catalog, granted, readonly } });
}

describe('PermissionMatrix', () => {
  it('keeps column headings below the sticky shell with an opaque background', () => {
    const header = mountMatrix().get('[data-testid="matrix-table"] thead');

    expect(header.classes()).toEqual(expect.arrayContaining([
      'sticky', 'top-[var(--shell-chrome-height,0px)]', 'z-20', 'bg-background',
    ]));
    expect(header.findAll('th[scope="col"]')).toHaveLength(6);
  });

  it('lists every catalog resource, including konferencijos', () => {
    const table = mountMatrix().get('[data-testid="matrix-table"]');

    expect(table.find('[data-resource="meetings"]').exists()).toBe(true);
    expect(table.find('[data-resource="mailingLists"]').exists()).toBe(true);
  });

  it('offers only the scopes an ability allows', () => {
    const cell = mountMatrix().get('[data-testid="matrix-table"] [data-cell="meetings.read"]');

    expect(cell.findAll('option').map(option => option.attributes('value'))).toEqual(['', 'padalinys', '*']);
  });

  it('shows a save bar with the diff and emits the whole set', async () => {
    const wrapper = mountMatrix(['meetings.read.padalinys']);

    expect(wrapper.find('[data-testid="matrix-save-bar"]').exists()).toBe(false);

    await wrapper.get('[data-testid="matrix-table"] [data-cell="mailingLists.read"]').setValue('padalinys');
    await wrapper.get('[data-testid="matrix-save"]').trigger('click');

    expect(wrapper.emitted('save')?.[0]).toEqual([['mailingLists.read.padalinys', 'meetings.read.padalinys']]);
  });

  it('says plainly when deleting has no trash', () => {
    const wrapper = mountMatrix();

    expect(wrapper.find('[data-resource="meetings"] [data-testid="cell-permanent"]').exists()).toBe(false);
    expect(wrapper.find('[data-resource="mailingLists"] [data-testid="cell-permanent"]').exists()).toBe(true);
    expect(wrapper.get('[data-cell="mailingLists.delete"]').attributes('aria-label')).toMatch(/negrįžtamai|permanently|delete_permanent/);
  });

  it('opens read-only on the granted resources, without anything to save', () => {
    const wrapper = mountMatrix(['meetings.read.padalinys'], true);

    expect(wrapper.find('[data-resource="mailingLists"]').exists()).toBe(false);
    expect(wrapper.find('[data-resource="meetings"]').exists()).toBe(true);
    expect(wrapper.find('[data-cell="meetings.read"]').exists()).toBe(false);
  });
});
