<template>
  <SectionBand divider="top" spacing="tight">
    <EyebrowLabel :text="$t('accessibility.partner_organizations')" class="text-center text-muted-foreground" />

    <RuledGrid :columns="{ base: 2, sm: 3, lg: 5 }" align="center" top-rule="full" class="mt-6">
      <SmartLink
        v-for="banner in banners"
        :key="banner.id"
        :href="banner.link_url || null"
        :aria-label="`${$t('accessibility.visit')} ${banner.title}`"
        :class="[
          'flex items-center justify-center px-4 py-8 text-center',
          banner.link_url && 'group transition-colors hover:bg-secondary focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
        ]"
      >
        <img
          v-if="banner.image_url"
          :src="banner.image_url"
          :alt="banner.title"
          loading="lazy"
          :class="[
            'max-h-10 w-auto object-contain transition-[filter] duration-300',
            'grayscale group-hover:grayscale-0',
            // Dark logos need inverting to read against the near-black canvas — but only at
            // rest. Reversing the invert on hover would recolour the mark in dark mode too,
            // which is the light-mode-only affordance; `dark:group-hover:grayscale` re-asserts
            // grayscale on hover to cancel the plain (light-mode) `group-hover:grayscale-0`.
            'dark:invert dark:group-hover:grayscale',
          ]"
        >
        <span
          v-else
          class="text-base font-bold uppercase leading-tight tracking-wide text-foreground transition-colors group-hover:text-brand"
        >
          {{ banner.title }}
        </span>
      </SmartLink>
    </RuledGrid>
  </SectionBand>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { EyebrowLabel, SectionBand } from '@/Components/Public/Base';
import { RuledGrid } from '@/Components/Brand';

defineProps<{
  banners: App.Entities.Banner[];
}>();
</script>
