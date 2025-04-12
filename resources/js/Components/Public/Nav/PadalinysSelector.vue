<template>
  <Popover @update:open="handlePopoverOpenChange">
    <PopoverTrigger as-child>
      <Button 
        variant="outline" 
        :class="[
          'flex items-center gap-2 w-auto justify-between', 
          size === 'tiny' ? 'h-8 px-2 text-xs' : size === 'small' ? 'h-9 px-3 py-2' : 'h-10 px-4 py-2',
        ]" 
        :disabled="isDisabled"
      >
        <div class="flex items-center">
          <MapPin class="mr-2 h-4 w-4" />
          <span>{{ padalinys }}</span>
        </div>
        <ChevronDown class="h-4 w-4 opacity-50" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="p-0" :class="{ 'w-[300px]': viewMode === 'list', 'w-[520px]': viewMode === 'map' }" align="start">
      <div class="flex items-center gap-2 p-2 border-b border-zinc-200 dark:border-zinc-800"> 
        <Button variant="ghost" size="sm" :class="{ 'bg-muted': viewMode === 'list' }" @click="setViewMode('list')">
          <List class="h-4 w-4" />
          <span class="ml-2">{{ $t('List') }}</span>
        </Button>
        <Button variant="ghost" size="sm" :class="{ 'bg-muted': viewMode === 'map' }" @click="setViewMode('map')">
          <Map class="h-4 w-4" />
          <span class="ml-2">{{ $t('Map') }}</span>
        </Button>
        
        <div class="ml-auto flex">
          <Input 
            v-if="viewMode === 'map'"
            class="h-8 w-32"
            :placeholder="$t('Ieškoti...')"
            v-model="searchQuery"
          />
        </div>
      </div>
      
      <!-- List View -->
      <div v-if="viewMode === 'list'" class="padalinys-list">
        <div class="p-0">
          <div class="space-y-1 p-1">
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
        </div>
      </div>
      
      <!-- Map View -->
      <div v-else-if="viewMode === 'map'" class="padalinys-map">
          <Suspense>
            <PadalinysMap 
              ref="mapComponentRef"
              :faculties="options_padaliniai" 
              :search-query="searchQuery"
              :on-faculty-select="handleSelectPadalinys"
              :faculty-locations="facultyLocations"
              @update:hovered-location="hoveredLocation = $event"
            />
            <template #fallback>
              <div class="h-[350px] w-full bg-muted rounded-md flex items-center justify-center">
                <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
              </div>
            </template>
          </Suspense>

        <!-- Special Locations Section (Kaunas & Šiauliai). Disabled for now, as marker collections are used -->
        <div class="border-t pt-2 pb-2 px-2 border-zinc-200 dark:border-zinc-800 md:hidden">
          <p class="text-xs font-medium text-muted-foreground mb-2">{{ $t('Other Cities') }}</p>
          <div class="flex flex-wrap gap-2">
            <Button
              v-for="option in otherCitiesFaculties" 
              :key="option.key"
              size="sm"
              variant="outline"
              :class="[
                'flex items-center gap-1.5', 
                isActivePadalinys(option.key) && 'bg-muted'
              ]"
              @click="handleSelectPadalinys(option.key)"
            >
              <Avatar class="h-5 w-5">
                <AvatarImage v-if="option.primary_institution?.image_url" :src="option.primary_institution.image_url" />
                <AvatarFallback>{{ option.key.substring(0, 2).toUpperCase() }}</AvatarFallback>
              </Avatar>
              <div class="flex flex-col items-start truncate">
                <span class="text-xs font-medium">{{ option.label }}</span>
                <span class="text-[10px] text-muted-foreground">{{ getCityName(option) }} • {{ option.primary_institution?.short_name || getFormattedAlias(option.key) }}</span>
              </div>
            </Button>
          </div>
        </div>

        <!-- Mobile Friendly List for Vilnius Faculties -->
        <div v-if="vilniusFaculties.length > 0" class="mt-3 md:hidden max-h-24 overflow-y-auto">
          <ScrollArea>
            <div class="flex p-1 gap-2">
              <Button
                v-for="option in vilniusFaculties" 
                :key="option.key"
                size="sm"
                variant="outline"
                :class="{ 'bg-muted': isActivePadalinys(option.key) }"
                @click="handleSelectPadalinys(option.key)"
              >
                <Avatar class="h-4 w-4 mr-2">
                  <AvatarImage v-if="option.primary_institution?.image_url" :src="option.primary_institution.image_url" />
                  <AvatarFallback class="text-[10px]">{{ option.key.substring(0, 2).toUpperCase() }}</AvatarFallback>
                </Avatar>
                {{ option.label }}
              </Button>
            </div>
          </ScrollArea>
        </div>
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
  size: "tiny" | "small" | "medium";
  prependOptions?: Array<DropdownOption>;
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
  let padalinys_alias = key;

  // if padalinys is array, get first element (for mobile)
  // because tree component returns array of selected keys
  if (Array.isArray(padalinys_alias)) {
    padalinys_alias = key[0];
  }

  // get last two elements of host and join them with dot
  const hostWithoutSubdomain = window.location.host
    .split(".")
    .slice(-2)
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

const options_padaliniai = computed<DropdownOption[]>(() => {
  const options = usePage()
    .props.tenants.filter(
      (tenant) => (tenant.type === "padalinys" || tenant.type === "pagrindinis") && tenant.id <= 17
    )
    .map((tenant) => ({
      label:
        props.size.toLowerCase() === "tiny"
          ? $t(tenant.shortname.split(" ")[2] || '')
          : $t(tenant.fullname.split("atstovybė ")[1] || ''),
      key: tenant.alias,
      primary_institution: tenant.primary_institution,
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
      option => ['kaunas', 'siauliai'].includes(facultyLocations[option.key]?.city)
    );
  }
  
  return options_padaliniai.value.filter(option => 
    ['kaunas', 'siauliai'].includes(facultyLocations[option.key]?.city) && (
      option.label.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      option.key.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  );
});

const padalinys = computed(() => {
  return $t(
    usePage().props.tenant?.alias !== "vusa"
      ? usePage().props.tenant?.shortname.split(" ").pop() ?? "Padaliniai"
      : "Padaliniai",
  );
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
