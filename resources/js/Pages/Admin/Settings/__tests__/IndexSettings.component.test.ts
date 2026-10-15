import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import IndexSettings from '@/Pages/Admin/Settings/IndexSettings.vue';

function createWrapper(props: { isSuperAdmin?: boolean } = {}) {
  return mount(IndexSettings, {
    props: { isSuperAdmin: props.isSuperAdmin ?? false },
  });
}

const tileHrefs = (wrapper: ReturnType<typeof mount>, category: string) =>
  wrapper.findAll(`[data-category="${category}"] a`).map(link => link.attributes('href'));

describe('IndexSettings', () => {
  it('renders one navigation tile per general settings page', () => {
    const hrefs = tileHrefs(createWrapper(), 'general');

    expect(hrefs).toEqual([
      '/mocked-route/settings.forms.edit',
      '/mocked-route/settings.meetings.edit',
      '/mocked-route/settings.atstovavimas.edit',
      '/mocked-route/settings.documents.edit',
      '/mocked-route/settings.cadences.index',
      '/mocked-route/settings.site.edit',
    ]);
  });

  it('gives every tile a label and a description', () => {
    const wrapper = createWrapper();

    for (const tile of wrapper.findAll('[data-category="general"] a')) {
      expect(tile.find('[data-tile-label]').text()).toContain('.title');
      expect(tile.find('[data-tile-description]').text()).toContain('.description');
    }
  });

  it('hides the authorization section for non-super-admins', () => {
    const wrapper = createWrapper({ isSuperAdmin: false });

    expect(wrapper.text()).not.toContain('settings.categories.authorization');
    expect(wrapper.find('[data-category="authorization"]').exists()).toBe(false);
  });

  it('shows the authorization tile for super admins', () => {
    const wrapper = createWrapper({ isSuperAdmin: true });

    expect(wrapper.text()).toContain('settings.categories.authorization');
    expect(tileHrefs(wrapper, 'authorization')).toEqual(['/mocked-route/settings.authorization.edit']);
  });
});
