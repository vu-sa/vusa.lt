<template>
  <div class="padalinys-map">
    <div class="relative h-[350px] w-full bg-muted overflow-hidden">
      <div id="padalinys-leaflet-map" class="h-full w-full !bg-muted" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDark } from '@vueuse/core';
import type * as Leaflet from 'leaflet';
import type { Layer, Map as LeafletMap, MarkerClusterGroup, TileLayer } from 'leaflet';

type LeafletApi = typeof Leaflet;
type ZoomTransitionMap = LeafletMap & {
  _animatingZoom?: boolean;
  _onZoomTransitionEnd?: () => void;
};

interface DropdownOption {
  label: string;
  key: string;
  primary_institution?: {
    short_name?: string;
    image_url?: string;
  };
  isMainOffice?: boolean;
}

interface FacultyLocation {
  lat: number;
  lng: number;
  city: 'vilnius' | 'kaunas' | 'siauliai';
}

interface Props {
  faculties: DropdownOption[];
  onFacultySelect: (key: string) => void;
  searchQuery: string;
  facultyLocations: Record<string, FacultyLocation>;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  'update:hoveredLocation': [option: DropdownOption | null];
}>();

// Importing leaflet dynamically to avoid SSR issues
let L: LeafletApi | null = null;
let leafletMap: ZoomTransitionMap | null = null;
let markers: Layer[] = [];
let markerClusterGroup: MarkerClusterGroup | null = null;
let suppressMarkerClicksUntil = 0;

const hoveredLocation = ref<DropdownOption | null>(null);
const isMapInitialized = ref(false);
const mapCreationAttempted = ref(false);

// Get theme state for dark mode detection
const isDark = useDark();

// CARTO now requires an API key on tile requests, passed as `key` (not `api_key`) per their docs.
const cartoApiKey = usePage().props.map?.cartoApiKey;
const tileUrlSuffix = cartoApiKey ? `?key=${encodeURIComponent(cartoApiKey)}` : '';

// Map tile URLs for light and dark modes
const mapTileUrls = {
  light: `https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png${tileUrlSuffix}`,
  dark: `https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png${tileUrlSuffix}`,
};

// Map tile layer - will be initialized once we know the theme
let tileLayer: TileLayer | null = null;

const isActivePadalinys = (key: string): boolean => {
  return usePage().props.tenant?.alias === key;
};

const vilniusFaculties = computed(() => {
  if (!props.searchQuery) {
    return props.faculties.filter(
      option => props.facultyLocations[option.key]?.city === 'vilnius',
    );
  }

  return props.faculties.filter(option =>
    props.facultyLocations[option.key]?.city === 'vilnius' && (
      option.label.toLowerCase().includes(props.searchQuery.toLowerCase())
      || option.key.toLowerCase().includes(props.searchQuery.toLowerCase())
    ),
  );
});

const filteredFaculties = computed(() => {
  if (!props.searchQuery) {
    return props.faculties;
  }

  return props.faculties.filter(option =>
    option.label.toLowerCase().includes(props.searchQuery.toLowerCase())
    || option.key.toLowerCase().includes(props.searchQuery.toLowerCase()),
  );
});

// Set hovered location and emit to parent
const setHoveredLocation = (option: DropdownOption | null) => {
  hoveredLocation.value = option;
  emit('update:hoveredLocation', option);
};

// Update renderAvatarToHTML to create proper HTML directly
const renderAvatarToHTML = (option: DropdownOption, isActive: boolean): string => {
  // Get avatar URL from primary institution
  const avatarUrl = option.primary_institution?.image_url;
  const { key } = option;
  const { isMainOffice } = option;
  const avatarClasses = `map-avatar ${isActive ? 'active' : ''} ${isMainOffice ? 'main-office' : ''}`;

  if (avatarUrl) {
    return `
      <div class="${avatarClasses}" data-slot="avatar">
        <img src="${avatarUrl}" alt="${option.label}" class="h-full w-full object-cover" />
      </div>
    `;
  }

  const fallbackText = key.substring(0, 2).toUpperCase();

  return `
    <div class="${avatarClasses}" data-slot="avatar">
      <div class="flex h-full w-full items-center justify-center bg-muted text-muted-foreground">
        ${fallbackText}
      </div>
    </div>
  `;
};

// Initialize map when component is mounted
onMounted(async () => {
  await nextTick();
  initializeOrUpdateMap();
});

// Watch for changes to the faculties or search query
watch(() => [props.faculties, props.searchQuery], () => {
  if (isMapInitialized.value) {
    updateMapMarkers();
  }
}, { deep: true });

// Watch for theme changes and update map tiles accordingly
watch(isDark, (newIsDark) => {
  if (L && leafletMap && tileLayer) {
    tileLayer.remove();

    tileLayer = L.tileLayer(newIsDark ? mapTileUrls.dark : mapTileUrls.light, {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 19,
    }).addTo(leafletMap);
  }
});

// Hover and focus can request initialization again while the dynamic imports are pending.
let isInitializingMap = false;

