import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import PersonQuoteDisplay from '../PersonQuoteDisplay.vue';

function makeElement(overrides: Record<string, unknown> = {}) {
  return {
    type: 'person-quote',
    json_content: {
      quote: { type: 'doc', content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Narystė man daug davė.' }] }] },
      snapshot: { name: 'Vardenė Pavardenė', photoUrl: null, attribution: 'Koordinatorė, VU SA MIF' },
    },
    options: { align: 'center', showAvatar: true },
    ...overrides,
  };
}

describe('PersonQuoteDisplay', () => {
  it('renders the quote text and attribution', () => {
    const wrapper = mount(PersonQuoteDisplay, { props: { element: makeElement() } });
    expect(wrapper.text()).toContain('Narystė man daug davė.');
    expect(wrapper.text()).toContain('Vardenė Pavardenė');
    expect(wrapper.text()).toContain('Koordinatorė, VU SA MIF');
  });

  it('falls back to initials when there is no photo', () => {
    const wrapper = mount(PersonQuoteDisplay, { props: { element: makeElement() } });
    expect(wrapper.text()).toContain('VP');
  });

  it('hides the avatar row when showAvatar is false', () => {
    const wrapper = mount(PersonQuoteDisplay, {
      props: { element: makeElement({ options: { align: 'center', showAvatar: false } }) },
    });
    expect(wrapper.text()).not.toContain('Vardenė Pavardenė');
  });

  it('applies the anchor id from anchorId for ToC scroll targets', () => {
    const wrapper = mount(PersonQuoteDisplay, { props: { element: makeElement(), anchorId: 9 } });
    expect(wrapper.find('#rc-9').exists()).toBe(true);
  });
});
