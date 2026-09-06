import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@/Composables/useApi', () => ({
  useApi: () => ({
    data: { value: null },
    isFetching: { value: false },
    execute: vi.fn(),
  }),
}));

import TimetableBlockToolbar from '../TimetableBlockToolbar.vue';
import type { ContentPart } from '../../Types';

function makeContent(overrides: Partial<ContentPart> = {}): ContentPart {
  return {
    type: 'timetable',
    json_content: [
      { startTime: '10:00', endTime: '11:00', title: 'Atidarymas' },
    ],
    options: { title: 'Dienotvarkė' },
    ...overrides,
  };
}

describe('TimetableBlockToolbar', () => {
  it('renders title input and rows list', () => {
    const wrapper = mount(TimetableBlockToolbar, {
      props: {
        content: makeContent(),
        blockKey: 'tt-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: {
        stubs: {
          RCBlockToolbarShell: { template: '<div><slot /></div>' },
          RCWidthPicker: true,
        },
      },
    });

    expect(wrapper.text()).toContain('rich-content.rows (1)');
    expect(wrapper.text()).toContain('Atidarymas');
  });

  it('emits update:content when adding a row', async () => {
    const wrapper = mount(TimetableBlockToolbar, {
      props: {
        content: makeContent(),
        blockKey: 'tt-1',
        canMoveUp: true,
        canMoveDown: true,
        canDelete: true,
      },
      global: {
        stubs: {
          RCBlockToolbarShell: { template: '<div><slot /></div>' },
          RCWidthPicker: true,
        },
      },
    });

    const addBtn = wrapper.findAll('button').find(b => b.text().includes('rich-content.add_timetable_row'));
    expect(addBtn).toBeDefined();
    await addBtn!.trigger('click');

    expect(wrapper.emitted('update:content')).toBeTruthy();
    const emitted = wrapper.emitted('update:content')?.[0]?.[0] as ContentPart;
    expect((emitted.json_content as unknown[]).length).toBe(2);
  });
});
