<template>
  <NScrollbar>
    <NDropdown :disabled="isDisabled"
      :options="options_padaliniai"
      size="small"
      style="overflow: auto; max-height: 600px"
      :render-label="renderPadalinysLabel"
      @select="$emit('select:padalinys', $event)"
    >
      <NButton
        :disabled="isDisabled"
        :size="size"
      >
        {{ padalinys }}
        <NIcon class="ml-1" size="18" :component="ChevronDown20Regular" />
      </NButton>
    </NDropdown>
  </NScrollbar>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ChevronDown20Regular } from "@vicons/fluent";
import {
  type DropdownOption,
  NButton,
  NDropdown,
  NEllipsis,
  NIcon,
  NScrollbar,
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
    .props.padaliniai.filter(
      (padalinys) => padalinys.type === "padalinys" && padalinys.id <= 17
    )
    .map((padalinys) => ({
      label:
        props.size.toLowerCase() === "tiny"
          ? $t(padalinys.shortname.split(" ")[2])
          : $t(padalinys.fullname.split("atstovybė ")[1]),
      key: padalinys.alias,
    }));
});

const padalinys = computed(() => {
  return $t(
    usePage().props.padalinys?.alias !== "vusa"
      ? usePage().props.padalinys?.shortname.split(" ").pop() ?? "Padaliniai"
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
