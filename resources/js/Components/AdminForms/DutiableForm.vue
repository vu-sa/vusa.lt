<template>
  <NForm :model="form" label-placement="top">
    <NTabs animated type="card" pane-class="overflow-x-auto">
      <NTabPane display-directive="show" name="lt" tab="🇱🇹">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi :span="2">
            <template #label>
              <div class="flex gap-1">
                <span> Papildoma informacija </span>
              </div>
            </template>
            <NInput
              v-model:value="form.extra_attributes.study_program"
              type="text"
              placeholder="Įrašyti tekstą..."
            />
          </NFormItemGi>
          <NFormItemGi label="Pareigų ėjimo pradžia" required :span="2"
            ><NDatePicker
              v-model:formatted-value="form.start_date"
              value-format="yyyy-MM-dd"
              type="date"
              clearable
          /></NFormItemGi>
          <NFormItemGi label="Pareigų ėjimo pabaiga" required :span="2"
            ><NDatePicker
              v-model:formatted-value="form.end_date"
              value-format="yyyy-MM-dd"
              type="date"
              clearable
          /></NFormItemGi>
          <NFormItemGi label="Aprašymas" :span="6">
            <TipTap
              v-model="form.extra_attributes.info_text"
              :search-files="$page.props.search.other"
            />
          </NFormItemGi>
          <NFormItemGi :span="2">
            <template #label>
              <div class="flex gap-1">
                <span>Papildoma nuotrauka</span>
              </div></template
            >
            <UploadImageButtons
              v-model="form.extra_attributes.additional_photo"
              :path="'contacts'"
            />
          </NFormItemGi>
          <NFormItemGi :span="2">
            <template #label>
              <div class="flex gap-1">
                <span>
                  <strong>Negiminizuoti</strong> kontakto pareigos galūnės
                </span>
                <HelpTextHover
                  >Išjungia automatinį šios kontakto pareigybės giminizavimą
                  pagal vardą ir pavardę. Bus naudojamas originalus pareigybės
                  pavadinimas.
                </HelpTextHover>
              </div>
            </template>
            <NSwitch
              v-model:value="form.extra_attributes.use_original_duty_name"
            ></NSwitch>
          </NFormItemGi>
        </NGrid>
      </NTabPane>
      <NTabPane display-directive="show" name="en" tab="🇬🇧">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi label="Studijų programa" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.study_program"
              type="text"
              placeholder="Įrašyti tekstą..."
            />
          </NFormItemGi>
          <NFormItemGi label="Aprašymas" :span="6">
            <TipTap
              v-model="form.extra_attributes.en.info_text"
              :search-files="$page.props.search.other"
            />
          </NFormItemGi>
        </NGrid>
      </NTabPane>
    </NTabs>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      />
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSwitch,
  NTabPane,
  NTabs,
} from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import UploadImageButtons from "@/Components/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  dutiable: App.Models.MainPage;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("dutiable", props.dutiable);

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
