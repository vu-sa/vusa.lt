<template>
  <Popover @update:open="handlePopoverOpenChange">
    <PopoverTrigger as-child>
      <Button 
        variant="outline" 
        :size="size === 'tiny' ? 'sm' : 'default'"
        class="flex items-center gap-2 w-auto justify-between tracking-normal"
        :disabled="isDisabled"
        :title="$t('Pasirinkti padalinį')"
      >
        <div class="flex items-center">
          <MapPin class="mr-2 h-4 w-4" />
          <span class="tracking-normal">{{ padalinys }}</span>
        </div>
        <ChevronDown class="h-4 w-4 opacity-50 transition-transform duration-200" :class="{ 'rotate-180': isPopoverOpen }" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="p-0" :class="{ 'w-[300px]': viewMode === 'list', 'w-[520px]': viewMode === 'map' }" align="start">
      <div class="flex items-center gap-2 p-2 border-b border-zinc-200 dark:border-zinc-800"> 
        <Button 
          variant="ghost" 
          size="sm" 
          :class="{ 'bg-muted': viewMode === 'list' }" 
          @click="setViewMode('list')"
        >
          <List class="h-4 w-4" />
          <span class="ml-2">{{ $t('List') }}</span>
        </Button>
        <Button 
          variant="ghost" 
          size="sm" 
          :class="{ 'bg-muted': viewMode === 'map' }" 
          @click="setViewMode('map')"
        >
          <Map class="h-4 w-4" />
          <span class="ml-2">{{ $t('Map') }}</span>
        </Button>
        
        <div class="ml-auto flex">
          <Input 
            v-if="viewMode === 'map'"
            class="h-8 w-32"
            :placeholder="`${$t('Ieškoti')}...`"
            v-model="searchQuery"
          />
        </div>
      </div>
      
      <!-- List View -->
      <div v-if="viewMode === 'list'" class="padalinys-list">
        <ScrollArea class="h-[350px]">
            <div class="space-y-1 p-1 overflow-hidden">
              <template v-for="option in options_padaliniai" :key="option.key">
                <Button
                  variant="ghost"
                  :class="[
                    'flex w-full cursor-pointer items-center justify-start gap-2 rounded-md px-2 py-1.5 text-sm', 
                    isActivePadalinys(option.key) && 'bg-accent text-accent-foreground',
                    option.isMainOffice && 'font-bold'
                  ]"
                  @click="handleSelectPadalinys(option.key)"
                >
                  <Avatar class="h-6 w-6">
                    <AvatarImage v-if="option.primary_institution?.image_url" :src="option.primary_institution.image_url" />
                    <AvatarFallback>{{ option.key.substring(0, 2).toUpperCase() }}</AvatarFallback>
                  </Avatar>
                  
                  <div class="flex flex-col items-start truncate w-full">
                    <span class="font-medium">{{ option.label }}</span>
                    <span class="text-xs text-muted-foreground">{{ $t(option.primary_institution?.short_name ?? '') }}</span>
                  </div>
                  
                  <Check 
                    class="ml-auto h-4 w-4 opacity-0 transition-opacity"
                    :class="{ 'opacity-100': isActivePadalinys(option.key) }"
                  />
                </Button>
              </template>
            </div>
        </ScrollArea>
      </div>
      
      <!-- Map View with LoadWhenVisible for better performance -->
      <div v-else-if="viewMode === 'map'" class="padalinys-map relative">
        <Suspense>
          <PadalinysMap 
            ref="mapComponentRef"
            :faculties="options_padaliniai" 
            :search-query="searchQuery"
            :on-faculty-select="handleSelectPadalinys"
            :faculty-locations="facultyLocations"
            class="max-h-[350px] overflow-hidden"
            @update:hovered-location="hoveredLocation = $event"
          />
          <template #fallback>
            <div class="h-[350px] w-full bg-muted rounded-md flex items-center justify-center">
              <div class="flex flex-col items-center gap-4">
                <Skeleton class="h-10 w-10 rounded-full" />
                <div class="space-y-2">
                  <Skeleton class="h-3 w-28" />
                  <Skeleton class="h-2 w-20" />
                </div>
                <div class="text-sm text-muted-foreground mt-2">{{ $t('Loading map...') }}</div>
              </div>
            </div>
          </template>
        </Suspense>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref, watch, onMounted, nextTick } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Check, ChevronDown, MapPin, Map, List, Loader2 } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Popover, PopoverTrigger, PopoverContent } from "@/Components/ui/popover";
