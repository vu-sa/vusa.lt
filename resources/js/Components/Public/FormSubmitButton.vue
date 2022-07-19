<template>
  <NButton @click="handleValidateClick">
    <slot>Pateikti</slot>
  </NButton>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NButton, createDiscreteApi } from "naive-ui";

const emit = defineEmits(["resetForm"]);
const { message } = createDiscreteApi(["message"]);

const props = defineProps({
  submitRoute: { type: String, required: true },
  formRef: { type: Object, required: true },
  formValue: { type: Object, required: true },
});

const handleValidateClick = (e) => {
  e.preventDefault();
  //   console.log(props.formRef, props.formRef.value, props.formValue);
  props.formRef?.validate((errors) => {
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
  });
};
</script>
