import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCNumberSection from '../RCNumberSection.vue';

import type { NumberStatSection } from '@/Types/contentParts';

function makeElement(content: NumberStatSection['json_content'] = []): NumberStatSection {
  return { json_content: content, options: { title: '' } };
}

const stubs = {
  RCSection: { template: '<section><slot /></section>' },
  NumberStatistic: { template: '<div><slot /></div>' },
  RCInlineText: { template: '<span><slot /></span>' },
  RCAddPlaceholder: { template: '<button data-add />' },
  Popover: { template: '<div><slot /></div>' },
  PopoverAnchor: true,
  PopoverContent: { template: '<div><slot /></div>' },
};

describe('RCNumberSection', () => {
  it('offers an on-canvas control that adds a statistic in full-screen editing mode', async () => {
    const wrapper = mount(RCNumberSection, {
      props: { element: makeElement(), editable: true, blockKey: 'stats-1' },
      global: { stubs },
    });

    await wrapper.find('[data-add]').trigger('click');

    expect(wrapper.emitted('update:element')).toEqual([[
      { json_content: [{ endNumber: 0, label: '' }], options: { title: '' } },
    ]]);
  });
});
