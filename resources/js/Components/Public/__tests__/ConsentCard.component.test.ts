import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ConsentCard from '../ConsentCard.vue';

const acknowledge = vi.fn();

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

function findButtonByText(wrapper: ReturnType<typeof mount>, text: string) {
  return wrapper.findAll('button').find(b => b.text().includes(text));
}

describe('ConsentCard', () => {
  beforeEach(() => {
    acknowledge.mockClear();
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

  it('links to the privacy policy', () => {
    const wrapper = mountCard();

    expect(wrapper.find('a[href$="/privatumas"]').exists()).toBe(true);
  });
});
