import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { handoverFields, type HandoverNoteData, type WritableHandoverDuty } from '@/Types/learning';
import { createMockPage } from '@/tests/helpers/createMockPage';
import HandoverEditor from '../HandoverEditor.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
  vi.mocked(router.post).mockClear();
  vi.mocked(router.patch).mockClear();
});

function render(note: HandoverNoteData | null = null) {
  const duty: WritableHandoverDuty = {
    dutyId: 'duty-1', title: 'Representative', note, notes: note ? [note] : [],
    cadenceId: 'cadence', cadenceLabel: '2026–2027', termStart: '2026-07-01', author: { id: 'author', name: 'Author' },
  };
  return mount(HandoverEditor, { props: { duty } });
}

const existing: HandoverNoteData = {
  id: 'note-1', dutyId: 'duty-1', duty: 'Representative', seatDutyId: 'duty-1', seatTitle: 'Representative',
  cadenceId: 'cadence', cadenceLabel: '2026–2027', termStart: '2026-07-01', author: { id: 'author', name: 'Author' },
  situation: 'Keep my existing notes.\nSecond line.', open_issues: null, people: 'Rūta', resources: null, advice: null,
  createdAt: null, updatedAt: null,
};

describe('HandoverEditor', () => {
  it('renders five empty fields with labels and hints and prefills saved plain text', () => {
    const fresh = render();
    expect(fresh.findAll('textarea')).toHaveLength(5);
    for (const field of handoverFields) {
      const textarea = fresh.get<HTMLTextAreaElement>(`textarea[name="${field}"]`);
      expect(textarea.element.value).toBe('');
      expect(textarea.attributes('maxlength')).toBe('5000');
      expect(fresh.get(`label[for="${textarea.attributes('id')}"]`).text()).toBeTruthy();
      expect(fresh.get(`#${textarea.attributes('aria-describedby')}`).text()).toBeTruthy();
    }
    fresh.unmount();
    const saved = render(existing);
    expect(saved.get<HTMLTextAreaElement>('textarea[name="situation"]').element.value).toBe(existing.situation);
    expect(saved.get<HTMLTextAreaElement>('textarea[name="people"]').element.value).toBe('Rūta');
    expect(saved.get<HTMLTextAreaElement>('textarea[name="advice"]').element.value).toBe('');
    saved.unmount();
  });

  it('posts five fields against the duty and blocks duplicate submits', async () => {
    const wrapper = render();
    await wrapper.get('textarea[name="situation"]').setValue('My notes\nSecond line');
    await wrapper.get('form').trigger('submit');
    expect(router.post).toHaveBeenCalledWith(route('handoverNotes.store', 'duty-1'), {
      situation: 'My notes\nSecond line', open_issues: '', people: '', resources: '', advice: '',
    }, expect.objectContaining({ preserveScroll: true }));
    await wrapper.get('form').trigger('submit');
    expect(router.post).toHaveBeenCalledTimes(1);
    expect(router.patch).not.toHaveBeenCalled();
    expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeDefined();
    wrapper.unmount();
  });

  it('patches the current entry and keeps the draft after a field validation error', async () => {
    const wrapper = render(existing);
    await wrapper.get('textarea[name="advice"]').setValue('Ask your coordinator.');
    await wrapper.get('form').trigger('submit');
    expect(router.patch).toHaveBeenCalledWith(route('handoverNotes.update', 'note-1'), {
      situation: existing.situation, open_issues: '', people: 'Rūta', resources: '', advice: 'Ask your coordinator.',
    }, expect.any(Object));
    expect(router.post).not.toHaveBeenCalled();
    const callbacks = vi.mocked(router.patch).mock.calls[0]?.[2] as {
      onError: (errors: Record<string, string>) => void;
      onFinish: () => void;
    };
    callbacks.onError({ advice: 'Write a shorter note.' });
    callbacks.onFinish();
    await wrapper.vm.$nextTick();
    expect(wrapper.text()).toContain('Write a shorter note.');
    expect(wrapper.find('[role="alert"]').exists()).toBe(true);
    expect(wrapper.get('textarea[name="advice"]').attributes('aria-invalid')).toBe('true');
    expect(wrapper.get<HTMLTextAreaElement>('textarea[name="advice"]').element.value).toBe('Ask your coordinator.');
    expect(wrapper.get('button[type="submit"]').attributes('disabled')).toBeUndefined();
    wrapper.unmount();
  });
});
