<template>
  <NForm ref="formRef" :rules="rules" :model="doingForm">
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
      <!-- <NFormItemGi label="Statusas" path="status" required :span="2">
        <NRadioGroup v-model:value="doingForm.status">
          <NRadio
            v-for="status in doingStatusOptions"
            :key="status.value"
            :value="status.value"
            ><StatusTag :status="status.label"></StatusTag
          ></NRadio>
        </NRadioGroup>
      </NFormItemGi> -->

      <NFormItemGi :span="2" :show-label="false"
        ><NButton type="primary" @click="upsertDoing"
          >Sukurti</NButton
        ></NFormItemGi
      >
    </NGrid>
  </NForm>
</template>

<script setup lang="ts">
import { Method } from "@inertiajs/inertia";
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
import { useForm, usePage } from "@inertiajs/inertia-vue3";

import { doingOptions, doingStatusOptions } from "@/Composables/someTypes";
import StatusTag from "@/Components/Tags/StatusTag.vue";

const emit = defineEmits(["success"]);

const props = defineProps<{
  doing: App.Entities.Doing;
  doingTypes?: any;
  modelRoute: string;
  matter?: App.Entities.Matter;
  // This question form is from a quick action button, idk if it shouldn't be refactored
  matterForm?: Record<string, any>;
}>();

const showModal = ref(false);
const doingForm = useForm(props.doing);
const formRef = ref(null);

const rules = {
  title: {
    required: true,
    trigger: ["blur"],
  },
  date: {
    required: true,
    trigger: ["blur"],
    message: "Veiklos data yra privaloma",
  },
  type_id: {
    required: true,
    trigger: ["blur"],
  },
};

const upsertDoing = () => {
  formRef.value?.validate((errors) => {
    if (errors) {
      /* empty */
    } else {
      doingForm.transform((data) => ({
        ...data,
        question_id: props.question?.id,
        questionForm: props.questionForm,
        user_id: usePage().props.value.auth?.user.id,
      }));

      doingForm.submit(
        props.modelRoute === "doings.update" ? Method.PATCH : Method.POST,
        route(props.modelRoute),
        {
          onSuccess: () => {
            showModal.value = false;
            emit("success");
          },
        }
      );
    }
  });
};
</script>
