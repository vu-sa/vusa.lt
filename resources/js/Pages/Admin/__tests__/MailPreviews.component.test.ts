import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import MailPreviews from '../MailPreviews.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const previews = [
  { key: 'digest-single', label: 'Single notification', group: 'Digests', subjects: { lt: 'Vienas pranešimas', en: 'One notification' } },
  { key: 'feedback', label: 'Feedback', group: 'Other', subjects: { lt: 'Atsiliepimas', en: 'Feedback' } },
];

const factory = () => mount(MailPreviews, { props: { previews } });

function expectedSrc(preview = 'digest-single', format = 'html', locale = 'lt') {
  return route('mailPreviews.render', { preview, format, locale });
}

describe('MailPreviews', () => {
  it('selects another email without navigating the page and shows its subject', async () => {
    const wrapper = factory();
    expect(wrapper.get('iframe').attributes('src')).toBe(expectedSrc());

    await wrapper.get('[data-preview="feedback"]').trigger('click');

    expect(wrapper.get('iframe').attributes('src')).toBe(expectedSrc('feedback'));
    expect(wrapper.get('[data-testid="subject"]').text()).toBe('Atsiliepimas');
    expect(wrapper.get('iframe').attributes('sandbox')).toBe('');
  });

  it('switches phone and desktop widths without changing the email', async () => {
    const wrapper = factory();
    expect(wrapper.get('iframe').element.style.width).toBe('640px');

    await wrapper.get('[data-testid="width-390"]').trigger('click');
    expect(wrapper.get('iframe').element.style.width).toBe('390px');
    expect(wrapper.get('iframe').attributes('src')).toBe(expectedSrc());

    await wrapper.get('[data-testid="width-640"]').trigger('click');
    expect(wrapper.get('iframe').element.style.width).toBe('640px');
  });

  it('changes the requested format and locale and displays the localized subject', async () => {
    const wrapper = factory();
    await wrapper.get('[data-testid="format-text"]').trigger('click');
    expect(wrapper.get('iframe').attributes('src')).toBe(expectedSrc('digest-single', 'text'));

    await wrapper.get('[data-testid="locale-en"]').trigger('click');
    expect(wrapper.get('iframe').attributes('src')).toBe(expectedSrc('digest-single', 'text', 'en'));
    expect(wrapper.get('[data-testid="subject"]').text()).toBe('One notification');

    await wrapper.get('[data-testid="format-html"]').trigger('click');
    expect(wrapper.get('iframe').attributes('src')).toBe(expectedSrc('digest-single', 'html', 'en'));
  });

  it('searches the list without losing the selected preview', async () => {
    const wrapper = factory();
    await wrapper.get('input[type="search"]').setValue('Feedback');

    expect(wrapper.find('[data-preview="digest-single"]').exists()).toBe(false);
    expect(wrapper.find('[data-preview="feedback"]').exists()).toBe(true);
    expect(wrapper.get('iframe').attributes('src')).toBe(expectedSrc());
  });

  it('renders an empty catalog without an iframe', () => {
    const wrapper = mount(MailPreviews, { props: { previews: [] } });
    expect(wrapper.find('iframe').exists()).toBe(false);
  });
});
