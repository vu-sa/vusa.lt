<template>
  <NForm :model="agendaItemForm">
    <NGrid cols="1">
      <NFormItemGi label="Klausimo pavadinimas" path="title" required>
        <NInput
          v-model:value="agendaItemForm.title"
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
import { NButton, NForm, NFormItemGi, NGrid, NInput } from "naive-ui";
import { useForm } from "@inertiajs/vue3";

// import { modelDefaults } from "@/Types/formOptions";

const emit = defineEmits<{
  (e: "submit", form: any): void;
}>();

const props = defineProps<{
  agendaItem: App.Entities.AgendaItem | Record<string, any>;
}>();

const agendaItemForm = useForm(props.agendaItem);

const handleSubmit = () => {
  // validate form
  emit("submit", agendaItemForm);
};
</script>
