<template>
  <Link :href="route(asideHeaderRoute)">
    <NButton secondary round size="tiny" class="mr-4 duration-200 last:mr-0">
      <slot />
    </NButton>
  </Link>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { NButton } from "naive-ui";
import route from "ziggy-js";

defineProps<{ asideHeaderRoute: string }>();
</script>
