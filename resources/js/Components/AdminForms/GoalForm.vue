<template>
  <NForm ref="formRef" :model="model" :rules="rules">
    <NGrid cols="1">
      <NFormItemGi required path="title">
        <template #label>
          <span class="inline-flex items-center gap-1"
            ><NIcon :component="Icons.TITLE" />Pavadinimas</span
          >
        </template>
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
import { NButton, NForm, NFormItemGi, NGrid, NIcon, NInput } from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import Icons from "@/Types/icons/filled";

const emit = defineEmits(["formSuccess"]);

const props = defineProps<{
  goal: App.Entities.Goal;
}>();

const formRef = ref(null);
const model = useForm(props.goal);
const rules = {
  title: {
    required: true,
    message: "Pavadinimas yra privalomas",
  },
};

const handleClick = () => {
  formRef.value?.validate((errors) => {
    if (!errors) {
      model.patch(route("goals.update", props.goal.id), {
        onSuccess: () => {
          emit("formSuccess");
        },
      });
    }
  });
};
</script>
