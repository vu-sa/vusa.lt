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
  it('renders each option with its count, percentage and voters', () => {
    const wrapper = mount(PollBlock, { props: { poll: makePoll() } });

    const text = wrapper.text();
    expect(text).toContain('Yes');
    expect(text).toContain('No');
    // 2 of 3 votes ≈ 67%
    expect(text).toContain('67%');
    expect(text).toContain('Ada, Ben');
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