import { Avatar, AvatarFallback, AvatarImage } from "@/Components/ui/avatar";
import { useStorage } from '@vueuse/core';
import { Skeleton } from '@/Components/ui/skeleton';

// Import PadalinysMap component
import PadalinysMap from "./PadalinysMap.vue";

interface DropdownOption {
  label: string;
  key: string;
  primary_institution?: {
    short_name?: string;
    image_url?: string;
  };
  isMainOffice?: boolean;
}

interface Props {
  /** Button size variant - affects padding and height */
  size: "tiny" | "small" | "medium";
  /** Additional options to prepend to the dropdown list */
  prependOptions?: Array<DropdownOption>;
  /** Override default label when on main tenant (instead of "Padaliniai") */
  mainTenantLabel?: string;
}

interface FacultyLocation {
  lat: number;
  lng: number;
  city: 'vilnius' | 'kaunas' | 'siauliai';
}

const props = defineProps<Props>();
const mapComponentRef = ref<InstanceType<typeof PadalinysMap> | null>(null);

// Faculty locations with real geographical coordinates for Vilnius, Kaunas, and Šiauliai
const facultyLocations: Record<string, FacultyLocation> = {
  // Central
  'vusa': { lat: 54.682265, lng: 25.2868833, city: 'vilnius' },
  // Main faculties with approximate real coordinates
  'chgf': { lat: 54.675974, lng: 25.2740121, city: 'vilnius' },   // Faculty of Chemistry and Geosciences
  'evaf': { lat: 54.72238, lng: 25.332105, city: 'vilnius' },     // Faculty of Economics
  'filf': { lat: 54.683494, lng: 25.2885235, city: 'vilnius' },    // Faculty of Philology
  'ff': { lat: 54.72238, lng: 25.331061, city: 'vilnius' },     // Faculty of Physics
  'fsf': { lat: 54.683777, lng: 25.2870587, city: 'vilnius' },    // Faculty of Philosophy
  'gmc': { lat: 54.722313, lng: 25.326331, city: 'vilnius' },    // Life Sciences Center
  'if': { lat: 54.6831617, lng: 25.2870202, city: 'vilnius' },     // Faculty of History
  'kf': { lat: 54.721975, lng: 25.333105, city: 'vilnius' },    // Faculty of Communication
  'knf': { lat: 54.89535, lng: 23.88947, city: 'kaunas' },     // Kaunas Faculty
  'mif': { lat: 54.67509, lng: 25.273487, city: 'vilnius' },    // Faculty of Mathematics and Informatics
  'mf': { lat: 54.682572, lng: 25.25881457, city: 'vilnius' },    // Faculty of Medicine
  'sa': { lat: 55.92864804, lng: 23.3145673, city: 'siauliai' },     // Šiauliai Academy
  'tf': { lat: 54.72238, lng: 25.33312, city: 'vilnius' },     // Faculty of Law
  'tspmi': { lat: 54.678565, lng: 25.284323, city: 'vilnius' },  // Institute of International Relations
  'vm': { lat: 54.72498, lng: 25.33620, city: 'vilnius' },     // Business School
};

// City names for translation
const cityNames = {
  'vilnius': 'Vilnius',
  'kaunas': 'Kaunas',
  'siauliai': 'Šiauliai'
};

const viewMode = useStorage('padalinysSelectorViewMode', 'list'); // 'list' or 'map'
const searchQuery = ref('');
const hoveredLocation = ref<DropdownOption | null>(null);
const isPopoverOpen = ref(false);

const handleSelectPadalinys = (key: string | string[]) => {
  let padalinys_alias: string = Array.isArray(key) ? key[0] ?? '' : key;

  // Remove the first subdomain (current tenant) and keep the rest of the hostname
  // This handles both production (ff.vusa.lt) and staging (ff.naujas.vusa.lt) environments
  const hostWithoutSubdomain = window.location.host
    .split(".")
    .slice(1)
    .join(".");

  // If padalinys_alias is 'vusa', set to 'www'
  if (padalinys_alias === 'vusa') {
    padalinys_alias = 'www';
  }

  window.location.href = `${window.location.protocol}//${padalinys_alias}.${hostWithoutSubdomain}${usePage().url}`;
};

