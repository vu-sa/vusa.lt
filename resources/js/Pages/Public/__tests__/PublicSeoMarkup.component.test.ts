import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import ContentPage from '../ContentPage.vue';
import HomePage from '../HomePage.vue';

import PublicLayout from '@/Components/Public/Layouts/PublicLayout.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

const heroes = [1, 2].map(id => ({
  id,
  type: 'hero',
  json_content: { title: `<p>Section ${id}</p>`, description: '', buttons: [] },
  options: { variant: 'centered' },
}));

const stubs = {
  ...commonStubs,
  FeedbackPopover: true,
  HighlightsFloatingButton: true,
  TableOfContents: true,
};

beforeEach(() => {
  localStorage.clear();
  vi.mocked(usePage).mockReturnValue(createMockPage({ auth: { user: null } }));
});

describe('public page headings', () => {
  it.each([true, false])('retains one page heading with show_title=%s and multiple heroes', async (showTitle) => {
    const wrapper = mount(ContentPage, {
      props: {
        navigationItemId: 0,
        page: { title: 'Page title', show_title: showTitle, content: { parts: heroes } },
      },
      global: { stubs },
    });
    await vi.dynamicImportSettled();
    await flushPromises();

    expect(wrapper.findAll('h1')).toHaveLength(1);
    expect(wrapper.get('h1').text()).toBe('Page title');
    expect(wrapper.get('h1').classes().includes('sr-only')).toBe(!showTitle);
    expect(wrapper.findAll('[role="heading"][aria-level="2"]')).toHaveLength(2);
    expect(wrapper.find('[role="heading"][aria-level="1"]').exists()).toBe(false);
    wrapper.unmount();
  });

  it('retains a hidden heading when a title-less page contains no hero', () => {
    const wrapper = mount(ContentPage, {
      props: { navigationItemId: 0, page: { title: 'Page title', show_title: false, content: { parts: [] } } },
      global: { stubs },
    });

    expect(wrapper.get('h1.sr-only').text()).toBe('Page title');
    wrapper.unmount();
  });

  it('gives the home page its own heading before its hero sections', async () => {
    const wrapper = mount(HomePage, {
      props: { heading: 'Organisation name', content: { parts: heroes } },
      global: { stubs },
    });
    await vi.dynamicImportSettled();
    await flushPromises();

    expect(wrapper.findAll('h1')).toHaveLength(1);
    expect(wrapper.get('h1.sr-only').text()).toBe('Organisation name');
    expect(wrapper.findAll('[role="heading"][aria-level="2"]')).toHaveLength(2);
    expect(wrapper.findAll('h1, [role="heading"]')[0].text()).toBe('Organisation name');
    wrapper.unmount();
  });
});

it('excludes public chrome from snippets while keeping page content available', async () => {
  vi.mocked(usePage).mockReturnValue(createMockPage({
    auth: { user: null },
    tenant: { banners: [{ id: 1 }] },
  }));
  const wrapper = mount(PublicLayout, {
    slots: { default: '<h1>Page title</h1><p>Page content</p>' },
    global: {
      mocks: { $page: usePage() },
      stubs: {
        ...commonStubs,
        MainNavigation: { template: '<header data-testid="navigation">Navigation</header>' },
        SiteFooter: { template: '<footer>Footer</footer>' },
        PartnersBanner: { template: '<aside data-testid="partners">Partners</aside>' },
        ConsentCard: { template: '<div data-testid="consent">Consent</div>' },
        Toaster: true,
        StagingWatermark: true,
      },
    },
  });
  await flushPromises();

  for (const selector of ['header', 'footer', '[data-testid="partners"]', '[data-testid="consent"]']) {
    expect(wrapper.get(selector).element.closest('div[data-nosnippet]')).not.toBeNull();
  }
  expect(wrapper.get('main h1').element.closest('[data-nosnippet]')).toBeNull();
  expect(wrapper.get('main p').element.closest('[data-nosnippet]')).toBeNull();
  wrapper.unmount();
});
