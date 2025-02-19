<template>
  <NForm ref="formRef" :model="model" :rules="rules">
    <FadeTransition>
      <SuggestionAlert
        :show-alert="showAlert"
        @alert-closed="emit('alert-closed')"
      >
        <slot name="suggestion-content"></slot>
      </SuggestionAlert>
    </FadeTransition>
    <NGrid :cols="1">
      <NFormItemGi :label="$t('forms.fields.title')" required path="name">
        <NInput v-model:value="model.title" />
      </NFormItemGi>
      <NFormItemGi label="Preliminari data" required path="date">
        <NDatePicker
          v-model:value="model.date"
          :first-day-of-week="0"
          placeholder="Datą bus galima pakeisti!"
          type="date"
          clearable
        />
      </NFormItemGi>
    </NGrid>
    <NButton type="primary" @click="handleValidateForm"> Pradėti! </NButton>
  </NForm>
</template>

<script setup lang="tsx">
import {
  type FormInst,
  type FormRules,
  NButton,
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
} from "naive-ui";
import { ref } from "vue";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";

const emit = defineEmits<{
  (e: "submit:form", form: Record<string, any>): void;
  (e: "alert-closed"): void;
}>();

const props = defineProps<{
  formTemplate: Record<string, any>;
  showAlert: boolean;
}>();

const formRef = ref<FormInst | null>(null);

const model = ref(props.formTemplate);

const rules: FormRules = {
  title: [
    {
      required: true,
      message: "Pavadinimas yra privalomas",
      trigger: "blur-sm",
    },
  ],
  date: [
    {
      type: "number",
      required: true,
      message: "Data yra privaloma",
      trigger: "blur-sm",
    },
  ],
};

const handleValidateForm = () => {
  formRef.value?.validate((errors) => {
    if (!errors) {
      emit("submit:form", model.value);
    }
  });
};
</script>
