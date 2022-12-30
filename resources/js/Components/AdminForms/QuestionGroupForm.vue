<template>
  <NForm ref="formRef" :model="model" :rules="rules">
    <NGrid cols="1">
      <NFormItemGi label="Pavadinimas" required path="title">
        <NInput v-model:value="model.title"></NInput>
      </NFormItemGi>
      <NFormItemGi :show-label="false"
        ><NButton type="primary" @click="handleClick"
          >Atnaujinti</NButton
        ></NFormItemGi
      >
    </NGrid>
  </NForm>
</template>

<script setup lang="tsx">
import { NButton, NForm, NFormItemGi, NGrid, NInput } from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

const emit = defineEmits(["formSuccess"]);

const props = defineProps<{
  questionGroup: App.Models.QuestionGroup;
}>();

const formRef = ref(null);
const model = useForm(props.questionGroup);
const rules = {
  title: {
    required: true,
    message: "Pavadinimas yra privalomas",
  },
};

const handleClick = () => {
  formRef.value?.validate((errors) => {
    if (!errors) {
      model.patch(route("questionGroups.update", props.questionGroup.id), {
        onSuccess: () => {
          emit("formSuccess");
        },
      });
    }
  });
};
</script>
