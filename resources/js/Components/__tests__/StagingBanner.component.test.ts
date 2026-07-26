import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { afterEach, describe, expect, it, vi } from 'vitest';

import StagingBanner from '@/Components/StagingBanner.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('StagingBanner', () => {
  afterEach(() => {
    vi.clearAllMocks();
  });

  it('renders staging information in normal flow', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      staging: {
        isStaging: true,
        filesReadOnly: true,
        sharepointReadOnly: true,
      },
    }));

    const wrapper = mount(StagingBanner);
    const status = wrapper.get('[data-slot="staging-status"]');

    expect(status.text()).toContain('STAGING ENVIRONMENT');
    expect(status.text()).toContain('File storage is shared with production');
    expect(status.text()).toContain('SharePoint is shared with production');
    expect(status.classes()).toContain('rounded-xl');
    expect(status.classes()).not.toContain('fixed');
    expect(status.classes()).not.toContain('shadow-lg');
  });

  it('dismisses the staging notice without leaving a spacer', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      staging: {
        isStaging: true,
        filesReadOnly: false,
        sharepointReadOnly: false,
      },
    }));

    const wrapper = mount(StagingBanner);

    await wrapper.get('button[aria-label="Dismiss staging banner"]').trigger('click');

    expect(wrapper.html()).toBe('<!--v-if-->');
  });

  it('does not render outside staging', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      staging: {
        isStaging: false,
        filesReadOnly: false,
        sharepointReadOnly: false,
      },
    }));

    const wrapper = mount(StagingBanner);

    expect(wrapper.find('[data-slot="staging-status"]').exists()).toBe(false);
  });
});
