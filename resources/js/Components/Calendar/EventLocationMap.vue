<template>
  <div
    ref="mapContainer"
    class="h-48 w-full overflow-hidden border border-border bg-secondary"
  />
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, useTemplateRef, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useDark } from '@vueuse/core';
import type * as Leaflet from 'leaflet';

const props = defineProps<{
  latitude: number;
  longitude: number;
  /** Shown in the marker tooltip. */
  label: string;
}>();

const mapContainer = useTemplateRef<HTMLElement>('mapContainer');
const isDark = useDark();
const cartoApiKey = usePage().props.map?.cartoApiKey;

// Held outside of Vue's reactivity: Leaflet instances must not be proxied.
let L: typeof Leaflet | null = null;
let map: Leaflet.Map | null = null;
let tileLayer: Leaflet.TileLayer | null = null;

// CARTO now requires an API key on tile requests, passed as `key` (not `api_key`) per their docs.
const tileUrlSuffix = cartoApiKey ? `?key=${encodeURIComponent(cartoApiKey)}` : '';
const tileUrls = {
  light: `https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png${tileUrlSuffix}`,
  dark: `https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png${tileUrlSuffix}`,
};

const createTileLayer = () => {
  if (!L) return null;
  return L.tileLayer(isDark.value ? tileUrls.dark : tileUrls.light, {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 19,
  });
};

onMounted(async () => {
  if (typeof window === 'undefined' || !mapContainer.value) return;

  try {
    L = (await import('leaflet')).default;
    await import('leaflet/dist/leaflet.css');

    map = L.map(mapContainer.value, {
      zoomControl: false,
      attributionControl: false,
      // The map sits inside an article; grabbing the wheel would trap the reader.
      scrollWheelZoom: false,
      // This is a static, decorative preview — no keyboard panning needed, and it avoids
      // Leaflet focusing the container on click (see PadalinysMap for why that matters there).
      keyboard: false,
    }).setView([props.latitude, props.longitude], 15);

    tileLayer = createTileLayer()?.addTo(map) ?? null;

    L.control.attribution({ position: 'bottomright', prefix: '' })
      .addAttribution('© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>')
      .addTo(map);

    L.control.zoom({ position: 'bottomright' }).addTo(map);

    L.marker([props.latitude, props.longitude], {
      icon: L.divIcon({
        className: 'event-location-marker',
        html: '<span class="event-location-pin"></span>',
        iconSize: [18, 18],
        iconAnchor: [9, 9],
      }),
    })
      .addTo(map)
      .bindTooltip(props.label, { direction: 'top', offset: [0, -12] });
  }
  catch (error) {
    console.error('Failed to load the location map:', error);
  }
});

watch(isDark, () => {
  if (!map || !tileLayer) return;

  tileLayer.remove();
  tileLayer = createTileLayer()?.addTo(map) ?? null;
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
  tileLayer = null;
  L = null;
});
</script>

<style scoped>
:deep(.event-location-pin) {
  display: block;
  width: 18px;
  height: 18px;
  border-radius: 9999px;
  background-color: var(--brand);
  border: 3px solid white;
  box-shadow: 0 1px 6px rgb(0 0 0 / 35%);
}

:deep(.leaflet-container) {
  background: transparent;
  font: inherit;
}

:deep(.leaflet-control-zoom a) {
  border: none;
  background-color: rgb(255 255 255 / 90%);
  color: #3f3f46;
}

:deep(.leaflet-control-attribution) {
  background: rgb(255 255 255 / 70%);
  font-size: 0.625rem;
}

.dark :deep(.leaflet-control-zoom a) {
  background-color: rgb(39 39 42 / 90%);
  color: #e4e4e7;
}

.dark :deep(.leaflet-control-attribution) {
  background: rgb(39 39 42 / 70%);
  color: #a1a1aa;
}

.dark :deep(.leaflet-control-attribution a) {
  color: #d4d4d8;
}

:deep(.leaflet-tooltip) {
  border: none;
  border-radius: 0;
  padding: 0.375rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 500;
  box-shadow: 0 2px 10px rgb(0 0 0 / 15%);
}

.dark :deep(.leaflet-tooltip) {
  background-color: #27272a;
  color: #f4f4f5;
}

.dark :deep(.leaflet-tooltip-top::before) {
  border-top-color: #27272a;
}
</style>
