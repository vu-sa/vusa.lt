<template>
  <Popover @update:open="handlePopoverOpenChange">
    <PopoverTrigger as-child>
      <Button
        variant="ghost"
        :size="size === 'tiny' ? 'sm' : 'default'"
        class="flex w-auto items-center justify-between gap-2 border border-border
          text-sm font-bold uppercase tracking-wide
          text-foreground transition-colors duration-200
          hover:border-brand hover:bg-transparent hover:text-brand
          dark:hover:bg-transparent dark:hover:text-brand"
        :disabled="isDisabled"
        :title="$t('Pasirinkti padalinį')"
      >
        <div class="flex items-center gap-2">
          <!-- Brand-coloured pin: it is the one mark that tells a reader this control is about
               *where* they are. The label itself is full-strength — this names the current unit,
               so it is content, not chrome. -->
          <IFluentLocation24Regular class="h-4 w-4 text-brand" />
          <span>{{ padalinys }}</span>
        </div>
        <IFluentChevronDown24Regular class="h-4 w-4 opacity-50 transition-transform duration-200" :class="{ 'rotate-180': isPopoverOpen }" />
      </Button>
    </PopoverTrigger>
    <PopoverContent class="p-0" :class="{ 'w-[300px]': viewMode === 'list', 'w-[520px]': viewMode === 'map' }" align="start">
      <div class="flex items-center gap-2 border-b border-border p-2">
        <Button
          v-for="view in (['list', 'map'] as const)"
          :key="view"
          :variant="viewMode === view ? 'brand' : 'ghost'"
          size="public-sm"
          class="flex-1"
          :class="viewMode !== view && 'text-muted-foreground hover:text-foreground'"
          @click="setViewMode(view)"
        >
          <component :is="view === 'list' ? IFluentList24Regular : IFluentMap24Regular" class="h-4 w-4" />
          {{ view === 'list' ? $t('List') : $t('Map') }}
        </Button>

        <Input
          v-if="viewMode === 'map'"
          v-model="searchQuery"
          class="h-8 w-32 shrink-0"
          :placeholder="`${$t('Ieškoti')}...`"
        />
      </div>

      <!-- List View -->
      <div v-if="viewMode === 'list'" class="padalinys-list">
        <ScrollArea class="h-[350px]">
          <div class="overflow-hidden">
            <template v-for="option in options_padaliniai" :key="option.key">
              <!--
                A unit with a photo renders as one of the mega menu's image items: the picture is
                the row's ground, grayscale behind a scrim, with the name laid over it. Units
                without one keep the plain lettered row, so the list stays scannable either way.
              -->
              <button
                type="button"
                :class="[
                  'group relative flex w-full cursor-pointer items-center gap-3 border-l-2 text-left transition-colors',
                  'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
                  option.primary_institution?.image_url ? 'min-h-[4.5rem] bg-ink p-4' : 'px-4 py-2.5 hover:bg-secondary',
                  isActivePadalinys(option.key) ? 'border-brand' : 'border-transparent',
                  isActivePadalinys(option.key) && !option.primary_institution?.image_url && 'bg-secondary/60',
                ]"
                @click="handleSelectPadalinys(option.key)"
              >
                <template v-if="option.primary_institution?.image_url">
                  <img
                    :src="option.primary_institution.image_url"
                    alt=""
                    class="absolute inset-0 size-full object-cover opacity-60 grayscale transition-transform duration-500 group-hover:scale-105"
                  >
                  <span class="absolute inset-0 bg-gradient-to-r from-ink via-ink/70 to-ink/30" />
                </template>

                <Avatar v-else class="size-9 shrink-0 rounded-none">
                  <!-- `rounded-none` explicitly: the primitive's base is `rounded-full`, which is a
                       literal and so survives the public surface's zeroed radius scale. The design
                       has no circles. -->
                  <AvatarFallback class="rounded-none bg-secondary text-[11px] font-bold uppercase tracking-wide text-foreground">
                    {{ option.key.substring(0, 2).toUpperCase() }}
                  </AvatarFallback>
                </Avatar>

                <span class="relative z-10 min-w-0 flex-1">
                  <span
                    class="block truncate text-sm font-bold transition-colors"
                    :class="option.primary_institution?.image_url ? 'text-white group-hover:text-brand-fill' : 'text-foreground group-hover:text-brand'"
                  >
                    {{ option.label }}
                  </span>
                  <!-- The main office's unit name and short name are both "VU SA"; showing both
                       stacks the same words twice. -->
                  <span
                    v-if="secondaryLabel(option)"
                    class="block truncate text-xs"
                    :class="option.primary_institution?.image_url ? 'text-white/70' : 'text-muted-foreground'"
                  >
                    {{ secondaryLabel(option) }}
                  </span>
                </span>

                <IFluentCheckmark24Regular
                  v-if="isActivePadalinys(option.key)"
                  class="relative z-10 size-4 shrink-0"
                  :class="option.primary_institution?.image_url ? 'text-brand-fill' : 'text-brand'"
                />
              </button>
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
import { trans as $t } from 'laravel-vue-i18n';

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
import { Avatar, AvatarFallback } from '@/Components/ui/avatar';
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

/** The short name below a unit's name, unless it just repeats it. */
function secondaryLabel(option: TenantOption): string {
  const shortName = $t(option.primary_institution?.short_name ?? '');

  return shortName.trim().toLowerCase() === (option.label ?? '').trim().toLowerCase() ? '' : shortName;
}
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
