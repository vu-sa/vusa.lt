<template>
  <NForm>
    <SuggestionAlert :show-alert="showAlert" @alert-closed="showAlert = false">
      <p v-if="$page.props.app.locale === 'lt'">
        Viena svarbiausių veiklų atstovavime yra
        <strong>dalinimasis informacija</strong>, tada kai ji pasirodo!
      </p>
      <p v-else>
        One of the most important activities in representation is
        <strong>sharing information</strong> when it appears!
      </p>
      <p class="mt-4">
        {{ $t('Būtent') }}
        <ModelChip>
          <template #icon>
            <Icons.MEETING />
          </template>{{ $t('posėdžiai') }}
        </ModelChip>
        <template v-if="$page.props.app.locale === 'lt'">
          "
          ir jų informacija yra labai svarbi – kad galėtume atstovauti studentams geriausiai, kaip tik tai įmanoma!
        </template>
        <template v-else>
          and their information is very important – so we can represent students as best as possible!
        </template>
      </p>
      <p class="mt-4">
        <strong>{{ $t('Pradėkim') }}! 💪</strong>
      </p>
    </SuggestionAlert>
    <NFormItem>
      <template #label>
        <span class="flex items-center gap-1">
          <NIcon :component="Icons.INSTITUTION" />
          {{ $t("Institucija") }}
        </span>
      </template>

      <NSelect v-model:value="institution_id" filterable class="min-w-[260px]" :options="institutions"
        :placeholder="'VU studijų programos komitetas...'" />
    </NFormItem>
    <NButton :disabled="!institution_id" @click="$emit('submit', institution_id)">
      {{ $t("Toliau") }}...
    </NButton>
  </NForm>
</template>

<script setup lang="ts">
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
    .props.auth?.user?.current_duties?.map((duty) => {
      if (!duty.institution) {
        return;
      }

      return {
        label: duty.institution?.name,
        value: duty.institution?.id,
      };
    })
    // filter unique
    .filter((institution) => institution !== undefined).filter(
      (value, index, self) =>
        self.findIndex((t) => t?.value === value?.value) === index
    );
});
</script>
