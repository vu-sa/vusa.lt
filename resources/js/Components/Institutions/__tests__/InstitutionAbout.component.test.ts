import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import InstitutionAbout from '../InstitutionAbout.vue';

describe('InstitutionAbout', () => {
  it('renders nothing without a description', () => {
    expect(mount(InstitutionAbout, { props: { description: '  ' } }).html()).toBe('<!--v-if-->');
  });

  it('summarises the description in two plain lines, keeping paragraphs apart', () => {
    const wrapper = mount(InstitutionAbout, { props: { description: '<p>Pirmas.</p><p>Antras <strong>sakinys</strong>.</p>' } });

    const summary = wrapper.get('p.line-clamp-2');
    expect(summary.text()).toBe('Pirmas. Antras sakinys.');
    expect(summary.find('strong').exists()).toBe(false);
  });

  it('offers the full text only when the summary is cut off', async () => {
    const wrapper = mount(InstitutionAbout, { props: { description: '<p>Ilgas aprašymas.</p>' } });
    expect(wrapper.find('[data-testid="institution-about-toggle"]').exists()).toBe(false);

    // jsdom has no layout, so give the clamped paragraph the measurements of an overflowing one.
    const summary = wrapper.get('p.line-clamp-2').element;
    Object.defineProperty(summary, 'scrollHeight', { configurable: true, value: 120 });
    Object.defineProperty(summary, 'clientHeight', { configurable: true, value: 40 });
    await wrapper.setProps({ description: '<p>Ilgas aprašymas, <em>kuris netelpa</em>.</p>' });
    await new Promise(resolve => requestAnimationFrame(resolve));

    await wrapper.get('[data-testid="institution-about-toggle"]').trigger('click');
    expect(wrapper.find('.prose em').text()).toBe('kuris netelpa');
  });
});

it('keeps a short link and media-only descriptions accessible', async () => {
  const wrapper = mount(InstitutionAbout, { props: { description: '<p><a href="https://vusa.lt">VU SA</a></p>' } });
  await wrapper.get('[data-testid="institution-about-toggle"]').trigger('click');
  expect(wrapper.get('a').attributes('href')).toBe('https://vusa.lt');
  await wrapper.setProps({ description: '<p><img src="/image.png" alt="Schema"></p>' });
  await wrapper.get('[data-testid="institution-about-toggle"]').trigger('click');
  expect(wrapper.get('img').attributes('alt')).toBe('Schema');
});
