import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import IndexSettings from '@/Pages/Admin/Settings/IndexSettings.vue';

/**
 * jsdom cannot model Tailwind's hover:/dark: variants — the visual card look is
 * intentionally not asserted here. We assert the wiring instead: card chrome
 * classes shared with ShowAdministration, link targets, and section visibility.
 */

function createWrapper(props: { isSuperAdmin?: boolean } = {}) {
  return mount(IndexSettings, {
    props: { isSuperAdmin: props.isSuperAdmin ?? false },
    global: {
      stubs: {
        PageContent: { template: '<div><slot /></div>' },
      },
    },
  });
}

const cardLinks = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAll('a[data-testid="inertia-link"]');

describe('IndexSettings', () => {
  it('renders one administration-style card per general settings page', () => {
    const wrapper = createWrapper();
    const links = cardLinks(wrapper);

    expect(links).toHaveLength(6);

    const hrefs = links.map(link => link.attributes('href'));
    expect(hrefs).toContain('/mocked-route/settings.forms.edit');
    expect(hrefs).toContain('/mocked-route/settings.meetings.edit');
    expect(hrefs).toContain('/mocked-route/settings.atstovavimas.edit');
    expect(hrefs).toContain('/mocked-route/settings.documents.edit');
    expect(hrefs).toContain('/mocked-route/settings.cadences.index');
    expect(hrefs).toContain('/mocked-route/settings.site.edit');
  });

  it('uses the ShowAdministration card chrome on every card', () => {
    const wrapper = createWrapper();

    for (const link of cardLinks(wrapper)) {
      const hoverWrapper = link.element.parentElement as HTMLElement;
      expect(hoverWrapper.classList.contains('group')).toBe(true);

      const card = link.element.firstElementChild as HTMLElement;
      expect(card.classList.contains('bg-linear-to-br')).toBe(true);
      expect(card.classList.contains('group-hover:ring-1')).toBe(true);
      expect(card.classList.contains('dark:from-zinc-900')).toBe(true);
    }
  });

  it('renders a title and description inside each card', () => {
    const wrapper = createWrapper();

    for (const link of cardLinks(wrapper)) {
      expect(link.text()).toContain('.title');
      expect(link.text()).toContain('.description');
    }

    expect(wrapper.text()).toContain('settings.categories.general');
  });

  it('hides the authorization section for non-super-admins', () => {
    const wrapper = createWrapper({ isSuperAdmin: false });

    expect(wrapper.text()).not.toContain('settings.categories.authorization');
    expect(cardLinks(wrapper)).toHaveLength(6);
  });

  it('shows the authorization card for super admins', () => {
    const wrapper = createWrapper({ isSuperAdmin: true });

    expect(wrapper.text()).toContain('settings.categories.authorization');

    const hrefs = cardLinks(wrapper).map(link => link.attributes('href'));
    expect(hrefs).toContain('/mocked-route/settings.authorization.edit');
  });
});
