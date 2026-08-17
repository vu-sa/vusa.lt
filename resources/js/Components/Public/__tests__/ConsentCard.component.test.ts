import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { usePage } from '@inertiajs/vue3';

import ConsentCard from '../ConsentCard.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

const acknowledge = vi.fn();

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('@/Composables/useCookieConsent', () => ({
  useCookieConsent: () => ({ acknowledge }),
}));

function mountCard() {
  return mount(ConsentCard, {
    global: {
      stubs: {
        IFluentCookies24Regular: { template: '<span />' },
      },
    },
  });
}

function withPrivacyPageUrl(privacyPageUrl: string | null) {
  vi.mocked(usePage).mockReturnValue(
    createMockPage({ organization: { privacyPageUrl } }),
  );
}

function findButtonByText(wrapper: ReturnType<typeof mount>, text: string) {
  return wrapper.findAll('button').find(b => b.text().includes(text));
}

describe('ConsentCard', () => {
  beforeEach(() => {
    acknowledge.mockClear();
    withPrivacyPageUrl('https://www.vusa.test/lt/privatumas');
  });

  it('states that visit statistics involve no cookies and no personal data', () => {
    const wrapper = mountCard();

    expect(wrapper.text()).toContain('Lankomumo statistiką renkame be slapukų ir be asmens duomenų.');
  });

  it('offers no analytics opt-in — Umami is cookieless, so there is nothing to consent to', () => {
    const wrapper = mountCard();

    expect(wrapper.find('.switch-stub').exists()).toBe(false);
    expect(findButtonByText(wrapper, 'Tik būtinieji')).toBeUndefined();
    expect(findButtonByText(wrapper, 'Sutikti su visais')).toBeUndefined();
  });

  it('dismisses the notice on acknowledge', async () => {
    const wrapper = mountCard();

    await findButtonByText(wrapper, 'Supratau')!.trigger('click');

    expect(acknowledge).toHaveBeenCalledTimes(1);
  });

  it('links to the privacy policy page resolved from SiteSettings', () => {
    const wrapper = mountCard();

    // The URL is resolved server-side (locale-correct, permalink-correct); the component only
    // renders whatever it is handed. It used to hardcode `${app.url}/privatumas`, which sent
    // English visitors to the Lithuanian page.
    expect(wrapper.find('a[href="https://www.vusa.test/lt/privatumas"]').exists()).toBe(true);
  });

  it('hides the privacy link when no page is configured', () => {
    withPrivacyPageUrl(null);

    const wrapper = mountCard();

    expect(wrapper.find('a').exists()).toBe(false);
    // The acknowledge button must still be there.
    expect(findButtonByText(wrapper, 'Supratau')).toBeDefined();
  });
});
