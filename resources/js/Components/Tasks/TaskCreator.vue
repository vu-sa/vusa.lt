<template>
  <NPopover style="width: 300px" trigger="click">
    <template #trigger>
      <NButton size="small" round>+</NButton>
    </template>
    <!-- Form title -->
    <NForm ref="formRef" class="m-2" :model="model" :rules="rules" size="small">
      <NGrid cols="1">
        <NFormItemGi label="Pavadinimas" required path="name">
          <NInput v-model:value="model.name"></NInput>
        </NFormItemGi>
        <!-- <NFormItemGi label="Subjektas">
          <NInput></NInput>
        </NFormItemGi> -->
        <NFormItemGi label="Data" path="due_date">
          <NDatePicker
            v-model:value="model.due_date"
            size="small"
          ></NDatePicker>
        </NFormItemGi>
        <NFormItemGi :show-label="false" :show-feedback="false">
          <NButton type="primary" @click="submit">Sukurti</NButton>
        </NFormItemGi>
      </NGrid>
    </NForm>
  </NPopover>
</template>

<script setup lang="tsx">
import {
  FormInst,
  NButton,
  NDatePicker,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NPopover,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";


const model = useForm("task", {
  name: "",
  due_date: new Date().getTime(),
});

const rules = {
  name: {
    required: true,
    trigger: "blur",
    message: "Pavadinimas yra privalomas",
  },
  due_date: {
    required: true,
    trigger: "blur",
    type: "number",
    message: "Data yra privaloma",
  },
};

const formRef = ref<FormInst | null>(null);

const submit = () => {
  formRef.value?.validate((errors) => {
    if (!errors) {
      model.post(route("tasks.store"), {
        preserveScroll: true,
        onSuccess: () => {
          model.reset();
        },
      });
    }
  });
};
</script>