const isActivePadalinys = (key: string): boolean => {
  return usePage().props.tenant?.alias === key;
};

// Format faculty alias to standardized format (VU SR PREFIX + UPPERCASE)
// This is kept for backward compatibility where primary_institution.short_name is not available
const getFormattedAlias = (key: string): string => {
  if (!key) return '';
  
  const isEnglish = usePage().props.app.locale === 'en';
  const prefix = isEnglish ? 'VU SR ' : 'VU SA ';
  
  // Special case for main organization
  if (key === 'vusa') {
    return isEnglish ? 'VU SR' : 'VU SA';
  }
  
  return prefix + key.toUpperCase();
};

const options_padaliniai = computed(() => {
  const options = usePage()
    .props.tenants.filter(
      (tenant) => (tenant.type === "padalinys" || tenant.type === "pagrindinis") && tenant.id <= 17
    )
    .map((tenant): DropdownOption => ({
      label: $t(tenant.fullname.split("atstovybė ")[1] || ''),
      key: tenant.alias,
      primary_institution: tenant.primary_institution ? {
        short_name: Array.isArray(tenant.primary_institution.short_name) 
          ? tenant.primary_institution.short_name[0] 
          : tenant.primary_institution.short_name || undefined,
        image_url: tenant.primary_institution.image_url || undefined
      } : undefined,
      isMainOffice: tenant.type === "pagrindinis"
    }));

  if (props.prependOptions) {
    return [...props.prependOptions, ...options];
  }

  return options;
});

// Separate faculties by city for different handling
const vilniusFaculties = computed(() => {
  if (!searchQuery.value) {
    return options_padaliniai.value.filter(
      option => facultyLocations[option.key]?.city === 'vilnius'
    );
  }
  
  return options_padaliniai.value.filter(option => 
    facultyLocations[option.key]?.city === 'vilnius' && (
      option.label.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      option.key.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  );
});

const otherCitiesFaculties = computed(() => {
  if (!searchQuery.value) {
    return options_padaliniai.value.filter(
      option => {
        const city = facultyLocations[option.key]?.city;
        return city && ['kaunas', 'siauliai'].includes(city);
      }
    );
  }
  
  return options_padaliniai.value.filter(option => {
    const city = facultyLocations[option.key]?.city;
    return city && ['kaunas', 'siauliai'].includes(city) && (
      option.label.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      option.key.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
  });
});

const padalinys = computed(() => {
  if (usePage().props.tenant?.alias === "vusa") {
    return $t(props.mainTenantLabel ?? "Padaliniai");
  }
  return $t(usePage().props.tenant?.shortname.split(" ").pop() ?? "Padaliniai");
});

const isDisabled = computed(() => {
  if (["lt", "en", "lt/naujienos"].includes(usePage().props.app.path)) {
    return false;
  }

  // check if contains kontaktai or contacts
  if (usePage().props.app.path.includes("kontaktai")) {
    return false;
  }

  if (usePage().props.app.path.includes("contacts")) {
    return false;
  }

  return true;
});

// Helper to get translated city name
const getCityName = (option: DropdownOption): string => {
  const city = facultyLocations[option.key]?.city;
  if (!city) return '';
  return $t(cityNames[city] || '');
};

// Set view mode and initialize map if needed
const setViewMode = (mode: 'list' | 'map') => {
  viewMode.value = mode;
  searchQuery.value = '';
  
  if (mode === 'map') {
    // Give DOM time to update before initializing map
    nextTick(() => {
      if (mapComponentRef.value) {
        setTimeout(() => {
          mapComponentRef.value?.forceUpdateMap();
        }, 50);
      }
    });
  }
};

// Handle popover open/close to initialize map when opened
const handlePopoverOpenChange = (open: boolean) => {
  isPopoverOpen.value = open;
  
  if (open) {
    if (viewMode.value === 'map') {
      // When popover opens, initialize or update map after DOM has updated
      nextTick(() => {
        setTimeout(() => {
          if (mapComponentRef.value) {
            mapComponentRef.value.initializeOrUpdateMap();
          }
        }, 100);
      });
    }
  } else {
    // Reset the search when popover is closed
    searchQuery.value = '';
  }
};
</script>
