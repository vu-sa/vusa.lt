<template>
  <component :is="useInertia ? Link : 'a'" v-if="href" :href @click="emit('click', $event)">
    <slot />
  </component>
  <div v-else>
    <slot />
  </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
  href?: string | null;
}>();

const emit = defineEmits<{
  click: [event: MouseEvent];
}>();

const page = usePage();
const useInertia = computed(() => {
  if (!props.href) return false;

  const current = import.meta.env.SSR
    ? new URL(page.url, (page.props.ziggy as { location?: string } | undefined)?.location ?? page.props.app.url)
    : new URL(window.location.href);
  const destination = new URL(props.href, current);

  // Public pages use a separate entry point; signed answer pages return plain HTML.
  return destination.origin === current.origin
    && (destination.pathname === '/mano' || destination.pathname.startsWith('/mano/'));
});
</script>
