<template>
  <NForm :model="form" label-placement="top">
    <NTabs animated type="card" pane-class="overflow-x-auto">
      <NTabPane display-directive="show" name="lt" tab="🇱🇹">
        <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
          <NFormItemGi label="Pareigų pavadinimas" :span="2">
            <NInput
              v-model:value="form.name"
              type="text"
              placeholder="Prezidentė"
            />
          </NFormItemGi>

          <NFormItemGi label="Pareigybinis el. paštas" :span="2">
            <NInput v-model:value="form.email" placeholder="vusa@vusa.lt" />
          </NFormItemGi>

          <NFormItemGi label="Institucija" :span="4">
            <NSelect
              v-model:value="form.institution.id"
              filterable
              placeholder="Pasirink instituciją pagal pavadinimą..."
              :options="institutionsFromDatabase"
              clearable
            />
          </NFormItemGi>

          <NFormItemGi label="Pareigybės tipas" :span="2">
            <NSelect
              v-model:value="form.types"
              multiple
              :options="dutyTypes"
              label-field="title"
              value-field="id"
              placeholder="Pasirinkti kategoriją..."
              clearable
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
          <NFormItemGi label="Pareigų pavadinimas" :span="2">
            <NInput
              v-model:value="form.extra_attributes.en.name"
              type="text"
              placeholder="Prezidentė"
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
        :disabled="hasUsers"
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
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  duty: App.Models.Duty;
  dutyTypes: App.Models.Type[];
  institutions: App.Models.Institution[];
  hasUsers?: boolean;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("institution", props.duty);

console.log(form.types);

const institutionsFromDatabase = props.institutions.map((institution) => ({
  label: `${institution.name} (${institution.padalinys?.shortname})`,
  value: institution.id,
}));
</script>
