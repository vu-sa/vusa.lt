<template>
  <NButton type="primary" @click="handleValidateClick">
    <slot>Pateikti</slot>
  </NButton>
</template>

<script setup lang="ts">
import {
  FormInst,
  FormValidationError,
  NButton,
  createDiscreteApi,
} from "naive-ui";
import { Inertia } from "@inertiajs/inertia";
import route from "ziggy-js";
import type { Ref } from "vue";

const emit = defineEmits(["resetForm"]);
const { message } = createDiscreteApi(["message"]);

const props = defineProps<{
  submitRoute: string;
  formRef: Ref<FormInst | null>;
  formValue: Record<string, string | Array<Record<string, any>> | true>;
}>();

const handleValidateClick = (e: MouseEvent) => {
  e.preventDefault();
  //   console.log(props.formRef, props.formRef.value, props.formValue);
  props.formRef.value?.validate(
    (errors: Array<FormValidationError> | undefined) => {
      if (!errors) {
        Inertia.post(route(props.submitRoute), props.formValue, {
          onSuccess: () => {
            message.success(
              `Ačiū už atsiskaitymo „${props.formValue.subject_name}“ užregistravimą!`
            );
            emit("resetForm");
          },
        });
      } else {
        //   console.log(errors);
        message.error("Užpildykite visus laukelius.");
      }
    }
  );
};
</script>
