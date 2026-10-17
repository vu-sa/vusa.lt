import { flushPromises, mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import CommentComposer from '../CommentComposer.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mentionables = [{ id: 'u1', name: 'Ona Onaitė' }, { id: 'me', name: 'Aš Pats' }];

let wrapper: ReturnType<typeof mount>;

const mountComposer = async (props: Record<string, unknown> = {}) => {
  wrapper = mount(CommentComposer, { props: { mentionables, ...props }, attachTo: document.body });
  await flushPromises();
};

const mention = (id: string, label: string) => `<span data-type="mention" data-id="${id}" data-label="${label}">@${label}</span>`;

const mentionButton = () => wrapper.findAll('button').find(button => button.text() === 'Paminėti')!;

describe('CommentComposer', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { user: { id: 'me' } } }));
  });

  afterEach(() => {
    wrapper.unmount();
    document.body.innerHTML = '';
  });

  /** Nothing appears on focus, so the panel keeps its height when someone starts writing. */
  it('shows the editor and its actions before anyone clicks', async () => {
    await mountComposer();

    expect(wrapper.find('.ProseMirror').exists()).toBe(true);
    expect(mentionButton().exists()).toBe(true);
    expect(wrapper.text()).toContain('Komentuoti');
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
});
