import { flushPromises, mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { createMockPage } from '@/tests/helpers/createMockPage';
import type { CommentDraft } from '@/Types/discussions';

import CommentComposer from '../CommentComposer.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mentionables = [{ id: 'u1', name: 'Ona Onaitė', profile_photo_path: null }, { id: 'me', name: 'Aš Pats', profile_photo_path: null }];

let wrapper: ReturnType<typeof mount>;

const mountComposer = async (props: Record<string, unknown> = {}) => {
  wrapper = mount(CommentComposer, { props: { mentionables, ...props }, attachTo: document.body });
  await flushPromises();
};

const mention = (id: string, label: string) => `<span data-type="mention" data-id="${id}" data-label="${label}">@${label}</span>`;

const mentionButton = () => wrapper.find('[data-testid="comment-mention"]');
const submitButton = () => wrapper.find('[data-testid="comment-submit"]');
const submitted = () => wrapper.emitted('submit')?.[0]?.[0] as CommentDraft | undefined;

async function type(html: string) {
  const { editor } = (wrapper.vm as unknown as { $: { setupState: { editor: { commands: { setContent: (value: string, options?: unknown) => void } } } } }).$.setupState;
  editor.commands.setContent(html, { emitUpdate: true });
  await flushPromises();
}

describe('CommentComposer', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { user: { id: 'me', name: 'Aš Pats' } } }));
  });

  afterEach(() => {
    wrapper.unmount();
    document.body.innerHTML = '';
  });

  it('rests as a single line in the panel and opens its actions on focus', async () => {
    await mountComposer({ collapsible: true });

    expect(wrapper.find('.ProseMirror').exists()).toBe(true);
    expect(wrapper.find('[data-slot="composer-footer"]').exists()).toBe(false);

    await wrapper.find('.ProseMirror').trigger('focus');
    (wrapper.vm as unknown as { $: { setupState: { focused: boolean } } }).$.setupState.focused = true;
    await flushPromises();

    expect(wrapper.find('[data-slot="composer-footer"]').exists()).toBe(true);
  });

  it('keeps formatting tools behind the formatting toggle', async () => {
    await mountComposer();

    expect(wrapper.find('[data-testid="comment-toolbar"]').exists()).toBe(false);
    await wrapper.find('[data-testid="comment-formatting-toggle"]').trigger('click');

    expect(wrapper.find('[data-testid="comment-toolbar"]').exists()).toBe(true);
  });

  it('starts a mention from the button, as typing @ would', async () => {
    await mountComposer();

    await mentionButton().trigger('click');
    await flushPromises();

    expect(wrapper.find('.ProseMirror').text()).toBe('@');
    expect(document.body.textContent).toContain('Ona Onaitė');
  });

  it('tells the author who will be notified of a mention, leaving out themselves', async () => {
    await mountComposer();

    const pick = async (name: string) => {
      await mentionButton().trigger('click');
      await flushPromises();
      [...document.querySelectorAll<HTMLButtonElement>('[data-comment-mention-menu] button')]
        .find(button => button.textContent?.includes(name))!
        .click();
      await flushPromises();
    };
    await pick('Ona Onaitė');
    await pick('Aš Pats');

    const notice = wrapper.find('[data-testid="mention-notice"]');
    expect(notice.text()).toContain('Ona Onaitė');
    expect(notice.text()).not.toContain('Aš Pats');
  });

  /** Only a new comment notifies mentioned people; an edit does not. */
  it('promises no notification while editing', async () => {
    await mountComposer({ quietMentions: true, content: `<p>${mention('u1', 'Ona Onaitė')}</p>` });

    expect(wrapper.find('[data-testid="mention-notice"]').exists()).toBe(false);
  });

  it('submits formatted HTML as a comment draft with the chosen duty', async () => {
    await mountComposer({
      content: '<ul><li><p>First</p></li></ul>',
      duties: [{ dutiable_id: 'd1', name: 'Koordinatorius', institution: 'MIF SA', use_original_duty_name: false }],
      defaultDutiableId: 'd1',
    });

    expect(wrapper.find('[data-testid="author-duty-label"]').text()).toContain('MIF SA');
    await submitButton().trigger('click');

    expect(submitted()).toMatchObject({ mode: 'comment', dutiableId: 'd1', imageIds: [] });
    expect(submitted()!.body).toContain('<ul><li><p>First</p></li></ul>');
  });

  it('offers a choice instead of a preselected duty when only former duties remain', async () => {
    await mountComposer({
      duties: [{ dutiable_id: 'old', name: 'Koordinatorius', institution: 'MIF SA', use_original_duty_name: false, start_date: '2023-09-01', end_date: '2024-06-30', is_current: false }],
      defaultDutiableId: null,
    });

    expect(wrapper.find('[data-testid="author-duty-label"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="author-duty-picker"]').text()).toContain('discussions.author_duty.none');
  });

  it('renders a duty that arrives without a name', async () => {
    await mountComposer({
      duties: [
        { dutiable_id: 'a', name: null, institution: 'Senatas', tenant: 'VU SA', use_original_duty_name: false, is_current: true },
        { dutiable_id: 'b', name: 'Koordinatorius', institution: 'MIF SA', tenant: 'VU SA MIF', use_original_duty_name: false, is_current: true },
      ],
      defaultDutiableId: 'a',
    });

    expect(wrapper.find('[data-testid="author-duty-picker"]').text()).toContain('VU SA');
  });

  it('treats an empty paragraph as empty after loading HTML', async () => {
    await mountComposer({ content: '<p></p>' });

    await vi.waitFor(() => expect(submitButton().attributes('disabled')).toBeDefined());
    await submitButton().trigger('click');
    expect(wrapper.emitted('submit')).toBeUndefined();
  });

  it('needs a title before a topic can start, then sends it with the body', async () => {
    await mountComposer({ modes: ['topic', 'comment'] });
    await type('<p>Kaip ruoštis?</p>');

    expect(submitButton().attributes('disabled')).toBeDefined();
    await wrapper.find('[data-testid="composer-topic-title"]').setValue('Pirmas posėdis');
    await submitButton().trigger('click');

    expect(submitted()).toMatchObject({ mode: 'topic', title: 'Pirmas posėdis' });
  });

  it('asks for two poll options before posting a poll', async () => {
    await mountComposer({ modes: ['comment', 'poll'] });
    await wrapper.find('[data-testid="composer-mode-poll"]').trigger('click');
    await type('<p>Kada susitinkam?</p>');

    await submitButton().trigger('click');
    expect(wrapper.emitted('submit')).toBeUndefined();
    expect(wrapper.find('[role="alert"]').exists()).toBe(true);

    await wrapper.find('[data-testid="poll-yes-no"]').trigger('click');
    await submitButton().trigger('click');

    expect(submitted()).toMatchObject({ mode: 'poll', poll: { options: [{ label: 'Taip' }, { label: 'Ne' }], allow_multiple: false, closes_at: null } });
  });

  it('keeps poll options when switching to another tab and back', async () => {
    await mountComposer({ modes: ['comment', 'poll'] });
    await wrapper.find('[data-testid="composer-mode-poll"]').trigger('click');
    await wrapper.find('[data-testid="poll-yes-no"]').trigger('click');
    await wrapper.find('[data-testid="composer-mode-comment"]').trigger('click');
    await wrapper.find('[data-testid="composer-mode-poll"]').trigger('click');

    expect(wrapper.findAll<HTMLInputElement>('[data-testid="poll-option"]').map(input => input.element.value)).toEqual(['Taip', 'Ne']);
  });

  it('returns to the suggested duty after posting, so a one-off former duty does not stick', async () => {
    await mountComposer({
      duties: [
        { dutiable_id: 'now', name: 'Koordinatorius', institution: 'MIF SA', use_original_duty_name: false, is_current: true },
        { dutiable_id: 'old', name: 'Atstovas', institution: 'MIF taryba', use_original_duty_name: false, is_current: false },
      ],
      defaultDutiableId: 'now',
    });
    (wrapper.vm as unknown as { $: { setupState: { dutiableId: string } } }).$.setupState.dutiableId = 'old';
    await type('<p>Iš patirties</p>');
    await submitButton().trigger('click');
    expect(submitted()).toMatchObject({ dutiableId: 'old' });

    (wrapper.vm as unknown as { reset: () => void }).reset();
    await flushPromises();
    expect((wrapper.vm as unknown as { $: { setupState: { dutiableId: string } } }).$.setupState.dutiableId).toBe('now');
  });

  it('dismisses suggestions with Escape and removes a popup when the editor unmounts', async () => {
    await mountComposer();
    await mentionButton().trigger('click');
    await flushPromises();
    expect(document.querySelector('[data-comment-mention-menu]')).not.toBeNull();

    await wrapper.find('.ProseMirror').trigger('keydown', { key: 'Escape' });
    await flushPromises();
    expect(document.querySelector('[data-comment-mention-menu]')).toBeNull();

    await mentionButton().trigger('click');
    await flushPromises();
    expect(document.querySelector('[data-comment-mention-menu]')).not.toBeNull();
    wrapper.unmount();
    expect(document.querySelector('[data-comment-mention-menu]')).toBeNull();
  });
});
