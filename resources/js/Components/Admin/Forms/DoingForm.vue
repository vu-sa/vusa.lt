<template>
  <NForm :model="doingForm">
    <NGrid cols="2">
      <NFormItemGi label="Veiklos pavadinimas" path="title" required :span="2">
        <NSelect
          v-model:value="doingForm.title"
          placeholder="Susitikimas su studentais"
          filterable
          tag
          :options="doingOptions"
          ><template #action>
            <span
              class="prose-sm prose-gray text-xs text-zinc-400 dark:prose-invert"
              >Gali įrašyti ir savo veiklą...</span
            >
          </template></NSelect
        >
      </NFormItemGi>
      <NFormItemGi label="Data" :span="2" path="date" required>
        <NDatePicker
          v-model:formatted-value="doingForm.date"
          value-format="yyyy-MM-dd HH:mm:ss"
          :first-day-of-week="0"
          :format="'yyyy-MM-dd HH:mm:ss'"
          type="datetime"
          placeholder="Kada vyksta veikla?"
          clearable
          :actions="['confirm']"
        />
      </NFormItemGi>
      <NFormItemGi v-if="doingTypes" label="Tipas" path="doing_type_id" required
        ><NSelect
          v-model:value="doingForm.type_id"
          placeholder="Pasirinkti tipą"
          filterable
          :options="doingTypes"
        ></NSelect
      ></NFormItemGi>
      <NFormItemGi label="Statusas" path="status" required :span="2">
        <NRadioGroup v-model:value="doingForm.status">
          <NRadio
            v-for="status in doingStatuses"
            :key="status.value"
            :value="status.value"
            >{{ status.label }}</NRadio
          >
        </NRadioGroup>
      </NFormItemGi>

      <NFormItemGi :span="2" :show-label="false"
        ><NButton type="primary" @click="upsertDoing"
          >Sukurti</NButton
        ></NFormItemGi
      >
    </NGrid>
  </NForm>
</template>

<script setup lang="ts">
import {
  NButton,
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NRadio,
  NRadioGroup,
  NSelect,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import { doingOptions } from "@/Composables/someTypes";

const emit = defineEmits(["success"]);

const props = defineProps<{
  doing: any;
  doingTypes?: any;
  modelRoute: string;
  question: any;
}>();

const showModal = ref(false);
const doingForm = useForm(props.doing);

const doingStatuses = [
  {
    label: "Sukurtas",
    value: "Sukurtas",
  },
  {
    label: "Pabaigtas",
    value: "Pabaigtas",
  },
];

const upsertDoing = () => {
  console.log(doingForm);
  doingForm.transform((data) => ({
    ...data,
    question_id: props.question.id,
  }));
  if (props.modelRoute == "doings.update") {
    doingForm.patch(
      route(props.modelRoute, {
        question_id: parseInt(props.question.id),
        doing: props.doing.id,
      }),
      {
        onSuccess: () => {
          showModal.value = false;
          emit("success");
        },
      }
    );
  } else {
    doingForm.post(
      route(props.modelRoute, {
        question_id: parseInt(props.question.id),
      }),
      {
        onSuccess: () => {
          showModal.value = false;
          emit("success");
        },
      }
    );
  }
};
</script>
