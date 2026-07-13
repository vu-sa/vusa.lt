import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ConsentCard from '../ConsentCard.vue';

const acceptAll = vi.fn();
const rejectAll = vi.fn();
const setAnalytics = vi.fn();
const analyticsAllowed = ref(false);

vi.mock('@/Composables/useCookieConsent', () => ({
  useCookieConsent: () => ({ acceptAll, rejectAll, setAnalytics, analyticsAllowed }),
}));

function mountCard() {
  return mount(ConsentCard, {
    global: {
      stubs: {
        Switch: {
          props: ['modelValue'],
          emits: ['update:modelValue'],
          template: '<button class="switch-stub" @click="$emit(\'update:modelValue\', !modelValue)" />',
        },
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
    acceptAll.mockClear();
    rejectAll.mockClear();
    setAnalytics.mockClear();
    analyticsAllowed.value = false;
  });

  it('offers both accept and decline with equal prominence', () => {
    const wrapper = mountCard();

    expect(findButtonByText(wrapper, 'Sutikti su visais')).toBeTruthy();
    expect(findButtonByText(wrapper, 'Tik būtinieji')).toBeTruthy();
  });

  it('accepting all opts in to analytics', async () => {
    const wrapper = mountCard();

    await findButtonByText(wrapper, 'Sutikti su visais')!.trigger('click');

    expect(acceptAll).toHaveBeenCalledTimes(1);
    expect(rejectAll).not.toHaveBeenCalled();
  });

  it('choosing necessary only declines analytics', async () => {
    const wrapper = mountCard();

    await findButtonByText(wrapper, 'Tik būtinieji')!.trigger('click');

    expect(rejectAll).toHaveBeenCalledTimes(1);
    expect(acceptAll).not.toHaveBeenCalled();
  });

  it('manage view saves the granular analytics choice', async () => {
    const wrapper = mountCard();

    await findButtonByText(wrapper, 'Tvarkyti')!.trigger('click');
    // Toggle the analytics switch on, then save.
    await wrapper.find('.switch-stub').trigger('click');
    await findButtonByText(wrapper, 'Išsaugoti pasirinkimus')!.trigger('click');

    expect(setAnalytics).toHaveBeenCalledWith(true);
  });

  it('seeds the analytics switch from the saved choice when reopened', async () => {
    analyticsAllowed.value = true;
    const wrapper = mountCard();

    await findButtonByText(wrapper, 'Tvarkyti')!.trigger('click');

    // Saving without touching the switch keeps the previously-stored "on" choice.
    await findButtonByText(wrapper, 'Išsaugoti pasirinkimus')!.trigger('click');

    expect(setAnalytics).toHaveBeenCalledWith(true);
  });
});
