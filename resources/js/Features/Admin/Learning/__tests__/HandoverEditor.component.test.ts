import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { HandoverNoteData, WritableHandoverDuty } from '@/Types/learning';
import { createMockPage } from '@/tests/helpers/createMockPage';
import CommentComposer from '@/Components/Discussions/CommentComposer.vue';
import HandoverEditor from '../HandoverEditor.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
  vi.mocked(router.post).mockClear();
  vi.mocked(router.patch).mockClear();
});

function render(note: HandoverNoteData | null = null) {
  const duty: WritableHandoverDuty = { dutyId: 'duty-1', title: 'Representative', note };
  return mount(HandoverEditor, {
    props: { duty },
    global: { stubs: {
      CommentComposer: {
        props: ['content', 'submitting', 'submitLabel'],
        emits: ['submit'],
        template: '<button :disabled="submitting" @click="$emit(\'submit\', \'<p>My notes</p>\')">{{ submitLabel }}</button>',
      },
    } },
  });
}

const existing: HandoverNoteData = {
  id: 'note-1', dutyId: 'duty-1', duty: 'Representative', author: { id: 'author', name: 'Author' },
  body: '<p>Keep my existing notes.</p>', createdAt: null, updatedAt: null,
};

describe('HandoverEditor', () => {
  it('prefills the five guided prompts only for a new note', () => {
    const fresh = render();
    const template = fresh.getComponent(CommentComposer).props('content');
    for (const key of ['institution', 'unfinished', 'people', 'documents', 'advice']) {
      expect(template).toContain(`<strong>learning_people.prompts.${key}</strong>`);
    }
    expect(fresh.text()).toContain('learning_people.readers');
    fresh.unmount();
    const saved = render(existing);
    expect(saved.getComponent(CommentComposer).props('content')).toBe(existing.body);
    saved.unmount();
    const blank = render({ ...existing, body: '' });
    expect(blank.getComponent(CommentComposer).props('content')).toBe('');
    blank.unmount();
  });

  it('posts a new note against its duty and blocks duplicate submits', async () => {
    const wrapper = render();
    await wrapper.get('button').trigger('click');
    expect(router.post).toHaveBeenCalledWith(route('handoverNotes.store', 'duty-1'), { body: '<p>My notes</p>' }, expect.objectContaining({ preserveScroll: true }));
    expect(router.patch).not.toHaveBeenCalled();
    expect(wrapper.get('button').attributes('disabled')).toBeDefined();
    wrapper.unmount();
  });

  it('patches an existing note rather than creating another', async () => {
    const wrapper = render(existing);
    await wrapper.get('button').trigger('click');
    expect(router.patch).toHaveBeenCalledWith(route('handoverNotes.update', 'note-1'), { body: '<p>My notes</p>' }, expect.any(Object));
    expect(router.post).not.toHaveBeenCalled();
    wrapper.unmount();
  });

  it('keeps the draft after validation errors and makes retry available', async () => {
    const wrapper = render(existing);
    await wrapper.get('button').trigger('click');
    const callbacks = vi.mocked(router.patch).mock.calls[0]?.[2] as {
      onError: (errors: Record<string, string>) => void;
      onFinish: () => void;
    };
    callbacks.onError({ body: 'Write a shorter note.' });
    callbacks.onFinish();
    await wrapper.vm.$nextTick();
    expect(wrapper.get('[role="alert"]').text()).toBe('Write a shorter note.');
    expect(wrapper.getComponent(CommentComposer).props('content')).toBe(existing.body);
    expect(wrapper.get('button').attributes('disabled')).toBeUndefined();
    wrapper.unmount();
  });
});
