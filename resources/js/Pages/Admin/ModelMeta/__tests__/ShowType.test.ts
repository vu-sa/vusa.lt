import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';

import ShowType from '../ShowType.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const RecordPageStub = defineComponent({
  props: { sections: { type: Array, default: () => [] } },
  template: '<main><slot name="files" /></main>',
});

const FilesPanelStub = defineComponent({
  name: 'FileableFilesPanel',
  props: ['fileable', 'files', 'canUpload', 'canDelete', 'folderUrl'],
  template: '<div data-slot="fileable-files-panel" />',
});

function render(typeKind: 'institutionType' | 'dutyType', canUpdate: boolean, sharepointPath: string | null = 'General/Types/Institutions/Senatas') {
  return mount(ShowType, {
    props: {
      typeKind,
      contentType: { id: '7', title: { lt: 'Senatas', en: 'Senate' }, slug: 'senatas' } as never,
      attachedModels: [],
      sharepointPath,
      files: [],
      can: { update: canUpdate, delete: false },
    },
    global: {
      stubs: {
        RecordPage: RecordPageStub,
        FileableFilesPanel: FilesPanelStub,
        SheetForm: true,
        ConfirmDialog: true,
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
