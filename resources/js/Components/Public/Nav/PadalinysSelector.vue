<template>
  <NScrollbar>
    <NDropdown
      :options="options_padaliniai"
      placement="top-start"
      size="small"
      style="overflow: auto; max-height: 600px"
      @select="$emit('select:padalinys', $event)"
    >
      <NButton
        :disabled="route().current('*page')"
        size="small"
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
  NIcon,
  NScrollbar,
} from "naive-ui";
import { usePage } from "@inertiajs/vue3";

defineEmits<{
  (event: "select:padalinys", key: string): void;
}>();

defineProps<{
  padalinys: string;
}>();

const options_padaliniai: DropdownOption[] = usePage().props.padaliniai.map(
  (padalinys) => ({
    label: $t(padalinys.fullname.split("atstovybė ")[1]),
    key: padalinys.alias,
  })
);
</script>
