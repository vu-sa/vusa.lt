<template>
  <NButton
    :tag="Link"
    secondary
    round
    size="tiny"
    :href="route(asideHeaderRoute)"
    class="mr-4 duration-200 last:mr-0"
  >
    <slot />
  </NButton>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { NButton } from "naive-ui";
import route from "ziggy-js";

defineProps<{ asideHeaderRoute: string }>();
</script>
