import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import { transChoice } from 'laravel-vue-i18n';

import SummerCampCard from '@/Components/Public/SummerCamps/SummerCampCard.vue';

const tenant = {
  id: 3,
  alias: 'mif',
  fullname: 'Vilniaus universiteto Studentų atstovybė Matematikos ir informatikos fakultete',
};

const makeCamp = (overrides: Record<string, unknown> = {}) => ({
  id: 1,
  title: 'MIF pirmakursių stovykla',
  date: '2026-08-25T10:00:00',
  end_date: '2026-08-27T18:00:00',
  is_all_day: false,
  location: 'Molėtų r., Kulionių k.',
  main_image_url: '',
  cto_url: null,
  ...overrides,
}) as any;

const stubs = {
  SmartLink: { template: '<a :href="href"><slot /></a>', props: ['href'] },
  IFluentTent24Regular: { template: '<span class="icon-tent" />' },
  IFluentLocation20Regular: { template: '<span class="icon-location" />' },
  IFluentChevronRight12Regular: { template: '<span class="icon-chevron" />' },
};

const mountCard = (events: unknown[]) =>
  mount(SummerCampCard, {
    props: { tenant, events: events as any },
    global: { stubs },
  });

describe('SummerCampCard', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('names the faculty in the nominative', () => {
    wrapper = mountCard([makeCamp()]);

    expect(wrapper.text()).toContain('Matematikos ir informatikos fakultetas');
  });

  it('falls back to the full name when there is no faculty part', () => {
    wrapper = mount(SummerCampCard, {
      props: {
        tenant: { id: 1, alias: 'vusa', fullname: 'Vilniaus universiteto Studentų atstovybė' },
        events: [makeCamp()] as any,
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Vilniaus universiteto Studentų atstovybė');
  });

  it('renders every camp a faculty runs, each linking to its own event', () => {
    wrapper = mountCard([
      makeCamp({ id: 11 }),
      makeCamp({ id: 22, date: '2026-08-29T10:00:00', end_date: '2026-08-31T18:00:00' }),
    ]);

    const links = wrapper.findAll('li a');

    expect(links).toHaveLength(2);
    expect(links[0].attributes('href')).not.toBe(links[1].attributes('href'));
    expect(wrapper.findAll('li')).toHaveLength(2);
  });

  it('flags how many camps a faculty runs only when there is more than one', () => {
    expect(mountCard([makeCamp()]).text()).not.toContain('camp_count');

    wrapper = mountCard([makeCamp({ id: 11 }), makeCamp({ id: 22 })]);
    expect(wrapper.text()).toContain('2');
  });

  it('declines the camp count through the translation layer rather than hardcoding a form', () => {
    wrapper = mountCard([makeCamp({ id: 11 }), makeCamp({ id: 22 })]);

    // Lithuanian noun forms depend on the number, so the count must reach $tChoice.
    // The forms themselves are asserted in tests/Feature/Public/SummerCampsPageTest.php.
    expect(transChoice).toHaveBeenCalledWith('summerCamps.camp_count', 2);
  });

  it('shows the date span and location of each camp', () => {
    wrapper = mountCard([makeCamp()]);

    expect(wrapper.text()).toContain('Molėtų r., Kulionių k.');
    // Multi-day span collapses to a single "25–27" style range.
    expect(wrapper.text()).toContain('25');
    expect(wrapper.text()).toContain('27');
  });

  it('links to registration only when the camp has a registration URL', () => {
    expect(mountCard([makeCamp()]).find('a[target="_blank"]').exists()).toBe(false);

    wrapper = mountCard([makeCamp({ cto_url: 'https://vusa.lt/registracija' })]);

    const registration = wrapper.find('a[target="_blank"]');
    expect(registration.exists()).toBe(true);
    expect(registration.attributes('href')).toBe('https://vusa.lt/registracija');
  });

  it('falls back to a placeholder when no camp has a cover image', () => {
    wrapper = mountCard([makeCamp()]);

    expect(wrapper.find('img').exists()).toBe(false);
    // The cover area still renders, as a gradient placeholder rather than a blank gap.
    expect(wrapper.find('[class*="aspect-"]').exists()).toBe(true);
  });

  it('uses the first available cover image across the faculty camps', () => {
    wrapper = mountCard([
      makeCamp({ id: 11, main_image_url: '' }),
      makeCamp({ id: 22, main_image_url: '/storage/camp.jpg' }),
    ]);

    expect(wrapper.find('img').attributes('src')).toBe('/storage/camp.jpg');
  });
});
