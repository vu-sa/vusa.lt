import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import CadenceSection from '../CadenceSection.vue';
import type { CadenceRow } from '../CadenceList.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function makeRow(overrides: Partial<CadenceRow> = {}): CadenceRow {
  return {
    id: 'global-2025',
    institution_id: null,
    start_date: '2025-07-01',
    end_date: '2026-06-30',
    label: '2025–2026',
    ...overrides,
  };
}

function mountSection(ownCadences: CadenceRow[], globalCadences: CadenceRow[]) {
  return mount(CadenceSection, {
    props: {
      institutionId: 'inst-1',
      ownCadences,
      globalCadences,
      defaults: { default_start_month_day: '07-01', default_end_month_day: '06-30' },
    },
    global: { stubs: commonStubs },
  });
}

const buttonByText = (wrapper: ReturnType<typeof mount>, text: string) =>
  wrapper.findAll('button').find(button => button.text().includes(text));

describe('CadenceSection', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] });
    vi.setSystemTime(new Date(2025, 10, 1));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('collapses to one line naming the shared term that applies now', () => {
    const wrapper = mountSection([], [makeRow(), makeRow({ id: 'global-2024', start_date: '2024-07-01', end_date: '2025-06-30', label: '2024–2025' })]);

    expect(wrapper.get('[data-slot="cadence-summary"]').text()).toContain('cadences.institution.summary_global');
    expect(wrapper.find('[data-slot="cadence-list"]').exists()).toBe(false);
  });

  it('opens straight into a prefilled first own term, with the warning', async () => {
    const wrapper = mountSection([], [makeRow()]);

    await buttonByText(wrapper, 'cadences.institution.customize')!.trigger('click');

    expect(wrapper.text()).toContain('cadences.institution.override_warning');
    const dates = wrapper.findAll('[data-slot="cadence-row-form"] input[type="date"]');
    expect(dates.map(input => (input.element as HTMLInputElement).value)).toEqual(['2026-07-01', '2027-06-30']);
  });

  it('reads the current term off the own terms once there are any', () => {
    const wrapper = mountSection(
      [makeRow({ id: 'own', institution_id: 'inst-1', start_date: '2025-09-01', end_date: '2026-08-31', label: 'own-term' })],
      [makeRow()],
    );

    expect(wrapper.get('[data-slot="cadence-summary"]').text()).toContain('cadences.institution.summary_own');
    expect(buttonByText(wrapper, 'cadences.institution.manage')).toBeDefined();
  });
});
