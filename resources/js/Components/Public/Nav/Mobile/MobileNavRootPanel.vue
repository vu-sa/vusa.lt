<template>
  <div class="flex flex-col gap-1 p-3">
    <SearchButton class="flex min-h-11 items-center justify-start gap-3 px-3 py-2.5 text-base font-normal hover:bg-secondary">
      {{ $t('Paieška') }}
    </SearchButton>

    <button
      v-if="isSwitchAllowed"
      type="button"
      class="flex min-h-11 items-center gap-3 px-3 py-2.5 text-left transition-colors hover:bg-secondary focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
      @click="$emit('openTenants')"
    >
      <IFluentLocation24Regular class="size-5 text-muted-foreground" />
      <span class="flex-1">{{ tenantLabel }}</span>
      <IFluentChevronRight24Regular class="size-4 shrink-0 text-muted-foreground" />
    </button>

    <hr class="my-2 border-border">

    <button
      v-for="(item, index) in mainNavigation"
      :key="item.name"
      type="button"
      class="flex min-h-11 items-center gap-3 px-3 py-2.5 text-left text-base font-medium transition-colors hover:bg-secondary focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
      @click="$emit('openSection', index)"
    >
      <span class="flex-1">{{ item.name }}</span>
      <IFluentChevronRight24Regular class="size-4 shrink-0 text-muted-foreground" />
    </button>

    <template v-if="tenantLinks.length">
      <p class="mt-4 px-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
        {{ $t('navigation.tenant_links', { tenant: tenantShortname }) }}
      </p>
      <div class="flex flex-col gap-1 py-1">
        <QuickLink
          v-for="link in tenantLinks"
          :key="link?.id"
          class="min-h-11 items-center px-3 py-2"
          :quick-link="link"
          @click="$emit('close')"
        />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import QuickLink from '../QuickLink.vue';

import SearchButton from '@/Components/Public/Nav/SearchButton.vue';
import { useTenantOptions } from '@/Composables/useTenantOptions';
import IFluentChevronRight24Regular from '~icons/fluent/chevron-right-24-regular';
import IFluentLocation24Regular from '~icons/fluent/location-24-regular';

defineEmits<{
  openSection: [index: number];
  openTenants: [];
  close: [];
}>();

const page = usePage();

const mainNavigation = computed(() => page.props.mainNavigation ?? []);
const tenantLinks = computed(() => page.props.tenant?.links ?? []);
const tenantShortname = computed(() => page.props.tenant?.shortname ? $t(page.props.tenant.shortname) : 'VU SA');

const { currentLabel, isSwitchAllowed } = useTenantOptions();
const tenantLabel = currentLabel();
</script>
