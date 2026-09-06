<template>
  <SmartLink
    :href="searchUrl"
    :title="$t('Paieška')"
    :class="cn(buttonVariants({ variant: 'ghost', size }), 'gap-2', props.class)"
  >
    <IFluentSearch20Regular class="h-4 w-4" aria-hidden="true" />
    <slot />
    <span v-if="!$slots.default" class="sr-only">{{ $t('Paieška') }}</span>
  </SmartLink>
</template>

<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { localizedRoute, localizedSlug } from '@/Utils/LocalizedRoutes';
import SmartLink from '@/Components/Public/SmartLink.vue';
import { buttonVariants } from '@/Components/ui/button';
import type { ButtonVariants } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';
import IFluentSearch20Regular from '~icons/fluent/search-20-filled';

const props = withDefaults(defineProps<{
  /** Pass `icon` in the header so the control is the same square as the other utility buttons —
   *  the default size's horizontal padding otherwise makes search visibly wider than its row. */
  size?: ButtonVariants['size'];
  class?: HTMLAttributes['class'];
}>(), {
  size: 'default',
  class: undefined,
});

const page = usePage();

// Global unified search page lives on the www subdomain.
const searchUrl = computed(() => {
  const locale = (page.props.app as { locale?: string })?.locale || 'lt';
  try {
    return localizedRoute('search', { subdomain: 'www' }, locale);
  }
  catch {
    // Ziggy is unavailable (SSR without the route list); fall back to the plain path.
    return `/${locale}/${localizedSlug('searchString', locale)}`;
  }
});
</script>
