import { afterEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import SnowGust from '../snow/SnowGust.vue';
import { useSeasonSnow } from '../useSeasonSnow';

const layer = (wrapper: ReturnType<typeof mount>) => wrapper.find('[data-slot="season-snow-gust"]');

describe('SnowGust', () => {
  afterEach(() => useSeasonSnow().stop());

  it('snows while the fir is shaken, then fades out before it goes', async () => {
    const wrapper = mount(SnowGust, { global: { stubs: { SnowFall: true } } });
    expect(layer(wrapper).exists()).toBe(false);

    useSeasonSnow().start();
    await wrapper.vm.$nextTick();
    expect(layer(wrapper).classes()).toContain('opacity-100');

    useSeasonSnow().stop();
    await wrapper.vm.$nextTick();
    expect(layer(wrapper).classes()).toContain('opacity-0');

    await layer(wrapper).trigger('transitionend');
    expect(layer(wrapper).exists()).toBe(false);
  });
});
