import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import { commonStubs } from '@/tests/stubs';

import DutiableTimeline from '../DutiableTimeline.vue';

const stubs = {
  ...commonStubs,
  // AdminContentPage calls route().current(), which the shared route() mock does not model.
  PageContent: {
    name: 'PageContent',
    template: '<div><slot name="after-heading" /><slot /></div>',
  },
  CollectionSelectDialog: {
    name: 'CollectionSelectDialog',
    props: ['open', 'collection', 'title'],
    template: '<div class="picker-stub"><slot name="trigger" /></div>',
    emits: ['confirm', 'update:open'],
  },
  DutiableTimelineEditor: {
    name: 'DutiableTimelineEditor',
    props: ['scopeType', 'scopeId', 'height'],
    template: '<div class="editor-stub" />',
  },
};

function mountPage(initialInstitution: { id: string; name: string } | null) {
  return mount(DutiableTimeline, { props: { initialInstitution }, global: { stubs } });
}

describe('Admin/People/DutiableTimeline', () => {
  it('asks for an institution before drawing anything', () => {
    const wrapper = mountPage(null);

    expect(wrapper.findComponent({ name: 'DutiableTimelineEditor' }).exists()).toBe(false);
    expect(wrapper.text()).toContain('dutiables.timeline.page.no_scope');
  });

  it('opens on the institution the URL named', () => {
    const editor = mountPage({ id: 'inst-1', name: 'Parlamentas' })
      .findComponent({ name: 'DutiableTimelineEditor' });

    expect(editor.props('scopeType')).toBe('institution');
    expect(editor.props('scopeId')).toBe('inst-1');
  });

  it('records a newly picked institution in the URL so the view is shareable', async () => {
    const visit = vi.spyOn(router, 'visit');
    const wrapper = mountPage(null);

    await wrapper.findComponent({ name: 'CollectionSelectDialog' }).vm.$emit('confirm', [
      { id: 'institution-inst-2', recordId: 'inst-2', title: 'MIF' },
    ]);

    // `recordId`, not `id`: the latter is the collection-prefixed search row key.
    expect(wrapper.findComponent({ name: 'DutiableTimelineEditor' }).props('scopeId')).toBe('inst-2');
    expect(visit).toHaveBeenCalled();
  });
});
