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

  it('revokes a file link only after confirming, and only once the file has one', async () => {
    const wrapper = mountPanel({ files: [protocol, { ...protocol, id: 'f2', public_link: 'https://sharepoint.test/x' }] });

    const revokeButtons = wrapper.findAll('[data-action="revoke-link"]');
    expect(revokeButtons).toHaveLength(1);

    await revokeButtons[0].trigger('click');
    expect(router.delete).not.toHaveBeenCalled();

    await wrapper.find('[data-testid="confirm-delete"]').trigger('click');
    expect(router.delete).toHaveBeenCalledWith(expect.stringContaining('fileableFiles.revokePublicLink'), expect.anything());
  });

  it('offers no link revocation to a reader', () => {
    const wrapper = mountPanel({ files: [{ ...protocol, public_link: 'https://sharepoint.test/x' }], canDelete: false });

    expect(wrapper.find('[data-action="revoke-link"]').exists()).toBe(false);
  });

  it('always says files are never shown on vusa.lt and links the folder when one is given', () => {
    const reader = mountPanel({ canUpload: false });
    expect(reader.find('[data-slot="fileable-files-privacy"]').text()).toContain('files.record.privacy_note');
    expect(reader.find('[data-slot="fileable-sharepoint-folder"]').exists()).toBe(false);

    const manager = mountPanel({ folderUrl: 'https://example.sharepoint.com/General' });
    expect(manager.find('[data-slot="fileable-sharepoint-folder"]').attributes('href')).toBe('https://example.sharepoint.com/General');
  });
});
