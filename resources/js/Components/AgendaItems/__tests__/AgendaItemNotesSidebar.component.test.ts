import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import AgendaItemNotesSidebar from '../AgendaItemNotesSidebar.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

// Controllable fake notes state, shared with the mocked composable.
const notesState = {
  doc: {},
  awareness: {},
  participants: ref<Array<{ id: string; name: string }>>([]),
  connectionState: ref('connected'),
  saveStatus: ref<'idle' | 'saving' | 'saved' | 'dirty'>('idle'),
  isHydrating: ref(false),
  notesHtml: ref(''),
  representatives: ref<Array<{ id: string; name: string }>>([]),
  currentUserColor: '#ef4444',
  setHtml: vi.fn(),
  flush: vi.fn(),
  destroy: vi.fn(),
};

const dismiss = vi.fn();

vi.mock('@/Composables/useAgendaItemNotes', () => ({
  useAgendaItemNotes: () => notesState,
}));

vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({
    isVisible: ref(true),
    isDismissed: ref(false),
    targetRef: ref(null),
    dismiss,
    reset: vi.fn(),
  }),
}));

function mountSidebar() {
  return mount(AgendaItemNotesSidebar, {
    props: { agendaItemId: 'item-1' },
    global: {
      stubs: {
        ...commonStubs,
        CollaborativeDocEditor: { name: 'CollaborativeDocEditor', template: '<div class="notes-editor-stub" />' },
        SpotlightPopover: { name: 'SpotlightPopover', template: '<div><slot /></div>' },
        UserAvatar: { name: 'UserAvatar', template: '<span class="user-avatar-stub" />' },
      },
    },
  });
}

describe('AgendaItemNotesSidebar', () => {
  beforeEach(() => {
    notesState.participants.value = [];
    notesState.saveStatus.value = 'idle';
    notesState.isHydrating.value = false;
    notesState.notesHtml.value = '';
    dismiss.mockClear();
  });

  it('shows the private notes header', () => {
    const wrapper = mountSidebar();
    expect(wrapper.text()).toContain('PRIVAT');
  });

  it('marks the feature as experimental', () => {
    const wrapper = mountSidebar();
    expect(wrapper.text()).toContain('Eksperiment');
  });

  it('renders the live editor when not hydrating and collapsed', () => {
    const wrapper = mountSidebar();
    expect(wrapper.find('.notes-editor-stub').exists()).toBe(true);
  });

  it('shows a skeleton (and no editor) while hydrating', async () => {
    notesState.isHydrating.value = true;
    const wrapper = mountSidebar();
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.animate-pulse').exists()).toBe(true);
    expect(wrapper.find('.notes-editor-stub').exists()).toBe(false);
  });

  it('caps the presence avatars at three and shows an overflow count', async () => {
    notesState.participants.value = [
      { id: '1', name: 'A' },
      { id: '2', name: 'B' },
      { id: '3', name: 'C' },
      { id: '4', name: 'D' },
      { id: '5', name: 'E' },
    ];
    const wrapper = mountSidebar();
    await wrapper.vm.$nextTick();
    expect(wrapper.findAll('.user-avatar-stub').length).toBe(3);
    expect(wrapper.text()).toContain('+2');
  });

  it('dismisses the spotlight when the expand button is clicked', async () => {
    const wrapper = mountSidebar();
    await wrapper.find('button').trigger('click');
    expect(dismiss).toHaveBeenCalled();
  });
});
