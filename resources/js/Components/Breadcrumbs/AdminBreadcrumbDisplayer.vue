<template>
  <NBreadcrumb>
    <AdminBreadcrumbItem
      v-for="option in breadcrumbOptions"
      :key="option.label"
      :option="option"
    ></AdminBreadcrumbItem
  ></NBreadcrumb>
</template>

<script setup lang="ts">
import { Home24Filled } from "@vicons/fluent";
import { NBreadcrumb } from "naive-ui";
import { computed } from "vue";

import AdminBreadcrumbItem, {
  type BreadcrumbOption,
} from "@/Components/Breadcrumbs/AdminBreadcrumbItem.vue";

const props = defineProps<{
  options: BreadcrumbOption[];
}>();

const breadcrumbOptions = computed<BreadcrumbOption[]>(() => {
  let options = props.options;

  // ? Pasiūlė Copilot
  // if (options.length > 3) {
  //   options = options.slice(0, 3);
  //   options.push({
  //     id: "ellipsis",
  //     title: "...",
  //     url: "#",
  //   });
  // }

  // add Home item to beggining
  options.unshift({
    label: "Pradinis",
    icon: Home24Filled,
    routeOptions: { name: "dashboard" },
  });

  return options;
});
</script>
