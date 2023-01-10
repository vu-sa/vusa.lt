<template>
  <NBreadcrumbItem
    @click="
      option.routeOptions
        ? Inertia.visit(
            route(option.routeOptions.name, option.routeOptions.params)
          )
        : undefined
    "
  >
    <div v-if="!option.dropdownOptions">
      <NIcon class="mr-1" size="16" :component="option.icon" />
      {{ option.label }}
    </div>
    <NDropdown v-else :options="option.dropdownOptions">
      <component :is="iconWithLabel" v-if="option.icon" />
      <span v-else>...</span>
    </NDropdown>
  </NBreadcrumbItem>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { NBreadcrumbItem, NDropdown, NIcon } from "naive-ui";

import type { Component } from "vue";
import type { DropdownOption } from "naive-ui";
import type { RouteParam, RouteParamsWithQueryOverload } from "ziggy-js";

export interface BreadcrumbOption {
  label: string | null;
  icon?: Component;
  dropdownOptions?: DropdownOption[];
  routeOptions?: {
    name: string;
    params?: RouteParamsWithQueryOverload | RouteParam;
  };
}

const props = defineProps<{
  option: BreadcrumbOption;
}>();

const iconWithLabel = () => {
  return (
    <div>
      <NIcon class="mr-1" size="16" component={props.option.icon} />
      {props.option.label}
    </div>
  );
};
</script>
