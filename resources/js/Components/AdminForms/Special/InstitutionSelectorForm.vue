<template>
  <NForm>
    <NFormItem>
      <template #label>
        <span class="flex items-center gap-1">
          <NIcon :component="Icons.INSTITUTION"></NIcon>
          Institucija
        </span>
      </template>
      <NSelect
        v-model:value="institution_id"
        class="min-w-[200px]"
        :options="institutions"
        :placeholder="'Pasirinkite instituciją'"
      ></NSelect>
    </NFormItem>
    <NButton @click="$emit('submit', institution_id)">Pasirinkti</NButton>
  </NForm>
</template>

<script setup lang="tsx">
import { NButton, NForm, NFormItem, NIcon, NSelect } from "naive-ui";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/filled";

defineEmits<{
  (e: "submit", data: string): void;
}>();

const institution_id = ref<"string" | null>(null);

const institutions = computed(() => {
  return usePage()
    .props.auth?.user?.duties?.map((duty) => {
      if (!duty.institution) {
        return;
      }

      return {
        label: duty.institution?.name,
        value: duty.institution?.id,
      };
    })
    .filter((institution) => institution !== undefined);
});
</script>
