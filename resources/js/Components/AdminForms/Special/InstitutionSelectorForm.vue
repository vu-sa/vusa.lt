<template>
  <NForm>
    <SuggestionAlert :show-alert="showAlert" @alert-closed="showAlert = false">
      <p>
        Viena svarbiausių veiklų atstovavime yra
        <strong>dalinimasis informacija</strong>, tada kai ji pasirodo!
      </p>
      <p class="mt-4">
        Būtent
        <ModelChip
          ><template #icon><NIcon :component="Icons.MEETING"></NIcon></template
          >posėdžiai</ModelChip
        >
        ir jų informacija yra labai svarbi – kad galėtume atstovauti studentams
        geriausiai, kaip tik tai įmanoma!
      </p>
      <p class="mt-4"><strong>Pradėkim! 💪</strong></p>
    </SuggestionAlert>
    <NFormItem>
      <template #label>
        <span class="flex items-center gap-1">
          <NIcon :component="Icons.INSTITUTION"></NIcon>
          Institucija
        </span>
      </template>

      <NSelect
        v-model:value="institution_id"
        class="min-w-[260px]"
        :options="institutions"
        :placeholder="'VU studijų programos komitetas...'"
      ></NSelect>
    </NFormItem>
    <NButton @click="$emit('submit', institution_id)">Pasirinkti...</NButton>
  </NForm>
</template>

<script setup lang="tsx">
import { NButton, NForm, NFormItem, NIcon, NSelect } from "naive-ui";
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/filled";
import ModelChip from "@/Components/Chips/ModelChip.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";

defineEmits<{
  (e: "submit", data: string): void;
}>();

const institution_id = ref<"string" | null>(null);

const showAlert = ref(true);

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
