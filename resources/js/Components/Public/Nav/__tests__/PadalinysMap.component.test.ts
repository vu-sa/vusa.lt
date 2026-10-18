import { flushPromises, mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';
import Leaflet from 'leaflet';

import PadalinysMap from '../PadalinysMap.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.mock('leaflet', () => {
  const map = {
    addLayer: vi.fn(),
    fitBounds: vi.fn(),
    invalidateSize: vi.fn(),
    remove: vi.fn(),
    setView: vi.fn(),
    getCenter: () => [54.68, 25.28],
    getZoom: () => 13,
    stop: vi.fn(),
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
  it('recreates map and cluster motion options when the preference changes', async () => {
    const preferences = useAccessibilityPreferences();
    preferences.reset();
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { path: 'lt' } }));
    const wrapper = mount(PadalinysMap, {
      attachTo: document.body,
      props: { faculties: [], facultyLocations: {}, onFacultySelect: vi.fn(), searchQuery: '' },
    });
    try {
      await vi.dynamicImportSettled();
      await flushPromises();
      expect(Leaflet.map).toHaveBeenLastCalledWith('padalinys-leaflet-map', expect.objectContaining({ zoomAnimation: true }));
      preferences.reduceMotion.value = true;
      await vi.dynamicImportSettled();
      await flushPromises();
      expect(Leaflet.map).toHaveBeenLastCalledWith('padalinys-leaflet-map', expect.objectContaining({
        zoomAnimation: false, fadeAnimation: false, markerZoomAnimation: false, inertia: false,
      }));
      expect(Leaflet.markerClusterGroup).toHaveBeenLastCalledWith(expect.objectContaining({ animate: false }));
    }
    finally {
      wrapper.unmount();
      preferences.reset();
    }
  });

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
