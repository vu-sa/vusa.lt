<template>
  <NBreadcrumb>
    <AdminBreadcrumbItem :option="homeOption" />
    <AdminBreadcrumbItem v-for="(option, index) in options" :key="index" :option="option" />
  </NBreadcrumb>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";

import Home24Filled from "~icons/fluent/home24-filled";

import AdminBreadcrumbItem, {
  type BreadcrumbOptionInterface,
} from "./AdminBreadcrumbItem.vue";

export type BreadcrumbOption = BreadcrumbOptionInterface;

defineProps<{
  options: BreadcrumbOption[];
}>();

const homeOption = computed(() => {
  return {
    label: $t("Pradinis"),
    icon: Home24Filled,
    routeOptions: { name: "dashboard" },
  };
});
</script>
