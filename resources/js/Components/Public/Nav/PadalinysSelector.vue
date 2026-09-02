<template>
  <Popover @update:open="handlePopoverOpenChange">
    <PopoverTrigger as-child>
      <Button
        variant="ghost"
        :size="size === 'tiny' ? 'sm' : 'default'"
        class="flex w-auto items-center justify-between gap-2 border border-border tracking-normal
          text-foreground/70 transition-colors duration-200
          hover:border-brand hover:bg-transparent hover:text-brand
          dark:hover:bg-transparent dark:hover:text-brand"
        :disabled="isDisabled"
        :title="$t('Pasirinkti padalinį')"
      >
        <div class="flex items-center">
          <IFluentLocation24Regular class="mr-2 h-4 w-4" />
          <span class="tracking-normal">{{ padalinys }}</span>
        </div>
        <IFluentChevronDown24Regular class="h-4 w-4 opacity-50 transition-transform duration-200" :class="{ 'rotate-180': isPopoverOpen }" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="p-0" :class="{ 'w-[300px]': viewMode === 'list', 'w-[520px]': viewMode === 'map' }" align="start">
      <div class="flex items-center gap-2 border-b border-border p-2">
        <Button
          variant="ghost"
          size="sm"
          :class="{ 'bg-muted': viewMode === 'list' }"
          @click="setViewMode('list')"
        >
          <IFluentList24Regular class="h-4 w-4" />
          <span class="ml-2">{{ $t('List') }}</span>
        </Button>
        <Button
          variant="ghost"
          size="sm"
          :class="{ 'bg-muted': viewMode === 'map' }"
          @click="setViewMode('map')"
        >
          <IFluentMap24Regular class="h-4 w-4" />
          <span class="ml-2">{{ $t('Map') }}</span>
        </Button>

        <div class="ml-auto flex">
          <Input
            v-if="viewMode === 'map'"
            v-model="searchQuery"
            class="h-8 w-32"
            :placeholder="`${$t('Ieškoti')}...`"
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
                  'flex w-full cursor-pointer items-center justify-start gap-2 px-2 py-1.5 text-sm',
                  // A brand left rule marks the current unit, echoing the ruled headline device.
                  isActivePadalinys(option.key) && 'border-l-2 border-brand bg-secondary',
                  option.isMainOffice && 'font-bold'
                ]"
                @click="handleSelectPadalinys(option.key)"
              >
                <Avatar class="h-6 w-6 rounded-none">
                  <AvatarImage v-if="option.primary_institution?.image_url" :src="option.primary_institution.image_url" />
                  <AvatarFallback>{{ option.key.substring(0, 2).toUpperCase() }}</AvatarFallback>
                </Avatar>

                <div class="flex flex-col items-start truncate w-full">
                  <span class="font-medium">{{ option.label }}</span>
                  <span class="text-xs text-muted-foreground">{{ $t(option.primary_institution?.short_name ?? '') }}</span>
                </div>

                <IFluentCheckmark24Regular
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
            :search-query
            :on-faculty-select="handleSelectPadalinys"
            :faculty-locations
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
                <div class="text-sm text-muted-foreground mt-2">
                  {{ $t('Loading map...') }}
                </div>
              </div>
            </div>
          </template>
        </Suspense>
      </div>
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, nextTick } from 'vue';
import { useStorage } from '@vueuse/core';

import PadalinysMap from './PadalinysMap.vue';

import IFluentCheckmark24Regular from '~icons/fluent/checkmark-24-regular';
import IFluentChevronDown24Regular from '~icons/fluent/chevron-down-24-regular';
import IFluentLocation24Regular from '~icons/fluent/location-24-regular';
import IFluentMap24Regular from '~icons/fluent/map-24-regular';
import IFluentList24Regular from '~icons/fluent/list-24-regular';
import type { TenantOption } from '@/Composables/useTenantOptions';
import { useTenantOptions } from '@/Composables/useTenantOptions';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Popover, PopoverTrigger, PopoverContent } from '@/Components/ui/popover';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Skeleton } from '@/Components/ui/skeleton';

interface Props {
  /** Button size variant - affects padding and height */
  size: 'tiny' | 'small' | 'medium';
  /** Additional options to prepend to the dropdown list */
  prependOptions?: Array<TenantOption>;
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
  vusa: { lat: 54.682265, lng: 25.2868833, city: 'vilnius' },
  // Main faculties with approximate real coordinates
  chgf: { lat: 54.675974, lng: 25.2740121, city: 'vilnius' }, // Faculty of Chemistry and Geosciences
  evaf: { lat: 54.72238, lng: 25.332105, city: 'vilnius' }, // Faculty of Economics
  filf: { lat: 54.683494, lng: 25.2885235, city: 'vilnius' }, // Faculty of Philology
  ff: { lat: 54.72238, lng: 25.331061, city: 'vilnius' }, // Faculty of Physics
  fsf: { lat: 54.683777, lng: 25.2870587, city: 'vilnius' }, // Faculty of Philosophy
  gmc: { lat: 54.722313, lng: 25.326331, city: 'vilnius' }, // Life Sciences Center
  if: { lat: 54.6831617, lng: 25.2870202, city: 'vilnius' }, // Faculty of History
  kf: { lat: 54.721975, lng: 25.333105, city: 'vilnius' }, // Faculty of Communication
  knf: { lat: 54.89535, lng: 23.88947, city: 'kaunas' }, // Kaunas Faculty
  mif: { lat: 54.67509, lng: 25.273487, city: 'vilnius' }, // Faculty of Mathematics and Informatics
  mf: { lat: 54.682572, lng: 25.25881457, city: 'vilnius' }, // Faculty of Medicine
  sa: { lat: 55.92864804, lng: 23.3145673, city: 'siauliai' }, // Šiauliai Academy
  tf: { lat: 54.72238, lng: 25.33312, city: 'vilnius' }, // Faculty of Law
  tspmi: { lat: 54.678565, lng: 25.284323, city: 'vilnius' }, // Institute of International Relations
  vm: { lat: 54.72498, lng: 25.33620, city: 'vilnius' }, // Business School
};

const viewMode = useStorage('padalinysSelectorViewMode', 'list'); // 'list' or 'map'
const searchQuery = ref('');
const hoveredLocation = ref<TenantOption | null>(null);
const isPopoverOpen = ref(false);

const { options: options_padaliniai, isActive: isActivePadalinys, switchTenant: handleSelectPadalinys, currentLabel, isSwitchAllowed } = useTenantOptions(props.prependOptions);

const padalinys = currentLabel(props.mainTenantLabel);
const isDisabled = computed(() => !isSwitchAllowed.value);

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
  }
  else {
    // Reset the search when popover is closed
    searchQuery.value = '';
  }
};
</script>
