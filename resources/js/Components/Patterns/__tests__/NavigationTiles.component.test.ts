import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import { NavigationTiles } from '..';

const IconStub = { template: '<svg class="tile-icon" />' };

const items = [
  { key: 'naujienos', href: '/mano/news', label: 'Naujienos', description: 'Straipsniai ir pranešimai', icon: IconStub },
  { key: 'zymos', href: '/mano/tags', label: 'Žymos', icon: IconStub },
];

describe('NavigationTiles', () => {
  it('renders one link per item with its label, description and icon', () => {
    const wrapper = mount(NavigationTiles, { props: { items } });

    const links = wrapper.findAll('a');
    expect(links.map(link => link.attributes('href'))).toEqual(['/mano/news', '/mano/tags']);
    expect(links[0].find('[data-tile-label]').text()).toBe('Naujienos');
    expect(links[0].find('[data-tile-description]').text()).toBe('Straipsniai ir pranešimai');
    expect(links[0].find('.tile-icon').exists()).toBe(true);
  });

  it('leaves the description out when an item has none', () => {
    const wrapper = mount(NavigationTiles, { props: { items } });

    expect(wrapper.find('[data-tile="zymos"] [data-tile-description]').exists()).toBe(false);
  });

  it('emits navigate with the item key when a tile is clicked', async () => {
    const wrapper = mount(NavigationTiles, { props: { items } });

    await wrapper.find('[data-tile="zymos"]').trigger('click');

    expect(wrapper.emitted('navigate')).toEqual([['zymos']]);
  });
});
