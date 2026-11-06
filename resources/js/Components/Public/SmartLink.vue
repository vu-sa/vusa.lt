<template>
  <component :is="useInertiaRouter ? Link : 'a'" v-if="safeUrl" :href="safeUrl" :prefetch="useInertiaRouter ? prefetch : undefined"
    :cache-for="useInertiaRouter ? cacheFor : undefined"
    :target="target ?? (useInertiaRouter ? undefined : '_blank')">
    <slot />
  </component>
  <span v-else>
    <slot />
  </span>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { safeHref } from '@/Utils/safeHref';

const props = defineProps<{
  href?: string | null;
  target?: string;
  prefetch?: boolean;
  cacheFor?: string | number;
}>();

const page = usePage();
const safeUrl = computed(() => safeHref(props.href));
const useInertiaRouter = computed(() => {
  if (!safeUrl.value || /^(mailto|tel):/i.test(safeUrl.value.replace(/[\u0000-\u0020\u007f]/g, ''))) return false;

  try {
    const current = import.meta.env.SSR
      ? new URL(page.url, (page.props.ziggy as { location?: string } | undefined)?.location ?? page.props.app.url)
      : new URL(window.location.href);
    const destination = new URL(safeUrl.value, current);
    return destination.origin === current.origin;
  } catch {
    return false;
  }
});
</script>
