import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import { commonStubs } from '@/tests/stubs';

import CadenceRowForm from '../CadenceRowForm.vue';

const stubs = {
  ...commonStubs,
  CollectionSelectDialog: {
    name: 'CollectionSelectDialog',
    props: ['open', 'collection', 'baseFilterBy', 'title', 'description'],
    template: '<div class="picker-stub" />',
    emits: ['confirm', 'update:open'],
  },
};

function mountForm(props: Record<string, unknown> = {}) {
  return mount(CadenceRowForm, {
    props: { modelValue: { start_date: '2025-07-01', end_date: '2026-06-30' }, ...props },
    global: { stubs },
  });
}

describe('CadenceRowForm', () => {
  it('offers no anchor for the global ladder, which belongs to no institution', () => {
    expect(mountForm().text()).not.toContain('cadences.actions.link_meeting');
  });

  // A term routinely opens at another body's sitting, so the picker is not scoped to the
  // institution — the search is already limited to what the user may see by its scoped key.
  it('offers every meeting the user may search, not only this institution’s', async () => {
    const wrapper = mountForm({ institutionId: 'inst-1' });

    await wrapper.findAll('button').find(button => button.text().includes('link_meeting'))!.trigger('click');

    expect(wrapper.findComponent({ name: 'CollectionSelectDialog' }).props('baseFilterBy')).toBeUndefined();
  });

  it('names the institution when the boundary is taken from another body’s sitting', async () => {
    const wrapper = mountForm({ institutionId: 'inst-1' });

    await wrapper.findAll('button').find(button => button.text().includes('link_meeting'))!.trigger('click');
    await wrapper.findComponent({ name: 'CollectionSelectDialog' }).vm.$emit('confirm', [{
      recordId: 'meeting-2',
      title: 'Konferencija',
      raw: { start_time: 1747562400, institution_id: 'inst-2', institution_name_lt: 'VU SA MIF' },
    }]);

    expect(wrapper.text()).toContain('VU SA MIF');
  });

  it('leaves the institution unnamed when the sitting is the term owner’s own', async () => {
    const wrapper = mountForm({
      institutionId: 'inst-1',
      modelValue: { start_date: '2025-05-18', end_date: '2026-05-17', start_meeting_id: 'meeting-1' },
      anchors: {
        start: { id: 'meeting-1', title: 'Konferencija', start_time: '', institution_id: 'inst-1', institution_name: 'VU SA MIF' },
        end: null,
      },
    });

    expect(wrapper.text()).toContain('Konferencija');
    expect(wrapper.text()).not.toContain('VU SA MIF');
  });

  it('takes the boundary date from the picked sitting and locks the field', async () => {
    const wrapper = mountForm({ institutionId: 'inst-1' });

    await wrapper.findAll('button').find(button => button.text().includes('link_meeting'))!.trigger('click');
    // 2025-05-18T10:00:00Z as the unix seconds Typesense stores.
    await wrapper.findComponent({ name: 'CollectionSelectDialog' }).vm.$emit('confirm', [{
      recordId: 'meeting-1',
      title: 'Ataskaitinė-rinkiminė konferencija',
      raw: { start_time: 1747562400 },
    }]);

    const startInput = wrapper.findAll('input[type="date"]')[0];
    expect((startInput.element as HTMLInputElement).value).toBe('2025-05-18');
    expect(startInput.attributes('disabled')).toBeDefined();
    expect(wrapper.text()).toContain('Ataskaitinė-rinkiminė konferencija');
  });

  it('submits the anchor alongside the dates', async () => {
    const wrapper = mountForm({
      institutionId: 'inst-1',
      modelValue: {
        start_date: '2025-05-18',
        end_date: '2026-05-17',
        start_meeting_id: 'meeting-1',
        end_meeting_id: null,
      },
      anchors: { start: { id: 'meeting-1', title: 'Konferencija', start_time: '' }, end: null },
    });

    await wrapper.findAll('button').find(button => button.text().includes('actions.save'))!.trigger('click');

    expect(wrapper.emitted('save')![0][0]).toMatchObject({
      start_date: '2025-05-18',
      start_meeting_id: 'meeting-1',
      end_meeting_id: null,
    });
  });

  it('unlinking hands the boundary back to the date field', async () => {
    const wrapper = mountForm({
      institutionId: 'inst-1',
      modelValue: { start_date: '2025-05-18', end_date: '2026-05-17', start_meeting_id: 'meeting-1' },
      anchors: { start: { id: 'meeting-1', title: 'Konferencija', start_time: '' }, end: null },
    });

    expect(wrapper.findAll('input[type="date"]')[0].attributes('disabled')).toBeDefined();

    await wrapper.find('button[aria-label="cadences.actions.unlink_meeting"]').trigger('click');

    expect(wrapper.findAll('input[type="date"]')[0].attributes('disabled')).toBeUndefined();
    expect(wrapper.text()).toContain('cadences.actions.link_meeting');
  });
});
