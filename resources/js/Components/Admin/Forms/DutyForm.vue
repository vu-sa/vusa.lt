<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Pareigų pavadinimas" :span="12">
        <NInput
          v-model:value="form.name"
          type="text"
          placeholder="Prezidentė"
        />
      </NFormItemGi>

      <NFormItemGi label="Pareigybinis el. paštas" :span="12">
        <NInput v-model:value="form.email" placeholder="vusa@vusa.lt" />
      </NFormItemGi>

      <NFormItemGi label="Institucija" :span="24">
        <NSelect
          v-model:value="form.institution.id"
          filterable
          placeholder="Ieškok institucijos pagal pavadinimą..."
          :options="institutionsFromDatabase"
          clearable
          remote
          :clear-filter-after-select="false"
          @search="getInstitutionOptions"
        />
      </NFormItemGi>

      <NFormItemGi label="Aprašymas" :span="24">
        <TipTap
          v-model="form.description"
          :search-files="$page.props.search.other"
        />
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end">
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

<script lang="ts">
const { message } = createDiscreteApi(["message"]);
</script>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import {
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSelect,
  createDiscreteApi,
} from "naive-ui";
import { debounce } from "lodash";
import { onMounted, ref } from "vue";
import { useForm, usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import TipTap from "@/components/TipTap.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  duty: App.Models.Duty;
  hasUsers: boolean;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("dutyInstitution", props.duty);

const institutionsFromDatabase = ref([]);

// TODO: label doesn't update after updateModel and refresh page...
if (props.modelRoute !== "duties.store") {
  institutionsFromDatabase.value = [
    {
      value: form.institution?.id,
      label: `${form.institution?.name} (${form.institution?.alias})`,
    },
  ];
}

const getInstitutionOptions = debounce((input) => {
  if (input.length > 2) {
    message.loading("Ieškoma...");
    Inertia.post(
      route("dutyInstitutions.search"),
      {
        data: {
          name: input,
        },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          institutionsFromDatabase.value =
            usePage().props.value.search.other.map(
              (institution: App.Models.DutyInstitution) => {
                return {
                  value: institution.id,
                  label: `${institution.name} (${institution.alias})`,
                };
              }
            );
        },
      }
    );
  }
}, 500);
</script>
