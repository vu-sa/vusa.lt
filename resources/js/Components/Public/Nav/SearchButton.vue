<template>
  <SmartLink
    :href="searchUrl"
    :title="$t('Paieška')"
    :class="cn(buttonVariants({ variant: 'ghost', animation: 'subtle' }), 'gap-2', props.class)"
  >
    <IFluentSearch20Filled class="h-4 w-4" aria-hidden="true" />
    <slot />
    <span v-if="!$slots.default" class="sr-only">{{ $t('Paieška') }}</span>
  </SmartLink>
</template>

<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { usePage } from '@inertiajs/vue3';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { buttonVariants } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';
import IFluentSearch20Filled from '~icons/fluent/search-20-filled';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

const page = usePage();

// Global unified search page lives on the www subdomain.
const searchUrl = computed(() => {
  const locale = (page.props.app as { locale?: string })?.locale || 'lt';
  try {
    return route('search', { subdomain: 'www', lang: locale });
  }
  catch {
    return '/paieska';
  }
});
</script>
