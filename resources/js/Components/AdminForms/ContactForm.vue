<template>
  <NForm :model="form" label-placement="top">
    <FormElement>
      <template #title>{{ $t("forms.context.main_info") }}</template>
      <NFormItem
        :label="$t('forms.fields.name_and_surname')"
        :span="1"
        required
      >
        <NInput
          v-model:value="form.name"
          type="text"
          placeholder="Vardenis Pavardenis"
        />
      </NFormItem>

      <NFormItem :label="$t('forms.fields.email')" :span="1" required>
        <NInput
          v-model:value="form.email"
          placeholder="vardas.pavarde@email.com"
        />
      </NFormItem>

      <NFormItem :label="$t('forms.fields.phone')" :span="1">
        <NInput v-model:value="form.phone" placeholder="+370 612 34 567" />
      </NFormItem>

      <NFormItem :label="$t('forms.fields.science_degree')" :span="1">
        <NSelect
          v-model:value="form.extra_attributes.degree"
          placeholder="Daktaro laipsnis"
          :options="degreeOptions"
        ></NSelect>
      </NFormItem>

      <NFormItem :label="$t('forms.fields.pedagogical_name')" :span="1">
        <NSelect
          v-model:value="form.extra_attributes.pedagogical_name"
          :disabled="form.extra_attributes.degree !== 'daktaras'"
          placeholder="Docentas"
          :options="pedagogicalNameOptions"
        ></NSelect>
      </NFormItem>
    </FormElement>

    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { NForm, NFormItem, NInput, NSelect } from "naive-ui";
import { useForm } from "@inertiajs/vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  contact: App.Entities.Contact;
  // duties: App.Entities.Duty[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("contact", props.contact);

const degreeOptions = [
  { label: $t("Bakalauro laipsnis"), value: "bakalauras" },
  { label: "Magistro laipsnis", value: "magistras" },
  { label: "Daktaro laipsnis", value: "daktaras" },
];

const pedagogicalNameOptions = [
  { label: "Docentas", value: "docentas" },
  { label: "Profesorius", value: "profesorius" },
];

// const dutyOptions = props.duties.map((duty) => ({
//   label: `${duty.name} (${duty.institution?.padalinys?.shortname})`,
//   value: duty.id,
// }));

// form.duties = props.contact.duties?.map((duty) => duty.id);
</script>
