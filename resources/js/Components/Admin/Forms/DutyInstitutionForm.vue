<template>
  <NForm :model="form" label-placement="top">
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

      <NFormItemGi label="Padalinys" :span="12">
        <NSelect
          v-model:value="form.padalinys_id"
          :options="options"
          placeholder="VU SA X"
          clearable
        />
      </NFormItemGi>

      <NFormItemGi label="Aprašymas" :span="24">
        <TipTap
          v-model="form.description"
          :search-files="$page.props.search.other"
        />
      </NFormItemGi>
    </NGrid>
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
import { NForm, NFormItemGi, NGrid, NInput, NSelect } from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  dutyInstitution: App.Models.DutyInstitution;
  padaliniai: Array<App.Models.Padalinys>;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("dutyInstitution", props.dutyInstitution);

const options = props.padaliniai.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname,
}));
</script>
