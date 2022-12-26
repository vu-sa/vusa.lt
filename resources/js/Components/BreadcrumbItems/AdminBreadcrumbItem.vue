<template>
  <NBreadcrumbItem @click="visitRoute ? Inertia.visit(visitRoute) : null">
    <NIcon v-if="icon" class="mr-1" size="16" :component="icon" />
    <slot />
  </NBreadcrumbItem>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { NBreadcrumbItem, NIcon } from "naive-ui";

defineProps<{
  visitRoute?: string;
  icon?: any;
}>();
</script>
