import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import PollBlock from '@/Components/Discussions/PollBlock.vue';
import type { PollData } from '@/Types/discussions';

function makePoll(overrides: Partial<PollData> = {}): PollData {
  return {
    options: [
      { id: 'a', label: 'Yes' },
      { id: 'b', label: 'No' },
    ],
    allow_multiple: false,
    closes_at: null,
    is_closed: false,
    total_votes: 3,
    tallies: [
      { option_id: 'a', count: 2, voters: [{ id: 'u1', name: 'Ada' }, { id: 'u2', name: 'Ben' }] },
      { option_id: 'b', count: 1, voters: [{ id: 'u3', name: 'Cira' }] },
    ],
    my_option_ids: ['a'],
    ...overrides,
  };
}

describe('PollBlock', () => {
  it('renders each option with its count and percentage, marking the viewer\'s choice', () => {
    const wrapper = mount(PollBlock, { props: { poll: makePoll() } });

    const options = wrapper.findAll('[data-testid="poll-option-vote"]');
    expect(options[0]!.text()).toContain('Yes');
    // 2 of 3 votes ≈ 67%
    expect(options[0]!.text()).toContain('67% · 2');
    expect(options[0]!.attributes('aria-pressed')).toBe('true');
    expect(options[1]!.attributes('aria-pressed')).toBe('false');
    expect(wrapper.text()).toContain('discussions.poll.who_voted');
  });

  it('lists who voted for each option on request', async () => {
    const wrapper = mount(PollBlock, { props: { poll: makePoll() }, attachTo: document.body });

    await wrapper.findAll('button').find(button => button.text() === 'discussions.poll.who_voted')!.trigger('click');

    expect(document.body.textContent).toContain('Ada, Ben');
    wrapper.unmount();
  });

  it('emits vote with the option id when an option is clicked', async () => {
    const wrapper = mount(PollBlock, { props: { poll: makePoll() } });

    await wrapper.findAll('button')[1].trigger('click'); // second option ("No")

    expect(wrapper.emitted('vote')?.[0]).toEqual(['b']);
  });

  it('does not emit vote when the poll is closed', async () => {
    const wrapper = mount(PollBlock, { props: { poll: makePoll({ is_closed: true }) } });

    await wrapper.findAll('button')[0].trigger('click');

    expect(wrapper.emitted('vote')).toBeUndefined();
  });
});
