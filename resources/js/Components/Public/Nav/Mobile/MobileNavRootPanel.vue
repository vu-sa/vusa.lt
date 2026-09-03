<template>
  <div>
    <!-- Tenant quick links as bordered chips, the important one carrying the brand. Chips rather
         than rows: there are only a handful and they are shortcuts, not a section of the menu. -->
    <div v-if="tenantLinks.length" class="flex flex-wrap gap-2 border-b border-border p-4">
      <SmartLink
        v-for="link in tenantLinks"
        :key="link?.id"
        prefetch
        :href="link?.link ?? '#'"
        class="inline-flex items-center gap-1.5 border px-3 py-2 text-sm transition-colors"
        :class="link?.is_important
          ? 'border-brand font-bold text-brand'
          : 'border-border font-medium text-muted-foreground hover:text-foreground'"
        @click="$emit('close')"
      >
        <Icon v-if="link?.icon" :icon="`fluent:${link.icon}`" class="size-3.5 shrink-0" />
        {{ link?.text }}
      </SmartLink>
    </div>

    <!-- Accordion, not a drill-down. Every section is one tap from the top level and the reader
         keeps their place in the list; the previous stack hid the rest of the menu behind a
         back button. -->
    <nav>
      <div v-if="isSwitchAllowed" class="border-b border-border">
        <button
          type="button"
          :class="sectionTriggerClass"
          :aria-expanded="openSection === TENANTS_SECTION"
          @click="toggle(TENANTS_SECTION)"
        >
          <span class="flex items-center gap-2">
            <IFluentLocation24Regular class="size-4 text-brand" />
            {{ tenantLabel }}
          </span>
          <IFluentAdd24Regular :class="togglerClass(TENANTS_SECTION)" />
        </button>

        <div v-if="openSection === TENANTS_SECTION" class="bg-secondary/40">
          <MobileTenantPanel />
        </div>
      </div>

      <div v-for="(item, index) in mainNavigation" :key="item.name" class="border-b border-border">
        <button
          type="button"
          :class="sectionTriggerClass"
          :aria-expanded="openSection === index"
          @click="toggle(index)"
        >
          {{ item.name }}
          <IFluentAdd24Regular :class="togglerClass(index)" />
        </button>

        <div v-if="openSection === index" class="bg-secondary/40">
          <MobileNavSectionPanel :item @close="$emit('close')" />
        </div>
      </div>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

import SmartLink from '../../SmartLink.vue';

import MobileNavSectionPanel from './MobileNavSectionPanel.vue';
import MobileTenantPanel from './MobileTenantPanel.vue';

import { useTenantOptions } from '@/Composables/useTenantOptions';
import IFluentAdd24Regular from '~icons/fluent/add-24-regular';
import IFluentLocation24Regular from '~icons/fluent/location-24-regular';

defineEmits<{
  close: [];
}>();

const page = usePage();

const mainNavigation = computed(() => page.props.mainNavigation ?? []);
const tenantLinks = computed(() => page.props.tenant?.links ?? []);

const { currentLabel, isSwitchAllowed } = useTenantOptions();
const tenantLabel = currentLabel();

/** Sentinel for the tenants section, which has no index in `mainNavigation`. */
const TENANTS_SECTION = -1;

const openSection = ref<number | null>(null);

const toggle = (index: number) => {
  openSection.value = openSection.value === index ? null : index;
};

const sectionTriggerClass = 'flex w-full items-center justify-between gap-3 px-4 py-4 text-left '
  + 'text-base font-bold uppercase tracking-wide text-foreground transition-colors hover:text-brand '
  + 'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring';

/** A plus that rotates into a cross — one mark for both states, as in the design. */
const togglerClass = (index: number) => [
  'size-5 shrink-0 text-brand transition-transform duration-200',
  openSection.value === index && 'rotate-45',
];
</script>
