<template>
  <NForm :model="reservationResourceForm">
    <NFormItem label="Pradžios laikas" path="start_time">
      <NDatePicker
        v-model:value="reservationResourceForm.start_time"
        placeholder="Pasirinkite pradžios laiką"
        clearable
      ></NDatePicker>
    </NFormItem>
    <NFormItem label="Pabaigos laikas" path="end_time">
      <NDatePicker
        v-model:value="reservationResourceForm.end_time"
        placeholder="Pasirinkite pabaigos laiką"
        clearable
      ></NDatePicker>
    </NFormItem>
    <NFormItem label="Išteklio pavadinimas" path="resource_id">
      <NSelect
        v-model:value="reservationResourceForm.resource_id"
        placeholder="Pasirinkite išteklio pavadinimą"
        clearable
      >
      </NSelect>
    </NFormItem>
    <NFormItem label="Kiekis" path="quantity">
      <NInputNumber
        v-model:value="reservationResourceForm.quantity"
        :disabled="reservationResourceForm.resource_id === null"
        min="1"
        placeholder="Įveskite išteklio kiekį"
      ></NInputNumber>
    </NFormItem>
    <NFormItem :show-label="false">
      <NButton
        :disabled="reservationResourceForm.resource_id === null"
        type="primary"
        @click="handleSubmit"
        >Sukurti</NButton
      >
    </NFormItem>
  </NForm>
</template>

<script setup lang="tsx">
import {
  NButton,
  NDatePicker,
  NForm,
  NFormItem,
  NInputNumber,
  NSelect,
} from "naive-ui";
import { ref } from "vue";
import type { InertiaForm } from "@inertiajs/vue3";

const props = defineProps<{
  reservationResourceForm: InertiaForm<{
    resource_id: number | null;
    reservation_id: number;
    quantity: number;
    start_time: string | null;
    end_time: string | null;
  }>;
}>();

const reservationResourceForm = ref(props.reservationResourceForm);
</script>
