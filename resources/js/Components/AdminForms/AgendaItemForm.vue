<template>
  <NForm ref="form" :model="model" :rules="rules">
    <NGrid cols="1">
      <NFormItemGi label="Klausimo pavadinimas" path="title" required>
        <NInput
          v-model:value="model.title"
          placeholder="Studijų tinklelio peržiūra"
        ></NInput>
      </NFormItemGi>
      <!-- <NFormItemGi label="Aprašymas" path="description">
        <NInput
          v-model:value="agendaItemForm.description"
          type="textarea"
          placeholder="Aprašykite klausimo kontekstą, jeigu to reikia..."
        ></NInput>
      </NFormItemGi> -->
      <NFormItemGi :show-label="false"
        ><NButton type="primary" @click="handleSubmit"
          >Sukurti</NButton
        ></NFormItemGi
      >
    </NGrid>
  </NForm>
</template>

<script setup lang="tsx">
import {
  type FormInst,
  type FormRules,
  NButton,
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

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
