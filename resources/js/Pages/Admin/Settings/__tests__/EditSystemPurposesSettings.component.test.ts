import { useForm } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi, afterEach } from 'vitest';
import { nextTick, ref } from 'vue';

import { scopedGlobals } from '@/tests/helpers/scopedGlobals';

import EditSystemPurposesSettings from '../EditSystemPurposesSettings.vue';

const { stubGlobal, restoreGlobals } = scopedGlobals();
afterEach(restoreGlobals);

const { dismiss } = vi.hoisted(() => ({ dismiss: vi.fn() }));

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(false), dismiss }),
}));

const mountSettings = (canEdit = true, typeCount = 0) => mount(EditSystemPurposesSettings, {
  props: {
    canEdit,
    purposes: [{ key: 'reservation_handling', label: 'Rezervacijų administravimas', drives: 'Gavėjai', responsibility_id: 'role-1' }],
    responsibilities: [{ id: 'role-1', title: 'Rezervacijų administravimas' }],
    dutyTypes: typeCount ? [{ key: 'coordinator', label: 'Koordinatoriai', drives: 'Viešieji kontaktai', type_id: 1, effective_type_id: 1 }] : [],
    dutyTypeOptions: Array.from({ length: typeCount }, (_, index) => ({ id: index + 1, title: `Tipas ${index + 1}`, parent_id: null })),
  },
  global: {
    stubs: {
      FormPage: { template: '<form><slot /></form>' },
      FormPanel: { template: '<section><slot /></section>' },
      SpotlightPopover: { template: '<div data-testid="spotlight"><slot /></div>' },
    },
  },
});

beforeEach(() => {
  vi.clearAllMocks();
  stubGlobal('route', (name: string) => `/mocked/${name}`);
});

describe('field validation descriptions', () => {
  it.each([16, 17])('connects the controls to their messages with %i duty types', async (count) => {
    const wrapper = mountSettings(true, count);
    const form = vi.mocked(useForm).mock.results.at(-1)!.value;
    const fields = [
      ['purpose_reservation_handling', 'purposes.reservation_handling', 'Gavėjai'],
      ['duty_type_coordinator', 'duty_types.coordinator', 'Viešieji kontaktai'],
    ] as const;

    for (const [, key] of fields) form.errors[key] = 'Pasirink kitą reikšmę';
    await nextTick();

    for (const [id, key, hint] of fields) {
      const control = wrapper.get(`select#${id}, input#${id}`);
      expect(wrapper.get<HTMLLabelElement>(`label[for="${id}"]`).element.control).toBe(control.element);
      expect(control.attributes('aria-invalid')).toBe('true');
      const [hintId, errorId] = control.attributes('aria-describedby')!.split(' ');
      expect(wrapper.get(`#${hintId}`).text()).toBe(hint);
      expect(wrapper.get(`#${errorId}`).text()).toBe('Pasirink kitą reikšmę');

      delete form.errors[key];
      await nextTick();

      expect(control.attributes('aria-describedby')).toBe(hintId);
      expect(control.attributes('aria-invalid')).not.toBe('true');
      expect(wrapper.find(`#${errorId}`).exists()).toBe(false);
    }
  });

  it('describes the reservation selector when the spotlight is absent', () => {
    const wrapper = mountSettings(false);
    const select = wrapper.get('select#purpose_reservation_handling');
    expect(wrapper.get(`#${select.attributes('aria-describedby')}`).text()).toBe('Gavėjai');
  });
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
