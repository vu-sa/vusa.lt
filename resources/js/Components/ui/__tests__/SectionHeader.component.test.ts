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

  it('keeps the roomy gap and the hairline rule when the separator is shown (default)', () => {
    const wrapper = mount(SectionHeader, { props: { title: 'Sveiki' } });
    expect(rootClass(wrapper)).toContain('mb-10');
    expect(wrapper.find('.border-b.border-border').exists()).toBe(true);
  });

  it('tightens the gap and drops the rule when the separator is hidden', () => {
    const wrapper = mount(SectionHeader, { props: { title: 'Sveiki', showSeparator: false } });
    expect(rootClass(wrapper)).toContain('mb-6');
    expect(rootClass(wrapper)).not.toContain('mb-10');
    expect(wrapper.find('.border-b.border-border').exists()).toBe(false);
  });

  it('renders the brand eyebrow only when one is given', () => {
    expect(mount(SectionHeader, { props: { title: 'Sveiki' } }).find('.u-eyebrow').exists()).toBe(false);

    const withEyebrow = mount(SectionHeader, { props: { title: 'Sveiki', eyebrow: 'VU SA' } });
    expect(withEyebrow.find('.u-eyebrow').text()).toBe('VU SA');
  });
});
