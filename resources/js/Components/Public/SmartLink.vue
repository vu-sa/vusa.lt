<template>
  <component :is="useInertiaRouter ? Link : 'a'" v-if="href" :href :prefetch="useInertiaRouter ? prefetch : undefined"
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

const props = defineProps<{
  href?: string | null;
  target?: string;
  prefetch?: boolean;
  cacheFor?: string | number;
}>();

const page = usePage();
const useInertiaRouter = computed(() => {
  if (!props.href?.startsWith('http')) return true;

  // On the server, `ziggy.location` is the request URL; `app.url` is always the www host, which
  // made same-tenant links render as new-tab <a>s on subdomains and stay that way after hydration.
  const current = import.meta.env.SSR
    ? new URL(page.url, (page.props.ziggy as { location?: string } | undefined)?.location ?? page.props.app.url)
    : new URL(window.location.href);
  const destination = new URL(props.href);
  return destination.origin === current.origin;
});
</script>
