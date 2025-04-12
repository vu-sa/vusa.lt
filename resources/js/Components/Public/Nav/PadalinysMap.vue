<template>
  <div class="padalinys-map">
    <div class="relative h-[350px] w-full bg-muted overflow-hidden">
      <!-- Leaflet Map Container -->
      <div id="padalinys-leaflet-map" class="h-full w-full"></div>

      <!-- <div class="absolute bottom-2 left-2 z-[1000] p-2 bg-zinc-50/80 dark:bg-zinc-900/80 backdrop-blur-sm rounded-md shadow text-sm max-w-[200px]">
        <p v-if="hoveredLocation" class="font-medium">
          {{ hoveredLocation.label }}
          <span class="block text-xs text-zinc-900 dark:text-zinc-50">{{ hoveredLocation.primary_institution?.short_name || '' }}</span>
        </p>
        <p v-else class="text-xs text-zinc-900 dark:text-zinc-50">
          {{ $t('Hover over a faculty to see details') }}
        </p>
      </div> -->
    </div>
  </div>
</template>

<script setup lang="tsx">
import { trans as $t, trans } from "laravel-vue-i18n";
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick, h, render } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { useDark } from "@vueuse/core";

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
const emit = defineEmits(['update:hoveredLocation']);

// Importing leaflet dynamically to avoid SSR issues
let L: any = null;
let leafletMap: any = null;
let markers: any[] = [];
let markerClusterGroup: any = null;

const hoveredLocation = ref<DropdownOption | null>(null);
const isMapInitialized = ref(false);
const mapCreationAttempted = ref(false);

// Get theme state for dark mode detection
const isDark = useDark();

// Map tile URLs for light and dark modes
const mapTileUrls = {
  light: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
  dark: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
};

// Map tile layer - will be initialized once we know the theme
let tileLayer: any = null;

const isActivePadalinys = (key: string): boolean => {
  return usePage().props.tenant?.alias === key;
};

const vilniusFaculties = computed(() => {
  if (!props.searchQuery) {
    return props.faculties.filter(
      option => props.facultyLocations[option.key]?.city === 'vilnius'
    );
  }

  return props.faculties.filter(option => 
    props.facultyLocations[option.key]?.city === 'vilnius' && (
      option.label.toLowerCase().includes(props.searchQuery.toLowerCase()) ||
      option.key.toLowerCase().includes(props.searchQuery.toLowerCase())
    )
  );
});

const filteredFaculties = computed(() => {
  if (!props.searchQuery) {
    return props.faculties;
  }

  return props.faculties.filter(option => 
    option.label.toLowerCase().includes(props.searchQuery.toLowerCase()) ||
    option.key.toLowerCase().includes(props.searchQuery.toLowerCase())
  );
});

// Set hovered location and emit to parent
const setHoveredLocation = (option: DropdownOption | null) => {
  hoveredLocation.value = option;
  emit('update:hoveredLocation', option);
};

