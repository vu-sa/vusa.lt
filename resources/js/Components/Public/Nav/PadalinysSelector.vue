<template>
  <NScrollbar>
    <NDropdown
      :options="options_padaliniai"
      size="small"
      style="overflow: auto; max-height: 600px"
      :render-label="renderPadalinysLabel"
      @select="$emit('select:padalinys', $event)"
    >
      <NButton
        :disabled="route().current('*page')"
        :size="size"
        style="border-radius: 0.5rem"
      >
        {{ $t(padalinys) }}
        <NIcon class="ml-1" size="18">
          <ChevronDown20Filled />
        </NIcon>
      </NButton>
    </NDropdown>
  </NScrollbar>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { ChevronDown20Filled } from "@vicons/fluent";
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
  padalinys: string;
  size: "tiny" | "small";
}>();

const options_padaliniai = computed<DropdownOption[]>(() => {
  return usePage().props.padaliniai.map((padalinys) => ({
    label:
      props.size.value === "tiny"
        ? padalinys.alias
        : $t(padalinys.fullname.split("atstovybė ")[1]),
    key: padalinys.alias,
  }));
});

const renderPadalinysLabel = (option: DropdownOption) => {
  return (
    <NEllipsis style={props.size === "tiny" ? "max-width: 200px" : ""}>
      {option.label}
    </NEllipsis>
  );
};
</script>
