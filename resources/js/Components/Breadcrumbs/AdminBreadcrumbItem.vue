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
      <component :is="iconWithLabel" />
    </NDropdown>
  </NBreadcrumbItem>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { NBreadcrumbItem, NDropdown, NIcon } from "naive-ui";


const props = defineProps<{
  option: App.Props.BreadcrumbOption;
}>();

const iconWithLabel = () => {
  return (
    <div>
      <NIcon class="mr-1" size="16" component={props.option.icon} />
      {props.option.label}
    </div>
  );
};

// const handleBreadcrumbDropdownSelect = (key) => {
//   switch (key) {
//     case "dashboard":
//       Inertia.visit(route("dashboard"));
//       break;
//     case "institution":
//       Inertia.visit(route("institutions.show", props.matter.institution.id));
//       break;
//     default:
//       break;
//   }
// };
</script>
