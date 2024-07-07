<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>
          Pareigybės ėjimo laikotarpis
        </template>
        <template #description>
          Šioje vietoje nustatomas pareigybės ėjimo laikotarpis. Kai žymima
          pareigybės laikotarpio ėjimo pabaiga, pareigybės pavadinimas bus
          giminizuotas pagal vardą ir pavardę.
        </template>
        <NFormItem label="Pareigų ėjimo pradžia" required>
          <NDatePicker v-model:formatted-value="form.start_date" value-format="yyyy-MM-dd" type="date" />
        </NFormItem>
        <NFormItem label="Pareigų ėjimo pabaiga" required>
          <NDatePicker v-model:formatted-value="form.end_date" clearable value-format="yyyy-MM-dd" type="date" />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>
          Papildoma informacija
        </template>
        <NFormItem>
          <template #label>
            <span class="inline-flex gap-1">
              <span> Papildomas tekstas </span>
              <InfoPopover>Šalia pareigybės skliausteliuose atsiranda šis papildomas
                tekstas.</InfoPopover>
            </span>
          </template>
          <NInput v-if="locale === 'lt'" v-model:value="form.extra_attributes.study_program" type="text"
            placeholder="Įrašyti tekstą...">
            <template #suffix>
              <SimpleLocaleButton v-model:locale="locale" />
            </template>
          </NInput>
          <NInput v-else-if="locale === 'en'" v-model:value="form.extra_attributes.en.study_program" type="text"
            placeholder="Add text...">
            <template #suffix>
              <SimpleLocaleButton v-model:locale="locale" />
            </template>
          </NInput>
        </NFormItem>
        <NFormItem>
          <template #label>
            <div class="inline-flex gap-1">
              <span>Papildoma nuotrauka</span>
              <InfoPopover>Ši nuotrauka leidžia vienam asmeniui turėti daugiau negu vieną
                nuotrauką, kuri rodoma, kai puslapyje asmuo vaizduojamas su šia
                pareigybe.</InfoPopover>
            </div>
          </template>
          <NMessageProvider>
            <UploadImageWithCropper v-model:url="form.extra_attributes.additional_photo" folder="contacts" />
          </NMessageProvider>
        </NFormItem>
        <NFormItem>
          <template #label>
            <div class="inline-flex items-center gap-2">
              Aprašymas
              <SimpleLocaleButton v-model:locale="locale" />
            </div>
          </template>
          <TipTap v-if="locale === 'lt'" v-model="form.extra_attributes.info_text" html />
          <TipTap v-else-if="locale === 'en'" v-model="form.extra_attributes.en.info_text" html />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>
          Įvardžiai
        </template>
        <template #description>
          <p>Asmens įvardžius gali nusistatyti pats asmuo, prisijungęs prie mano.vusa.lt, savo nustatymų lange. Taip pat
            galima nustatyti
            <Link :href="route('users.edit', dutiable?.dutiable_id)">
            čia
            </Link>.
          </p>
          <p> Pareigos pavadinimas bus rodomas taip: <strong> {{ changeDutyNameEndings(dutiable.dutiable, dutiable.duty,
            $page.props.app.locale, dutiable.dutiable.pronouns, dutiable.extra_attributes.use_original_duty_name)
              }}</strong>
          </p>
        </template>
        <NFormItem>
          <template #label>
            <span class="inline-flex gap-1">
              <span>
                Pareigos pavadinimo galūnės
                <strong>negiminizavimas</strong>
              </span>
              <InfoPopover>Išjungia automatinį šios kontakto pareigybės giminizavimą pagal
                vardą ir pavardę. Bus naudojamas originalus pareigybės
                pavadinimas.</InfoPopover>
            </span>
          </template>
          <NSwitch v-model:value="form.extra_attributes.use_original_duty_name" />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton v-if="deleteModelRoute" :form="form" :model-route="deleteModelRoute" />
      <UpsertModelButton :route-parameters="[dutiable.id]" :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import { Link, useForm } from "@inertiajs/vue3";
import {
  NDatePicker,
  NForm,
  NFormItem,
  NInput,
  NMessageProvider,
  NSwitch,
} from "naive-ui";
import { ref } from "vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";

import { changeDutyNameEndings } from "@/Utils/String";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import UploadImageWithCropper from "../Buttons/UploadImageWithCropper.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  dutiable: App.Entities.Dutiable;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("dutiable", props.dutiable);

const locale = ref("lt");

if (!form.extra_attributes || form.extra_attributes.length === 0) {
  form.extra_attributes = {};
}

if (!form.extra_attributes.study_program) {
  form.extra_attributes.study_program = "";
}

if (!form.extra_attributes.info_text) {
  form.extra_attributes.info_text = "";
}

if (!form.extra_attributes.additional_photo) {
  form.extra_attributes.additional_photo = "";
}

if (!form.extra_attributes.en) {
  form.extra_attributes.en = {};
}

// if form.extra_attributes.en is empty array then create empty object
if (form.extra_attributes.en.length === 0) {
  form.extra_attributes.en = {};
}
</script>
