<template>
  <Link
    :href="route(asideHeaderRoute)"
    class="mr-4 last:mr-0 duration-200"
    :class="
      route().current(asideHeaderRoute)
        ? ['text-red-800', 'hover:text-red-900']
        : ['text-gray-600', 'hover:text-gray-800']
    "
  >
    <slot></slot>
  </Link>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

defineProps<{ asideHeaderRoute: string }>();
</script>
