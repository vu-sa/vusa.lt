<template>
  <NDropdown :disabled="isDisabled" :options="options_padaliniai" size="small" style="overflow: auto; max-height: 440px"
    :render-label="renderPadalinysLabel" @select="$emit('select:padalinys', $event)">
    <NButton :disabled="isDisabled" :size="size" icon-placement="right">
      {{ padalinys }}
      <template #icon>
        <IFluentChevronDown20Regular />
      </template>
    </NButton>
  </NDropdown>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import {
  type DropdownOption,
  NEllipsis,
} from "naive-ui";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

defineEmits<{
  (event: "select:padalinys", key: string): void;
}>();

const props = defineProps<{
  size: "tiny" | "small";
}>();

const options_padaliniai = computed<DropdownOption[]>(() => {
  return usePage()
    .props.tenants.filter(
      (tenant) => tenant.type === "padalinys" && tenant.id <= 17
    )
    .map((tenant) => ({
      label:
        props.size.toLowerCase() === "tiny"
          ? $t(tenant.shortname.split(" ")[2])
          : $t(tenant.fullname.split("atstovybė ")[1]),
      key: tenant.alias,
    }));
});

const padalinys = computed(() => {
  return $t(
    usePage().props.tenant?.alias !== "vusa"
      ? usePage().props.tenant?.shortname.split(" ").pop() ?? "Padaliniai"
      : "Padaliniai",
  );
});

const isDisabled = computed(() => {
  if (["lt", "en", "lt/naujienos"].includes(usePage().props.app.path)) {
    return false;
  }

  // check if contains kontaktai or contacts
  if (usePage().props.app.path.includes("kontaktai")) {
    return false;
  }

  if (usePage().props.app.path.includes("contacts")) {
    return false;
  }

  return true;
});

const renderPadalinysLabel = (option: DropdownOption) => {
  return (
    <NEllipsis style={props.size === "tiny" ? "max-width: 200px" : ""}>
      {option.label}
    </NEllipsis>
  );
};
</script>
