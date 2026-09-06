import { flushPromises, mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';

import PadalinysMap from '../PadalinysMap.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('leaflet', () => {
  const map = {
    addLayer: vi.fn(),
    fitBounds: vi.fn(),
    invalidateSize: vi.fn(),
    remove: vi.fn(),
    setView: vi.fn(),
  };
  map.setView.mockReturnValue(map);

  const createControl = () => {
    const control = {
      addAttribution: vi.fn(),
      addTo: vi.fn(),
    };
    control.addAttribution.mockReturnValue(control);
    control.addTo.mockReturnValue(control);

    return control;
  };

  const tileLayer = {
    addTo: vi.fn(),
    remove: vi.fn(),
  };
  tileLayer.addTo.mockReturnValue(tileLayer);

  return {
    default: {
      control: {
        attribution: vi.fn(createControl),
        zoom: vi.fn(createControl),
      },
      map: vi.fn(() => map),
      markerClusterGroup: vi.fn(() => ({
        addLayer: vi.fn(),
        clearLayers: vi.fn(),
        on: vi.fn(),
      })),
      tileLayer: vi.fn(() => tileLayer),
    },
  };
});

vi.mock('leaflet.markercluster', () => ({}));

describe('PadalinysMap.vue', () => {
  it('uses the themed muted surface while map tiles load', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { path: 'lt' } }));
    const wrapper = mount(PadalinysMap, {
      props: {
        faculties: [],
        facultyLocations: {},
        onFacultySelect: vi.fn(),
        searchQuery: '',
      },
    });

    expect(wrapper.get('#padalinys-leaflet-map').classes()).toContain('!bg-muted');

    // Leaflet generates the controls at runtime; jsdom cannot evaluate their scoped CSS or dark cascade.
    await flushPromises();
    wrapper.unmount();
  });
});
