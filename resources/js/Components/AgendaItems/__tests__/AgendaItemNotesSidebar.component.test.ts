import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import { commonStubs } from '@/tests/stubs';

import AgendaItemNotesSidebar from '../AgendaItemNotesSidebar.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

// Controllable fake notes state, shared with the mocked composable.
const notesState = {
  doc: {},
  awareness: {},
  participants: ref<Array<{ id: string; name: string }>>([]),
  connectionState: ref('connected'),
  saveStatus: ref<'idle' | 'saving' | 'saved' | 'dirty'>('idle'),
  isHydrating: ref(false),
  loadFailed: ref(false),
  recoveredDraft: ref(''),
  recoveredAt: ref<number | null>(null),
  recoveredText: ref(''),
  resolveDraft: vi.fn(),
  retryLoad: vi.fn(),
  notesHtml: ref(''),
  representatives: ref<Array<{ id: string; name: string }>>([]),
  currentUserColor: '#ef4444',
  setHtml: vi.fn(),
  flush: vi.fn(),
  destroy: vi.fn(),
};

vi.mock('@/Composables/useAgendaItemNotes', () => ({
  useAgendaItemNotes: () => notesState,
}));

function mountSidebar() {
  return mount(AgendaItemNotesSidebar, {
    props: { agendaItemId: 'item-1' },
    global: {
      stubs: {
        ...commonStubs,
        AgendaItemNotesEditor: { name: 'AgendaItemNotesEditor', template: '<div class="notes-editor-stub" />' },
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
    notesState.loadFailed.value = false;
    notesState.recoveredDraft.value = '';
    notesState.recoveredAt.value = null;
    notesState.recoveredText.value = '';
    notesState.resolveDraft.mockReset();
    notesState.notesHtml.value = '';
  });

  it('offers a retry instead of an editor when the notes could not be loaded', async () => {
    notesState.loadFailed.value = true;
    const wrapper = mountSidebar();

    expect(wrapper.find('.notes-editor-stub').exists()).toBe(false);
    await wrapper.find('[role="alert"] button').trigger('click');
    expect(notesState.retryLoad).toHaveBeenCalledOnce();
  });

  it('shows the private notes header', () => {
    const wrapper = mountSidebar();
    expect(wrapper.text()).toContain('PRIVAT');
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

  it('shows the recovered draft preview and lets the user merge or discard it', async () => {
    notesState.recoveredDraft.value = '{"state":"abc"}';
    notesState.recoveredAt.value = 1700000000000;
    notesState.recoveredText.value = 'Pirma eilutė\nAntra eilutė';

    const wrapper = mountSidebar();
    expect(wrapper.find('.notes-editor-stub').exists()).toBe(false);
    expect(wrapper.text()).toContain('Pirma eilutė');
    expect(wrapper.text()).not.toContain('Eksperiment');

    const buttons = wrapper.findAll('button');
    await buttons[0]!.trigger('click');
    await buttons[1]!.trigger('click');
    expect(notesState.resolveDraft).toHaveBeenNthCalledWith(1, true);
    expect(notesState.resolveDraft).toHaveBeenNthCalledWith(2, false);
  });
});