// Render Avatar component as HTML string for Leaflet marker
const renderAvatarToHTML = (option: DropdownOption, isActive: boolean): string => {
  // Get avatar URL from primary institution
  const avatarUrl = option.primary_institution?.image_url;
  const key = option.key;
  const isMainOffice = option.isMainOffice;
  
  // Create a div to render our component
  const container = document.createElement('div');
  
  // Render the Avatar component
  const vnode = h(Avatar, 
    { 
      class: `map-avatar ${isActive ? 'active' : ''} ${isMainOffice ? 'main-office' : ''}` 
    }, 
    {
      default: () => [
        avatarUrl 
          ? h(AvatarImage, { src: avatarUrl, alt: option.label })
          : null,
        h(AvatarFallback, {}, () => key.substring(0, 2).toUpperCase())
      ]
    }
  );
  
  // Convert Vue component to HTML string
    render(vnode, container);
  
  // Return the HTML content
  return container.innerHTML;
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
watch(() => isDark, (newIsDark) => {
  if (leafletMap && tileLayer) {
    // Remove existing tile layer
    tileLayer.remove();
    
    // Add new tile layer based on theme
    tileLayer = L.tileLayer(newIsDark ? mapTileUrls.dark : mapTileUrls.light, {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: 'abcd',
      maxZoom: 19,
    }).addTo(leafletMap);
    
    // Force update styling for attribution in dark mode
    updateMapControlsStyle(newIsDark.value);
  }
});

// Initialize or recreate the map
const initializeOrUpdateMap = async () => {
  if (typeof window === 'undefined') return;
  
  try {
    // Clean up any existing map
    if (leafletMap) {
      leafletMap.remove();
      leafletMap = null;
      markers = [];
      markerClusterGroup = null;
      tileLayer = null;
    }
    
    mapCreationAttempted.value = true;
    
    // Dynamically import Leaflet and Leaflet.MarkerCluster
    const leaflet = await import('leaflet');
    L = leaflet.default;
    
    // Import Leaflet and MarkerCluster CSS
    await import('leaflet/dist/leaflet.css');
    
    // Import MarkerCluster
    const MarkerCluster = await import('leaflet.markercluster');
    await import('leaflet.markercluster/dist/MarkerCluster.css');
    await import('leaflet.markercluster/dist/MarkerCluster.Default.css');
    
    // Initialize map if container exists
    const container = document.getElementById('padalinys-leaflet-map');
    if (container) {
      // Center on Vilnius
      leafletMap = L.map('padalinys-leaflet-map', {
        zoomControl: false, // Disable default zoom control for cleaner look
        attributionControl: false // We'll add attribution in a more subtle way
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
        prefix: ''
      }).addAttribution('© <a href="https://www.openstreetmap.org/copyright" class="text-xs opacity-70">OpenStreetMap</a>').addTo(leafletMap);
      
      // Add custom zoom control in a more subtle position
      L.control.zoom({
        position: 'bottomright'
      }).addTo(leafletMap);
      
      // Apply custom styling for attribution and zoom controls
      updateMapControlsStyle(isDark.value);
      
      // Create marker cluster group with custom options
      markerClusterGroup = L.markerClusterGroup({
        showCoverageOnHover: false,
        maxClusterRadius: 40,
        iconCreateFunction: function(cluster) {
          const count = cluster.getChildCount();
          return L.divIcon({
            html: `<div class="marker-cluster-icon">+${count}</div>`,
            className: 'marker-cluster',
            iconSize: L.point(36, 36)
          });
        }
      });
      
      // Add marker cluster group to map
      leafletMap.addLayer(markerClusterGroup);
      
      // Add markers for each faculty in Vilnius only
      updateMapMarkers();
      
      isMapInitialized.value = true;
    }
  } catch (error) {
    console.error('Failed to load map libraries:', error);
    isMapInitialized.value = false;
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
  filteredFaculties.value.forEach(option => {
    const location = props.facultyLocations[option.key];
    if (!location) return;
    
    // Create marker based on whether we have an avatar image
    let marker;
    const isActive = isActivePadalinys(option.key);
    const avatarUrl = option.primary_institution?.image_url;
    
    if (avatarUrl) {
      const customIcon = L.divIcon({
        className: 'custom-map-marker',
        html: renderAvatarToHTML(option, isActive),
        iconSize: [32, 32],
        iconAnchor: [16, 32]
      });
      
      marker = L.marker([location.lat, location.lng], { icon: customIcon });
    } else {
      // Fallback to minimal circle marker
      const markerColor = isActive ? '#ef4444' : (isDark.value ? '#94a3b8' : '#64748b');
      marker = L.circleMarker([location.lat, location.lng], {
        radius: 8,
        fillColor: markerColor,
        color: isDark.value ? '#1e293b' : '#fff',
        weight: 2,
        opacity: 1,
        fillOpacity: 0.8
      });
    }
    
    // Add hover and click events
    marker.on('mouseover', () => setHoveredLocation(option));
    marker.on('mouseout', () => setHoveredLocation(null));
    marker.on('click', () => props.onFacultySelect(option.key));
    
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
      maxZoom: 14 // Prevent excessive zoom on small areas
    });
  } else {
    // If no markers (e.g., all filtered out), reset to Vilnius center
    leafletMap.setView([54.683333, 25.286944], 13);
  }
};

// Apply custom styling to map controls based on theme
const updateMapControlsStyle = (isDark: boolean) => {
  if (!leafletMap) return;
  
  // Find all zoom control buttons and apply spacing
  const zoomInButton = document.querySelector('.leaflet-control-zoom-in');
  const zoomOutButton = document.querySelector('.leaflet-control-zoom-out');
  
  if (zoomInButton && zoomOutButton) {
    // Add margin between zoom buttons
    (zoomInButton as HTMLElement).style.marginBottom = '4px';
    (zoomInButton as HTMLElement).style.borderRadius = '4px';
    (zoomOutButton as HTMLElement).style.borderRadius = '4px';
  }
  
  // Fix attribution background color in dark mode
  const attributionContainer = document.querySelector('.leaflet-control-attribution');
  if (attributionContainer) {
    if (isDark) {
      (attributionContainer as HTMLElement).style.backgroundColor = 'rgba(30, 41, 59, 0.7)';
      (attributionContainer as HTMLElement).style.color = '#94a3b8';
    } else {
      (attributionContainer as HTMLElement).style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
      (attributionContainer as HTMLElement).style.color = '#64748b';
    }
  }
};

// Provide methods for parent component to call
defineExpose({
  forceUpdateMap,
  initializeOrUpdateMap
});

// Clean up map on component unmount
onBeforeUnmount(() => {
  if (leafletMap) {
    leafletMap.remove();
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

/* Customize Leaflet controls for minimal design */
:deep(.leaflet-control-zoom) {
  border: none !important;
  margin-right: 10px;
  margin-bottom: 10px;
}

:deep(.leaflet-control-zoom a) {
  border-radius: 4px !important;
  color: #64748b;
  border: 1px solid #e2e8f0 !important;
  background-color: white;
  display: block; /* Ensure buttons stack properly */
}

:deep(.leaflet-control-zoom a:hover) {
  background-color: #f8fafc;
  color: #334155;
}

:deep(.leaflet-control-attribution) {
  background-color: rgba(255, 255, 255, 0.7);
  padding: 0 5px;
  font-size: 9px;
  color: #64748b;
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

/* Dark theme support - use !important for higher specificity */
.dark :deep(.leaflet-control-zoom a) {
  background-color: #1e293b !important;
  border-color: #334155 !important;
  color: #cbd5e1;
}

.dark :deep(.leaflet-control-zoom a:hover) {
  background-color: #334155 !important;
  color: #f1f5f9;
}

.dark :deep(.leaflet-control-attribution) {
  background-color: rgba(30, 41, 59, 0.7) !important;
  color: #94a3b8 !important;
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