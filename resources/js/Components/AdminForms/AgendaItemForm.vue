<template>
  <NForm ref="form" :model :rules>
    <NFormItem label="Klausimo pavadinimas" path="title" required>
      <NInput v-model:value="model.title" placeholder="Studijų tinklelio peržiūra" />
    </NFormItem>
    <NFormItem label="Aprašymas" path="description">
      <NInput v-model:value="model.description" type="textarea"
        placeholder="Aprašykite klausimo kontekstą, svarstymą, pakomentuokite balsavimą..." />
    </NFormItem>
    <NFormItem :show-label="false">
      <Button @click="handleSubmit">
        Pateikti
      </Button>
    </NFormItem>
  </NForm>
</template>

<script setup lang="ts">
import {
  type FormInst,
  type FormRules,
  NForm,
  NInput,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";

// import { modelDefaults } from "@/Types/formOptions";

const emit = defineEmits<{
  (e: "submit", form: any): void;
}>();

const props = defineProps<{
  agendaItem: App.Entities.AgendaItem | Record<string, any>;
}>();

const form = ref<FormInst | null>(null);
const model = useForm(props.agendaItem);

const rules: FormRules = {
  title: {
    required: true,
    message: "Klausimo pavadinimas yra privalomas",
  },
  description: {
    required: false,
  },
};

const handleSubmit = () => {
  // validate form
  form.value?.validate((errors) => {
    if (!errors) {
      emit("submit", model);
    }
  });
};
</script>