/** Leaflet leaves its zoom timer alive after `remove()`, so finish an active transition first. */
const removeMapSafely = (instance: ZoomTransitionMap): void => {
  if (instance._animatingZoom) {
    instance._onZoomTransitionEnd?.();
  }
  instance.remove();
};

// Initialize or recreate the map
const initializeOrUpdateMap = async () => {
  if (typeof window === 'undefined' || isInitializingMap) return;

  isInitializingMap = true;

  try {
    // Clean up any existing map
    if (leafletMap) {
      removeMapSafely(leafletMap);
      leafletMap = null;
      markers = [];
      markerClusterGroup = null;
      tileLayer = null;
    }

    mapCreationAttempted.value = true;

    // Dynamically import Leaflet and Leaflet.MarkerCluster
    const leaflet = await import('leaflet') as LeafletApi & { default: LeafletApi };
    L = leaflet.default;

    // Import Leaflet and MarkerCluster CSS
    await import('leaflet/dist/leaflet.css');

    await import('leaflet.markercluster');
    await import('leaflet.markercluster/dist/MarkerCluster.css');
    await import('leaflet.markercluster/dist/MarkerCluster.Default.css');

    // Initialize map if container exists
    const container = document.getElementById('padalinys-leaflet-map');
    if (container) {
      // Closing the popover during imports can leave Leaflet's stamp on the reused node.
      const leafletContainer = container as HTMLElement & { _leaflet_id?: number };
      if (leafletContainer._leaflet_id) {
        delete leafletContainer._leaflet_id;
      }

      // Center on Vilnius
      leafletMap = L.map('padalinys-leaflet-map', {
        zoomControl: false, // Disable default zoom control for cleaner look
        attributionControl: false, // We'll add attribution in a more subtle way
        // Replacing a focused cluster icon during zoom can close the hover-driven popover.
        keyboard: false,
      }).setView([54.683333, 25.286944], 13);

      // Add tile layer based on current theme
      tileLayer = L.tileLayer(isDark.value ? mapTileUrls.dark : mapTileUrls.light, {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
        subdomains: 'abcd',
        maxZoom: 19,
      }).addTo(leafletMap);

      // Add custom attribution control in a more subtle way
      L.control.attribution({
        position: 'bottomright',
        prefix: '',
      }).addAttribution('© <a href="https://www.openstreetmap.org/copyright" class="text-xs opacity-70">OpenStreetMap</a>').addTo(leafletMap);

      // Add custom zoom control in a more subtle position
      L.control.zoom({
        position: 'bottomright',
      }).addTo(leafletMap);

      // Create marker cluster group with custom options
      markerClusterGroup = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 40,
        iconCreateFunction(cluster) {
          const count = cluster.getChildCount();
          return L.divIcon({
            html: `<div class="marker-cluster-icon">+${count}</div>`,
            className: 'marker-cluster',
            iconSize: L.point(36, 36),
          });
        },
      });

      // Prevent a rapid second cluster click from landing on a newly revealed tenant marker.
      markerClusterGroup.on('clusterclick', () => {
        suppressMarkerClicksUntil = Date.now() + 400;
      });

      // Add marker cluster group to map
      leafletMap.addLayer(markerClusterGroup);

      // Add markers for each faculty in Vilnius only
      updateMapMarkers();

      isMapInitialized.value = true;
    }
  }
  catch (error) {
    console.error('Failed to load map libraries:', error);
    isMapInitialized.value = false;
  }
  finally {
    isInitializingMap = false;
  }
};

// Force update map when the component becomes visible
const forceUpdateMap = () => {
  if (leafletMap) {
    setTimeout(() => {
      leafletMap.invalidateSize();
    }, 10);
  }
};

// Update map markers based on filtered Vilnius options
const updateMapMarkers = () => {
  if (!L || !leafletMap || !markerClusterGroup) return;

  // Clear existing markers from cluster group
  markerClusterGroup.clearLayers();
  markers = [];

  // Only show Vilnius faculties on the map
  // vilniusFaculties.value.forEach(option => {
  filteredFaculties.value.forEach((option) => {
    const location = props.facultyLocations[option.key];
    if (!location) return;

    // Create marker based on whether we have an avatar image
    let marker: Layer;
    const isActive = isActivePadalinys(option.key);
    const avatarUrl = option.primary_institution?.image_url;

    if (avatarUrl) {
      const customIcon = L.divIcon({
        className: 'custom-map-marker',
        html: renderAvatarToHTML(option, isActive),
        iconSize: [32, 32],
        iconAnchor: [16, 32],
      });

      // Replacing a focused marker after cluster zoom can close the hover-driven popover.
      marker = L.marker([location.lat, location.lng], { icon: customIcon, keyboard: false });
    }
    else {
      // Fallback to minimal circle marker
      const markerColor = isActive ? '#ef4444' : (isDark.value ? '#94a3b8' : '#64748b');
      marker = L.circleMarker([location.lat, location.lng], {
        radius: 8,
        fillColor: markerColor,
        color: isDark.value ? '#1e293b' : '#fff',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.8,
      });
    }

    // Add hover and click events
    marker.on('mouseover', () => setHoveredLocation(option));
    marker.on('mouseout', () => setHoveredLocation(null));
    marker.on('click', () => {
      if (Date.now() < suppressMarkerClicksUntil) return;
      props.onFacultySelect(option.key);
    });

    // Add tooltip with faculty name and short name
    const shortName = $t(option.primary_institution?.short_name ?? '');

    if (option.isMainOffice) {
      option.label = `${$t('Centrinis biuras')}`;
    }

    marker.bindTooltip(`${option.label} (${shortName})`);

    // Add marker to cluster group
    markerClusterGroup.addLayer(marker);
    markers.push(marker);
  });

  // If we have markers, fit the map to show all of them
  if (markers.length > 0) {
    const group = L.featureGroup(markers);
    leafletMap.fitBounds(group.getBounds(), {
      padding: [30, 30],
      maxZoom: 14, // Prevent excessive zoom on small areas
    });
  }
  else {
    // If no markers (e.g., all filtered out), reset to Vilnius center
    leafletMap.setView([54.683333, 25.286944], 13);
  }
};

