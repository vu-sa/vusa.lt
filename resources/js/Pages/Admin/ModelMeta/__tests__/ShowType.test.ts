import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';

import ShowType from '../ShowType.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const RecordPageStub = defineComponent({
  props: { sections: { type: Array, default: () => [] } },
  template: '<main><slot name="models" /><slot name="roles" /><slot name="files" /></main>',
});

const FilesPanelStub = defineComponent({
  name: 'FileableFilesPanel',
  props: ['fileable', 'files', 'canUpload', 'canDelete', 'folderUrl'],
  template: '<div data-slot="fileable-files-panel" />',
});

function render(typeKind: 'institutionType' | 'dutyType', canUpdate: boolean, sharepointPath: string | null = 'General/Types/Institutions/Senatas', extraProps: Partial<InstanceType<typeof ShowType>['$props']> = {}) {
  return mount(ShowType, {
    props: {
      typeKind,
      contentType: { id: '7', title: { lt: 'Senatas', en: 'Senate' }, slug: 'senatas' } as never,
      attachedModels: [],
      sharepointPath,
      files: [],
      can: { update: canUpdate, delete: false },
      ...extraProps,
    },
    global: {
      stubs: {
        RecordPage: RecordPageStub,
        FileableFilesPanel: FilesPanelStub,
        SheetForm: true,
        ConfirmDialog: true,
        SpotlightPopover: { template: '<div><slot /></div>' },
      },
    },
  });
}

describe.each([
  ['institutionType', 'InstitutionType'],
  ['dutyType', 'DutyType'],
] as const)('%s files tab', (typeKind, fileableType) => {
  it('offers the files tab and upload to editors', () => {
    const wrapper = render(typeKind, true);

    expect(wrapper.getComponent(RecordPageStub).props('sections')).toContainEqual(expect.objectContaining({ value: 'files' }));
    expect(wrapper.getComponent(FilesPanelStub).props()).toMatchObject({
      fileable: { id: '7', type: fileableType },
      canUpload: true,
      canDelete: true,
    });
  });

  it('keeps the files readable but not uploadable for viewers', () => {
    const wrapper = render(typeKind, false);

    expect(wrapper.getComponent(RecordPageStub).props('sections')).toContainEqual(expect.objectContaining({ value: 'files' }));
    expect(wrapper.getComponent(FilesPanelStub).props('canUpload')).toBe(false);
  });

  it('hides upload when the SharePoint folder cannot be resolved', () => {
    expect(render(typeKind, true, null).getComponent(FilesPanelStub).props('canUpload')).toBe(false);
  });
});

describe('duty type grant management', () => {
  it('shows individual scoped grants and links to the responsibility instead of a bulk editor', () => {
    const wrapper = render('dutyType', true, null, {
      responsibilityGrants: [
        { id: 'grant-1', title: 'Koordinavimas', href: '/mano/responsibilities/role-1', scope_name: 'VU SA MIF', needs_scope: false },
        { id: 'grant-2', title: 'Registracija', href: '/mano/responsibilities/role-2', scope_name: null, needs_scope: true },
      ],
      can: { update: true, delete: false, manageResponsibilities: true, manageAssignments: false },
    });

    const grants = wrapper.get('[data-testid="type-responsibility-grants"]');
    expect(grants.get('a[href="/mano/responsibilities/role-1"]').text()).toBe('Koordinavimas');
    expect(grants.text()).toContain('VU SA MIF');
    expect(grants.get('[data-testid="type-grant-needs-scope"]').text()).toBe('types.grant_needs_scope');
    expect(wrapper.text()).toContain('types.open_responsibilities');
    expect(wrapper.text()).not.toContain('Tvarkyti susietus įrašus');
    expect(wrapper.findAllComponents({ name: 'SheetForm' })).toHaveLength(1);
  });

  it('keeps grant names readable without links or management for viewers', () => {
    const wrapper = render('dutyType', false, null, {
      responsibilityGrants: [{ id: 'grant-1', title: 'Komunikacija', href: null, scope_name: null, needs_scope: false }],
    });

    expect(wrapper.get('[data-testid="type-responsibility-grants"]').text()).toContain('Komunikacija');
    expect(wrapper.text()).not.toContain('types.open_responsibilities');
    expect(wrapper.find('[data-testid="type-responsibility-grants"] a').exists()).toBe(false);
  });
});
