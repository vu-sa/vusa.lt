<template>
  <NForm :model="form" label-placement="top">
    <NTabs animated type="card">
      <NTabPane display-directive="show" name="lt" tab="🇱🇹">
        <NGrid :span="24" :x-gap="24">
          <NFormItemGi label="Pavadinimas" :span="12">
            <NInput
              v-model:value="form.name"
              type="text"
              placeholder="Vilniaus universiteto Studentų atstovybė"
            />
          </NFormItemGi>

          <NFormItemGi label="Trumpas pavadinimas" :span="12">
            <NInput v-model:value="form.short_name" placeholder="VU SA" />
          </NFormItemGi>

          <NFormItemGi label="Techninė žymė" :span="12">
            <NInput
              v-model:value="form.alias"
              :disabled="modelRoute === 'dutyInstitutions.update'"
              type="text"
              placeholder="vu-sa"
            />
          </NFormItemGi>

          <NFormItemGi
            label="Padalinys, kuriam priklauso institucija"
            :span="12"
          >
            <NSelect
              v-model:value="form.padalinys_id"
              :options="options"
              placeholder="VU SA X"
              clearable
            />
          </NFormItemGi>

          <NFormItemGi label="Institucijos tipas" :span="12">
            <NSelect
              v-model:value="form.type_id"
              :options="dutyInstitutionTypes"
              label-field="name"
              value-field="id"
              placeholder="Studentų atstovų organas"
              clearable
            />
          </NFormItemGi>

          <NFormItemGi label="Nuotrauka" :span="24">
            <UploadImageButtons
              v-model="form.image_url"
              :path="'institutions'"
            />
          </NFormItemGi>

          <NFormItemGi label="Aprašymas" :span="24">
            <TipTap
              v-model="form.description"
              :search-files="$page.props.search.other"
            />
          </NFormItemGi>
        </NGrid>
      </NTabPane>
      <NTabPane display-directive="show" name="en" tab="🇬🇧">
        <NGrid :span="24" :x-gap="24">
          <NFormItemGi label="Pavadinimas" :span="12">
            <NInput
              v-model:value="form.attributes.en.name"
              type="text"
              placeholder="Vilnius University Students' Representation"
            />
          </NFormItemGi>

          <NFormItemGi label="Trumpas pavadinimas" :span="12">
            <NInput
              v-model:value="form.attributes.en.short_name"
              placeholder="VU SR"
            />
          </NFormItemGi>

          <NFormItemGi label="Techninė žymė" :span="12">
            <NInput
              v-model:value="form.attributes.en.alias"
              :disabled="modelRoute === 'dutyInstitutions.update'"
              type="text"
              placeholder=""
            />
          </NFormItemGi>

          <NFormItemGi label="Aprašymas" :span="24">
            <TipTap
              v-model="form.attributes.en.description"
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
      ></DeleteModelButton>
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
  NSelect,
  NTabPane,
  NTabs,
} from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap.vue";
import UploadImageButtons from "@/Components/Admin/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  dutyInstitution: App.Models.DutyInstitution;
  dutyInstitutionTypes: App.Models.DutyInstitutionType[];
  padaliniai: Array<App.Models.Padalinys>;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("dutyInstitution", props.dutyInstitution);

// if form.attributes.en is empty array then create empty object
if (form.attributes.en.length === 0) {
  form.attributes.en = {};
}

const options = props.padaliniai.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname,
}));
</script>
