import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { commonStubs } from '@/tests/stubs';
import CommentReactions from '@/Components/Discussions/CommentReactions.vue';

describe('CommentReactions', () => {
  it('draws each reaction as an icon chip with its count and the viewer\'s choice', async () => {
    const wrapper = mount(CommentReactions, {
      props: {
        reactions: [
          { emoji: '👍', count: 2, reacted_by_me: true, users: [{ id: 'u1', name: 'Ona' }, { id: 'u2', name: 'Jonas' }] },
          { emoji: '🎉', count: 1, reacted_by_me: false, users: [{ id: 'u3', name: 'Rūta' }] },
        ],
      },
      global: { stubs: commonStubs },
    });

    const chips = wrapper.findAll('[data-testid="reaction-chip"]');
    expect(chips).toHaveLength(2);
    expect(chips[0]!.text()).toBe('2');
    expect(chips[0]!.find('svg').exists()).toBe(true);
    expect(wrapper.text()).not.toContain('👍');
    expect(chips[0]!.attributes('aria-pressed')).toBe('true');
    expect(chips[1]!.attributes('aria-pressed')).toBe('false');

    await chips[1]!.trigger('click');
    expect(wrapper.emitted('toggle')?.[0]).toEqual(['🎉']);
  });
});
