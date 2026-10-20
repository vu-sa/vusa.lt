import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import EditAtstovavimasSettings from '../EditAtstovavimasSettings.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

let wrapper: ReturnType<typeof mount>;

function render(canEdit = true) {
  return mount(EditAtstovavimasSettings, {
    props: {
      canEdit,
      baseline_cadence_id: 'previous',
      baseline_cadences: [
        { id: 'previous', label: '2025–2026', start_date: '2025-07-01', end_date: '2026-06-30' },
        { id: 'older', label: '2024–2025', start_date: '2024-07-01', end_date: '2025-06-30' },
      ],
    },
    global: {
      stubs: {
        FormPage: { template: '<form @submit.prevent="$emit(\'submit\')"><slot /></form>' },
      },
    },
  });
}

afterEach(() => wrapper?.unmount());

describe('representation baseline settings', () => {
  it('shows the selected term dates and lets an editor choose automatic comparison', async () => {
    wrapper = render();
    const select = wrapper.get<HTMLSelectElement>('select#baseline_cadence_id');
    expect(select.element.value).toBe('previous');
    expect(select.text()).toContain('2025–2026');
    expect(select.text()).toContain('2025');
    await select.setValue('older');
    await wrapper.get('form').trigger('submit');
    const form = (wrapper.vm as unknown as { form: { baseline_cadence_id: string | null; post: ReturnType<typeof vi.fn> } }).form;
    expect(form.baseline_cadence_id).toBe('older');
    expect(form.post).toHaveBeenCalledWith(expect.stringContaining('settings.atstovavimas.update'));
    await select.setValue(select.find('option').element.value);
    expect(form.baseline_cadence_id).toBeNull();
  });

  it('prevents a settings reader from changing the baseline', () => {
    wrapper = render(false);
    expect(wrapper.get('select#baseline_cadence_id').attributes('disabled')).toBeDefined();
  });
});
