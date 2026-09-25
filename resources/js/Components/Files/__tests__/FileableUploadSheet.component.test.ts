import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

import FileableUploadSheet from '@/Components/Files/FileableUploadSheet.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const SheetFormStub = {
  name: 'SheetForm',
  props: ['open', 'disabled', 'saveLabel', 'processing', 'dirty', 'title'],
  emits: ['submit', 'update:open'],
  template: '<form @submit.prevent="$emit(\'submit\')"><slot /><button class="save" type="submit">{{ saveLabel }}</button></form>',
};

const file = (name: string) => new File(['x'], name, { type: 'application/pdf' });

const mountSheet = (props: Record<string, unknown> = {}) => mount(FileableUploadSheet, {
  props: {
    open: true,
    fileable: { id: 'm1', type: 'Meeting' },
    primaryTypes: ['Protokolai', 'Ataskaitos'],
    defaultDate: '2026-09-12 10:00:00',
    ...props,
  },
  global: { stubs: { ...commonStubs, SheetForm: SheetFormStub, DatePicker: true } },
});

const postedFiles = () => (vi.mocked(router.post).mock.calls[0]![1] as { files: Array<Record<string, unknown>> }).files;

describe('FileableUploadSheet', () => {
  beforeEach(() => vi.clearAllMocks());

  it('queues every dropped file with a type guessed from its name and posts them together', async () => {
    const wrapper = mountSheet({
      existingTypes: ['Protokolai'],
      initialFiles: [file('Ataskaita_galutine.pdf'), file('scan.pdf'), file('skaidres.pptx')],
    });

    expect(wrapper.findAll('[data-slot="fileable-upload-rows"] > li')).toHaveLength(3);

    await wrapper.find('form').trigger('submit');

    expect(router.post).toHaveBeenCalledWith(
      expect.stringContaining('fileableFiles.store'),
      expect.anything(),
      expect.objectContaining({ forceFormData: true }),
    );
    // The protocol is already on the meeting, the report is claimed by name, so the bare scan is "Kita".
    expect(postedFiles().map(entry => entry.type)).toEqual(['Ataskaitos', 'Kita', 'Pristatymai']);
    expect(postedFiles()[0]).toMatchObject({ date: '2026-09-12', name: '2026-09-12 ataskaita' });
    expect(postedFiles()[1]).toMatchObject({ name: 'scan' });
  });

  it('fills the missing primary type first when a name gives no hint', async () => {
    const wrapper = mountSheet({ initialFiles: [file('scan.pdf')] });

    await wrapper.find('form').trigger('submit');

    expect(postedFiles()[0]).toMatchObject({ type: 'Protokolai', name: '2026-09-12 protokolas' });
  });

  it('uses the type of the slot it was opened from', async () => {
    const wrapper = mountSheet({ presetType: 'Ataskaitos', initialFiles: [file('protokolas.pdf')] });

    await wrapper.find('form').trigger('submit');

    expect(postedFiles()[0]).toMatchObject({ type: 'Ataskaitos' });
  });

  it('keeps only the rows the server could not upload', async () => {
    const wrapper = mountSheet({ initialFiles: [file('a.pdf'), file('b.pdf')] });

    await wrapper.find('form').trigger('submit');
    const options = vi.mocked(router.post).mock.calls[0]![2] as { onSuccess: (page: unknown) => void };
    options.onSuccess({ props: { flash: { data: { failed_file_indexes: [1] } } } });
    await wrapper.vm.$nextTick();

    const names = wrapper.findAll('[data-slot="fileable-upload-rows"] input').map(input => (input.element as HTMLInputElement).value);
    expect(names).toHaveLength(1);
    expect(wrapper.emitted('update:open')).toBeUndefined();
  });
});
