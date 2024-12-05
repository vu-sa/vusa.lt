<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <p>
          Užpildžius šią informaciją, bus sukurtas mokymų juodraštis, kuriame bus galima užpildyti pilną mokymų
          informaciją.
        </p>
        <p>
          Įrašytą informaciją bus galima pakeisti.
        </p>
      </template>
      <NFormItem required>
        <template #label>
          <span class="inline-flex items-center gap-1">
            <NIcon :component="Icons.TITLE" />
            Pavadinimas
          </span>
        </template>
        <MultiLocaleInput v-model:input="form.name" />
      </NFormItem>
      <NFormItem required>
        <template #label>
          <div class="inline-flex items-center gap-2">
            Aprašymas
            <SimpleLocaleButton v-model:locale="locale" />
          </div>
        </template>
        <TipTap v-if="locale === 'lt'" v-model="form.description.lt" html />
        <TipTap v-else v-model="form.description.en" html />
      </NFormItem>
      <NFormItem required>
        <template #label>
          <span class="flex items-center gap-1">
            <NIcon :component="Icons.INSTITUTION" />
            {{ $t("Kas organizuoja mokymus?") }}
          </span>
        </template>

        <NSelect v-model:value="form.institution_id" filterable :options="institutions" />
      </NFormItem>
      <NFormItem label="Preliminari mokymų pradžia" required>
        <NDatePicker v-model:value="form.start_time" :first-day-of-week="0" :format="'yyyy-MM-dd HH:mm'"
          :time-picker-props="{
            format: 'HH:mm',
            minutes: 5,
            hours: Array.from({ length: 22 - 8 + 1 }, (v, i) => i + 8),
          }" type="datetime" clearable :actions="['confirm']" />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="tsx">
import { useForm, usePage } from "@inertiajs/vue3";
import { NIcon } from "naive-ui";
import { computed, ref } from "vue";

import FormElement from "./FormElement.vue";
import AdminForm from "./AdminForm.vue";
import Icons from "@/Types/Icons/regular";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";

const { training } = defineProps<{
  training: App.Entities.Membership;
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = useForm("training", training);
const locale = ref("lt");

// NOTE: Duplicated in InstitutionSelectorForm.vue
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
