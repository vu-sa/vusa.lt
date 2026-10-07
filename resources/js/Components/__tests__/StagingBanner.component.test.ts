import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { afterEach, describe, expect, it, vi } from 'vitest';

import StagingBanner from '@/Components/StagingBanner.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function mockStaging(staging: Record<string, boolean>) {
  vi.mocked(usePage).mockReturnValue(createMockPage({ staging }));
}

const mountBanner = (props: Record<string, boolean> = {}) => mount(StagingBanner, { props, global: { stubs: commonStubs } });

describe('StagingBanner', () => {
  afterEach(() => {
    vi.clearAllMocks();
  });

  it('stays one line and keeps the differences for the details dialog', async () => {
    mockStaging({ isStaging: true, filesReadOnly: true, sharepointReadOnly: true, mailToRequester: true });

    const wrapper = mountBanner();
    const status = wrapper.get('[data-slot="staging-status"]');

    expect(status.text()).toContain('staging.title');
    expect(status.text()).toContain('staging.summary');
    expect(wrapper.find('[data-slot="staging-details"]').exists()).toBe(false);

    await wrapper.get('[data-slot="staging-details-button"]').trigger('click');

    const details = wrapper.get('[data-slot="staging-details"]').text();
    expect(details).toContain('staging.topics.reset');
    expect(details).toContain('staging.topics.files');
    expect(details).toContain('staging.topics.sharepoint_read_only');
    expect(details).toContain('staging.topics.mail');
  });

  it('tells reviewers SharePoint writes go to the test site when staging SharePoint is writable', async () => {
    mockStaging({ isStaging: true, filesReadOnly: false, sharepointReadOnly: false });

    const wrapper = mountBanner();
    await wrapper.get('[data-slot="staging-details-button"]').trigger('click');
    const details = wrapper.get('[data-slot="staging-details"]').text();

    expect(details).toContain('staging.topics.sharepoint_test_site');
    expect(details).not.toContain('staging.topics.files');
    expect(details).not.toContain('staging.topics.mail');
  });

  it('collapses to a square warning control and reopens the notice', async () => {
    mockStaging({ isStaging: true, filesReadOnly: false, sharepointReadOnly: false });

    const wrapper = mountBanner({ dismissed: false });

    await wrapper.get('button[aria-label="staging.collapse"]').trigger('click');
    expect(wrapper.emitted('update:dismissed')?.[0]).toEqual([true]);

    await wrapper.setProps({ dismissed: true });
    expect(wrapper.html()).toBe('<!--v-if-->');

    await wrapper.setProps({ compact: true });
    const control = wrapper.get('[data-slot="staging-warning-button"]');
    expect(control.classes()).toContain('size-11');
    expect(control.attributes('aria-label')).toContain('staging.summary');
    await control.trigger('click');
    expect(wrapper.emitted('update:dismissed')?.[1]).toEqual([false]);

    await wrapper.setProps({ dismissed: false, compact: false });
    expect(wrapper.find('[data-slot="staging-status"]').exists()).toBe(true);
  });

  it('does not render outside staging', () => {
    mockStaging({ isStaging: false, filesReadOnly: false, sharepointReadOnly: false });

    expect(mountBanner().find('[data-slot="staging-status"]').exists()).toBe(false);
  });
});
