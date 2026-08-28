import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
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
    template: '<div class="picker-stub" />',
    emits: ['confirm', 'update:open'],
  },
  DutiableTimelineEditor: {
    name: 'DutiableTimelineEditor',
    props: ['scopeType', 'scopeId', 'height'],
    template: '<div class="editor-stub" />',
  },
};

type Inst = { id: string; name: string };

function mountPage(initialInstitution: Inst | null, userInstitutions: Inst[] = []) {
  return mount(DutiableTimeline, {
    props: { initialInstitution, userInstitutions },
    global: { stubs },
  });
}

const STORAGE_KEY = 'dutiable-timeline-institution';

describe('Admin/People/DutiableTimeline', () => {
  beforeEach(() => {
    localStorage.clear();
    window.history.replaceState({}, '', '/mano/dutiables/timeline');
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

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

  it('names the institution on the switcher rather than the action', () => {
    const wrapper = mountPage({ id: 'inst-1', name: 'Parlamentas' });

    expect(wrapper.text()).toContain('Parlamentas');
  });

  it('reopens on the last institution rather than the server guess', async () => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ id: 'inst-9', name: 'MIF' }));
    const visit = vi.spyOn(router, 'visit');

    const wrapper = mountPage({ id: 'inst-1', name: 'Parlamentas' });
    await wrapper.vm.$nextTick();

    expect(wrapper.findComponent({ name: 'DutiableTimelineEditor' }).props('scopeId')).toBe('inst-9');
    expect(visit).toHaveBeenCalled();
  });

  it('leaves an institution named in the URL alone', async () => {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ id: 'inst-9', name: 'MIF' }));
    window.history.replaceState({}, '', '/mano/dutiables/timeline?institution=inst-1');

    const wrapper = mountPage({ id: 'inst-1', name: 'Parlamentas' });
    await wrapper.vm.$nextTick();

    expect(wrapper.findComponent({ name: 'DutiableTimelineEditor' }).props('scopeId')).toBe('inst-1');
  });
});
