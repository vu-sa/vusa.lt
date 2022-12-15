<template>
  <NForm :model="form" label-placement="top">
    <NTabs animated type="card" pane-class="overflow-x-auto">
      <NTabPane display-directive="show" name="lt" tab="🇱🇹">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi :span="2">
            <template #label>
              <div class="flex gap-1">
                <span> Papildoma informacija </span>
                <HelpTextHover
                  >Rodoma pareigybės skliausteliuose
                </HelpTextHover>
              </div>
            </template>
            <NInput
              v-model:value="form.attributes.study_program"
              type="text"
              placeholder="Įrašyti tekstą..."
            />
          </NFormItemGi>
          <NFormItemGi label="Aprašymas" :span="6">
            <TipTap
              v-model="form.attributes.info_text"
              :search-files="$page.props.search.other"
            />
          </NFormItemGi>
          <NFormItemGi :span="2">
            <template #label>
              <div class="flex gap-1">
                <span>Papildoma nuotrauka</span
                ><HelpTextButton
                  >Ši nuotrauka bus rodoma „{{ dutyUser.duty.name }}“
                  pareigybei.</HelpTextButton
                >
              </div></template
            >
            <UploadImageButtons
              v-model="form.attributes.additional_photo"
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
              v-model:value="form.attributes.use_original_duty_name"
            ></NSwitch>
          </NFormItemGi>
        </NGrid>
      </NTabPane>
      <NTabPane display-directive="show" name="en" tab="🇬🇧">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi label="Studijų programa" :span="2">
            <NInput
              v-model:value="form.attributes.en.study_program"
              type="text"
              placeholder="Įrašyti tekstą..."
            />
          </NFormItemGi>
          <NFormItemGi label="Aprašymas" :span="6">
            <TipTap
              v-model="form.attributes.en.info_text"
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
import HelpTextButton from "@/Components/Buttons/HelperButtons/HelpTextButton.vue";
import UploadImageButtons from "@/Components/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  dutyUser: App.Models.MainPage;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("dutyUser", props.dutyUser);

if (!form.attributes || form.attributes.length === 0) {
  form.attributes = {};
}

if (!form.attributes.study_program) {
  form.attributes.study_program = "";
}

if (!form.attributes.info_text) {
  form.attributes.info_text = "";
}

if (!form.attributes.additional_photo) {
  form.attributes.additional_photo = "";
}

if (!form.attributes.en) {
  form.attributes.en = {};
}

// if form.attributes.en is empty array then create empty object
if (form.attributes.en.length === 0) {
  form.attributes.en = {};
}
</script>
