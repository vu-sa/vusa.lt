import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { ref } from 'vue';

import ResourcePreview from '../ResourcePreview.vue';

import ReservationResourceStateTag from '@/Components/Tag/ReservationResourceStateTag.vue';
import { commonStubs } from '@/tests/stubs';

const baseResource = {
  id: 'r1',
  name_lt: 'Kameros',
  name_en: 'Cameras',
  category_name: 'Technika',
  location: 'Kabinetas',
  capacity: 5,
  is_reservable: true,
  tenant_shortname: 'MIF',
};

interface ReservationPreview {
  id: string;
  name: string;
  quantity: number;
  state: string;
  start_time: number | null;
  end_time: number | null;
}

interface ResourcePreviewData {
  upcoming_reservations: ReservationPreview[];
  previous_reservations: ReservationPreview[];
  managers: Array<{
    id: string;
    name: string;
    email: string | null;
    phone: string | null;
    facebook_url: string | null;
    profile_photo_path: string | null;
  }>;
}

const mockController = {
  data: ref<ResourcePreviewData | null>(null),
  isLoading: ref(false),
};
const usePreviewApi = vi.hoisted(() => vi.fn());

vi.mock('@/Composables/usePreviewApi', () => ({ usePreviewApi }));

const mountDetail = (props: Record<string, unknown> = {}, slots: Record<string, string> = {}) =>
  mount(ResourcePreview, {
    props: { resource: baseResource, ...props },
    slots,
    global: { stubs: commonStubs },
  });

describe('ResourcePreview', () => {
  beforeEach(() => {
    mockController.data.value = null;
    mockController.isLoading.value = false;
    usePreviewApi.mockReset().mockReturnValue(mockController);
  });

  it('renders resource facts', () => {
    const wrapper = mountDetail();

    expect(wrapper.text()).toContain('Kameros');
    expect(wrapper.text()).toContain('Technika');
    expect(wrapper.text()).toContain('Kabinetas');
    expect(wrapper.text()).toContain('MIF');
  });

  it('shows availability box in select mode', () => {
    const wrapper = mountDetail({
      showAvailabilityBox: true,
      availability: {
        id: 'r1',
        capacity: 5,
        is_reservable: true,
        lowestCapacityAtDateTimeRange: 2,
        reservations: [],
        discrepancies: [],
      },
    });

    expect(wrapper.text()).toContain('2 / 5');
  });

  it('flags unavailable resources in the availability box', () => {
    const wrapper = mountDetail({
      showAvailabilityBox: true,
      availability: {
        id: 'r1',
        capacity: 5,
        is_reservable: true,
        lowestCapacityAtDateTimeRange: 0,
        reservations: [],
        discrepancies: [],
      },
    });

    expect(wrapper.text()).toContain('Šiuo laikotarpiu išteklius nepasiekiamas.');
  });

  it('shows discrepancy warning when active reservations ended but still occupy capacity', () => {
    const wrapper = mountDetail({
      showAvailabilityBox: true,
      availability: {
        id: 'r1',
        capacity: 5,
        is_reservable: true,
        lowestCapacityAtDateTimeRange: 2,
        reservations: [],
        discrepancies: [
          { id: 'res-1', name: 'Old booking', quantity: 1, state: 'lent', start_time: 1_600_000_000, end_time: 1_600_010_000 },
        ],
      },
    });

    expect(wrapper.text()).toContain('reservations.discrepancy.available_from_ended_reservations');
  });

  it('opens the resource unless the page supplies its own actions', () => {
    expect(mountDetail().find('[data-slot="preview-actions"] a').attributes('href')).toMatch(/^\/mocked-route\/resources\.show/);

    const custom = mountDetail({}, { actions: '<button class="reserve">Rezervuoti</button>' });
    expect(custom.find('[data-slot="preview-actions"] .reserve').exists()).toBe(true);
    expect(custom.find('[data-slot="preview-actions"] a').exists()).toBe(false);
  });

  it('does not fetch preview data in select mode', () => {
    mountDetail({ showAvailabilityBox: true });

    expect(usePreviewApi).toHaveBeenCalledWith(expect.any(String), false);
  });

  it('shows skeleton loaders for active reservations while fetching', () => {
    mockController.isLoading.value = true;
    const wrapper = mountDetail({ showUpcomingReservations: true });

    expect(wrapper.findAll('.animate-pulse').length).toBeGreaterThan(0);
  });

  it('renders active reservations with state and period when data is loaded', () => {
    mockController.data.value = {
      upcoming_reservations: [
        { id: 'res-1', name: 'Renginys', quantity: 2, state: 'reserved', start_time: 1_700_000_000, end_time: 1_700_010_000 },
      ],
      previous_reservations: [],
      managers: [],
    };

    const wrapper = mountDetail({ showUpcomingReservations: true });

    expect(wrapper.text()).toContain('Aktyvios rezervacijos');
    expect(wrapper.text()).toContain('Renginys');
    expect(wrapper.text()).toContain(':count vnt.');
    expect(wrapper.find('a[href*="/mocked-route/reservations.show"]').exists()).toBe(true);
  });

  it('flags active reservations whose window ended with an unresolved indicator', () => {
    mockController.data.value = {
      upcoming_reservations: [
        { id: 'res-1', name: 'Old active', quantity: 1, state: 'reserved', start_time: 1_600_000_000, end_time: 1_600_010_000 },
      ],
      previous_reservations: [],
      managers: [],
    };

    const wrapper = mountDetail({ showUpcomingReservations: true });

    // The tag capitalizes its label and hangs the unresolved hint off a title attribute, so assert
    // on what it is handed rather than on the rendered text.
    const tag = wrapper.findComponent(ReservationResourceStateTag);

    expect(wrapper.text()).toContain('Old active');
    expect(tag.props('state')).toBe('reserved');
    expect(tag.props('unresolved')).toBe(true);
  });

  it('renders previous terminal reservations when enabled', () => {
    mockController.data.value = {
      upcoming_reservations: [],
      previous_reservations: [
        { id: 'res-2', name: 'Senas renginys', quantity: 1, state: 'returned', start_time: 1_600_000_000, end_time: 1_600_010_000 },
      ],
      managers: [],
    };

    const wrapper = mountDetail({ showPreviousReservations: true });

    expect(wrapper.text()).toContain('Ankstesnės rezervacijos');
    expect(wrapper.text()).toContain('Senas renginys');
    expect(wrapper.find('a[href*="/mocked-route/reservations.show"]').exists()).toBe(true);
  });

  it('renders managers when data is loaded', () => {
    mockController.data.value = {
      upcoming_reservations: [],
      previous_reservations: [],
      managers: [{ id: 'u1', name: 'Jonas Jonaitis', email: 'jonas@example.com', phone: '+37060000000', facebook_url: null, profile_photo_path: null }],
    };

    const wrapper = mountDetail({ showManagers: true });

    expect(wrapper.text()).toContain('reservations.resource.managers');
    expect(wrapper.get('a[href="mailto:jonas@example.com"]').text()).toBe('Jonas Jonaitis');
  });
});
