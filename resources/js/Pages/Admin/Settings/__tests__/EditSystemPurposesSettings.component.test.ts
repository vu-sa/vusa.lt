import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import EditSystemPurposesSettings from '../EditSystemPurposesSettings.vue';

const { dismiss } = vi.hoisted(() => ({ dismiss: vi.fn() }));

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(false), dismiss }),
}));

const mountSettings = (canEdit = true) => mount(EditSystemPurposesSettings, {
  props: {
    canEdit,
    purposes: [{ key: 'reservation_handling', label: 'Rezervacijų administravimas', drives: 'Gavėjai', responsibility_id: 'role-1' }],
    responsibilities: [{ id: 'role-1', title: 'Rezervacijų administravimas' }],
    dutyTypes: [],
    dutyTypeOptions: [],
  },
  global: {
    stubs: {
      FormPage: { template: '<form><slot /></form>' },
      FormSection: { template: '<section><slot /></section>' },
      FormFieldWrapper: { template: '<div><slot /></div>' },
      SpotlightPopover: { template: '<div data-testid="spotlight"><slot /></div>' },
      NativeSelect: { template: '<select />' },
      TypeTreeSelect: true,
    },
  },
});

beforeEach(() => {
  vi.clearAllMocks();
  vi.stubGlobal('route', (name: string) => `/mocked/${name}`);
});

describe('reservation purpose discovery', () => {
  it('dismisses the discovery hint when the manager engages the selector', async () => {
    const wrapper = mountSettings();

    await wrapper.get('[data-testid="spotlight"] select').trigger('pointerdown');

    expect(dismiss).toHaveBeenCalledOnce();
  });

  it('does not show a management spotlight to a read-only viewer', () => {
    expect(mountSettings(false).find('[data-testid="spotlight"]').exists()).toBe(false);
  });
});
