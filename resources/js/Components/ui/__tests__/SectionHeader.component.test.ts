import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import SectionHeader from '../SectionHeader.vue';

/** The header's bottom gap lives on the root element — the first child rendered by the
 *  component (the wrapper <div> around the title/subtitle/separator). */
function rootClass(wrapper: ReturnType<typeof mount>) {
  return wrapper.element.className;
}

describe('SectionHeader', () => {
  it('renders the heading at the chosen semantic level', () => {
    const wrapper = mount(SectionHeader, { props: { title: 'Sveiki', level: 3 } });
    expect(wrapper.find('h3').exists()).toBe(true);
    expect(wrapper.find('h3').text()).toBe('Sveiki');
  });

  it('coerces an invalid level back to 2 rather than rendering an unknown tag', () => {
    const wrapper = mount(SectionHeader, { props: { title: 'Sveiki', level: 'bogus' as never } });
    expect(wrapper.find('h2').exists()).toBe(true);
  });

  it('keeps the roomy gap when the separator is shown (default)', () => {
    const wrapper = mount(SectionHeader, { props: { title: 'Sveiki' } });
    expect(rootClass(wrapper)).toContain('mb-12');
    expect(wrapper.find('.w-16.h-1').exists()).toBe(true);
  });

  it('tightens the gap when the separator is hidden', () => {
    const wrapper = mount(SectionHeader, { props: { title: 'Sveiki', showSeparator: false } });
    expect(rootClass(wrapper)).toContain('mb-6');
    expect(rootClass(wrapper)).not.toContain('mb-12');
    expect(wrapper.find('.w-16.h-1').exists()).toBe(false);
  });
});