// Provide methods for parent component to call
defineExpose({
  forceUpdateMap,
  initializeOrUpdateMap,
});

// Clean up map on component unmount
onBeforeUnmount(() => {
  if (leafletMap) {
    removeMapSafely(leafletMap);
    leafletMap = null;
    markers = [];
    markerClusterGroup = null;
    tileLayer = null;
    isMapInitialized.value = false;
    mapCreationAttempted.value = false;
  }
});
</script>

<style scoped>
:deep(.map-avatar) {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  background-color: white;
  transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
}

:deep(.map-avatar:hover) {
  transform: scale(1.1);
  z-index: 1000;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);
}

:deep(.map-avatar.active) {
  border-color: #ef4444;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.3);
}

:deep(.map-avatar.main-office) {
  border-color: #10b981;
  box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
}

/* Customize marker cluster styling */
:deep(.marker-cluster) {
  background-color: transparent;
  background: none;
}

:deep(.marker-cluster-icon) {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background-color: #ef4444;
  color: white;
  font-weight: bold;
  border-radius: 50%;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  border: 1px solid white;
  font-size: 14px;
}

:deep(.leaflet-container) {
  background: var(--muted);
  font: inherit;
}

:deep(.leaflet-control-zoom) {
  overflow: hidden;
  border: 1px solid var(--border) !important;
  border-radius: 0;
  margin-right: 10px;
  margin-bottom: 10px;
  box-shadow: 0 1px 3px rgb(0 0 0 / 12%);
}

:deep(.leaflet-control-zoom a) {
  /* Leaflet's own "+"/"−" glyph (set via innerHTML), just re-centred: `display: flex` beats
     the native `line-height`-based centring, which throws the glyph off-centre once the
     button is resized away from Leaflet's default 26px. `!important` on `display` guards
     against Leaflet's own CSS, which is imported dynamically at runtime and lands after this
     scoped style in source order — same-specificity rules resolve by source order, so without
     it Leaflet's `.leaflet-bar a { display: block }` would silently win. */
  display: flex !important;
  align-items: center;
  justify-content: center;
  /* The glyph's own descender-side leading outweighs its ascender-side leading, which reads as
     "sits a couple pixels low" once it's flex-centred on box height instead of Leaflet's default
     line-height centring. A bottom padding nudges the centred content up to compensate. */
  padding-bottom: 2px;
  width: 2rem;
  height: 2rem;
  border: 0 !important;
  border-bottom: 1px solid var(--border) !important;
  border-radius: 0 !important;
  background: var(--popover);
  color: var(--foreground);
  font-size: 1.125rem;
  line-height: 1;
  text-indent: 0;
  transition: color 150ms ease, background-color 150ms ease;
}

:deep(.leaflet-control-zoom a:last-child) {
  border-bottom: 0 !important;
}

:deep(.leaflet-control-zoom a:hover),
:deep(.leaflet-control-zoom a:focus-visible) {
  background: var(--popover);
  color: var(--brand);
}

:deep(.leaflet-control-zoom a:focus-visible) {
  outline: 2px solid var(--ring);
  outline-offset: -2px;
}

:deep(.leaflet-control-zoom a.leaflet-disabled) {
  background: var(--muted);
  color: var(--muted-foreground);
}

:deep(.leaflet-control-attribution) {
  background: color-mix(in oklab, var(--popover) 78%, transparent);
  padding: 0 5px;
  font-size: 9px;
  color: var(--muted-foreground);
}

:deep(.leaflet-tooltip) {
  font-size: 12px;
  padding: 4px 8px;
  border-radius: 4px;
  border: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  word-wrap: break-word; /* Ensure long words wrap properly */
  overflow-wrap: break-word; /* Modern version of word-wrap */
}

.dark :deep(.marker-cluster-icon) {
  background-color: #ef4444;
  color: white;
  border: 1px solid #1e293b;
}

.dark :deep(.map-avatar) {
  border-color: #1e293b;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.dark :deep(.leaflet-tooltip) {
  background-color: #1e293b;
  color: #f8fafc;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}
</style>
