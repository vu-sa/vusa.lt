<template>
  <NForm :model="form" label-placement="top">
    <NTabs animated type="card" pane-class="overflow-x-auto">
      <NTabPane display-directive="show" name="lt" tab="🇱🇹">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi label="Pavadinimas" :span="2">
            <NInput
              v-model:value="form.name"
              type="text"
              placeholder="Vilniaus universiteto Studentų atstovybė"
            />
          </NFormItemGi>

          <NFormItemGi label="Trumpas pavadinimas" :span="2">
            <NInput v-model:value="form.short_name" placeholder="VU SA" />
          </NFormItemGi>

          <NFormItemGi label="Techninė žymė" :span="2">
            <NInput
              v-model:value="form.alias"
              :disabled="modelRoute === 'institutions.update'"
              type="text"
              placeholder="vu-sa"
            />
          </NFormItemGi>

          <NFormItemGi
            label="Padalinys, kuriam priklauso institucija"
            :span="2"
          >
            <NSelect
              v-model:value="form.padalinys_id"
              :options="options"
              placeholder="VU SA X"
              clearable
            />
          </NFormItemGi>

          <NFormItemGi label="Institucijos tipas" :span="2">
            <NSelect
              v-model:value="form.types"
              :options="institutionTypes"
              label-field="title"
              value-field="id"
              placeholder="Studentų atstovų organas"
              clearable
            />
          </NFormItemGi>

          <NFormItemGi label="Nuotrauka" :span="6">
            <UploadImageButtons
              v-model="form.image_url"
              :path="'institutions'"
            />
          </NFormItemGi>

          <NFormItemGi label="Aprašymas" :span="6">
            <TipTap
              v-model="form.description"
              :search-files="$page.props.search.other"
            />
          </NFormItemGi>
        </NGrid>
      </NTabPane>
      <NTabPane display-directive="show" name="en" tab="🇬🇧">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi label="Pavadinimas" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.name"
              type="text"
              placeholder="Vilnius University Students' Representation"
            />
          </NFormItemGi>

          <NFormItemGi label="Trumpas pavadinimas" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.short_name"
              placeholder="VU SR"
            />
          </NFormItemGi>

          <NFormItemGi label="Techninė žymė" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.alias"
              :disabled="modelRoute === 'institutions.update'"
              type="text"
              placeholder=""
            />
          </NFormItemGi>

          <NFormItemGi label="Aprašymas" :span="6">
            <TipTap
              v-model="form.extra_attributes.en.description"
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

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UploadImageButtons from "@/Components/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  institution: App.Models.Institution;
  institutionTypes: App.Models.Type[];
  padaliniai: Array<App.Models.Padalinys>;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("institution", props.institution);

const options = props.padaliniai.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname,
}));
</script>
