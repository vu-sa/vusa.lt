import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

import FileableFilesPanel from '@/Components/Files/FileableFilesPanel.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const UploadSheetStub = {
  name: 'FileableUploadSheet',
  props: ['open', 'presetType', 'initialFiles'],
  template: '<div data-testid="upload-sheet" :data-open="open" :data-preset="presetType" />',
};

const ConfirmDialogStub = {
  name: 'ConfirmDialog',
  props: ['open'],
  emits: ['confirm', 'update:open'],
  template: '<button v-if="open" data-testid="confirm-delete" @click="$emit(\'confirm\')" />',
};

const protocol = { id: 'f1', name: 'Protokolas.pdf', file_type: 'Protokolai', file_date: '2026-09-12 00:00:00' };

const mountPanel = (props: Record<string, unknown> = {}) => mount(FileableFilesPanel, {
  props: {
    fileable: { id: 'm1', type: 'Meeting' },
    files: [],
    canUpload: true,
    canDelete: true,
    primaryTypes: ['Protokolai', 'Ataskaitos'],
    ...props,
  },
  global: { stubs: { ...commonStubs, FileableUploadSheet: UploadSheetStub, ConfirmDialog: ConfirmDialogStub } },
});

describe('FileableFilesPanel', () => {
  beforeEach(() => vi.clearAllMocks());

  it('shows a slot for each missing primary document and opens the upload with that type', async () => {
    const wrapper = mountPanel({ files: [protocol] });

    expect(wrapper.find('[data-missing-type="Protokolai"]').exists()).toBe(false);
    await wrapper.find('[data-missing-type="Ataskaitos"]').trigger('click');

    const sheet = wrapper.find('[data-testid="upload-sheet"]');
    expect(sheet.attributes('data-open')).toBe('true');
    expect(sheet.attributes('data-preset')).toBe('Ataskaitos');
  });

  it('opens a file in one click through the redirect route', () => {
    const wrapper = mountPanel({ files: [protocol] });

    const link = wrapper.find('[data-slot="fileable-file-row"] a');
    expect(link.attributes('href')).toContain('fileableFiles.open');
    expect(link.attributes('target')).toBe('_blank');
  });

  it('deletes only after confirming', async () => {
    const wrapper = mountPanel({ files: [protocol] });

    await wrapper.find('[data-slot="fileable-file-row"] button[aria-label="Ištrinti"]').trigger('click');
    expect(router.delete).not.toHaveBeenCalled();

    await wrapper.find('[data-testid="confirm-delete"]').trigger('click');
    expect(router.delete).toHaveBeenCalledWith(expect.stringContaining('fileableFiles.destroy'), expect.anything());
  });

  it('offers no upload or delete to a reader', () => {
    const wrapper = mountPanel({ files: [protocol], canUpload: false, canDelete: false });

    expect(wrapper.find('[data-testid="upload-sheet"]').exists()).toBe(false);
    expect(wrapper.find('button[data-missing-type]').exists()).toBe(false);
    expect(wrapper.find('button[aria-label="Ištrinti"]').exists()).toBe(false);
  });
});
