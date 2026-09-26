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

    expect(status.text()).toContain('Bandomoji aplinka');
    expect(status.text()).toContain('Failų saugykla bendrinama');
    expect(status.text()).toContain('SharePoint bendrinama');
    expect(status.classes()).toContain('rounded-xl');
    expect(status.classes()).not.toContain('fixed');
    expect(status.classes()).not.toContain('shadow-lg');
  });

  it('tells reviewers SharePoint writes go to the test site when staging SharePoint is writable', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      staging: {
        isStaging: true,
        filesReadOnly: true,
        sharepointReadOnly: false,
      },
    }));

    const text = mount(StagingBanner).get('[data-slot="staging-status"]').text();

    expect(text).toContain('SharePoint failai įkeliami į bandomąją svetainę');
    expect(text).not.toContain('SharePoint bendrinama su tikrąja aplinka');
  });

  it('collapses to a square warning control and reopens the full notice', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      staging: {
        isStaging: true,
        filesReadOnly: false,
        sharepointReadOnly: false,
      },
    }));

    const wrapper = mount(StagingBanner, { props: { dismissed: false } });

    await wrapper.get('button[aria-label="Suskleisti bandomosios aplinkos įspėjimą"]').trigger('click');
    expect(wrapper.emitted('update:dismissed')?.[0]).toEqual([true]);

    await wrapper.setProps({ dismissed: true });
    expect(wrapper.html()).toBe('<!--v-if-->');

    await wrapper.setProps({ compact: true });
    const control = wrapper.get('[data-slot="staging-warning-button"]');
    expect(control.classes()).toContain('size-11');
    expect(control.attributes('aria-label')).toContain('SharePoint failai įkeliami');
    await control.trigger('click');
    expect(wrapper.emitted('update:dismissed')?.[1]).toEqual([false]);

    await wrapper.setProps({ dismissed: false, compact: false });
    expect(wrapper.find('[data-slot="staging-status"]').exists()).toBe(true);
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
