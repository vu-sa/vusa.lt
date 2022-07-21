<template>
  <AdminLayout :title="calendar.title">
    <div class="main-card">
      <h3 class="mb-4">Bendra informacija</h3>
      <ul v-if="errors" class="mb-4 text-red-700">
        <li v-for="error in errors">{{ error }}</li>
      </ul>
      <form
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-4 grid-flow-row-dense"
      >
        <div class="lg:col-span-2">
          <label class="font-bold">Pavadinimas</label>
          <n-input
            v-model:value="calendar.title"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Data ir laikas</label>
          <n-date-picker
            v-model:formatted-value="calendar.date"
            placeholder="Įrašyti laiką..."
            value-format="yyyy-MM-dd HH:mm:ss"
            type="datetime"
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Kategorija</label>
          <n-select
            v-model:value="calendar.category"
            :options="options"
            placeholder="Įrašyti kategoriją..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Nuoroda</label>
          <n-input
            v-model:value="calendar.url"
            type="text"
            placeholder="Įrašyti nuorodą..."
          />
        </div>

        <div class="py-4 lg:col-span-4">
          <TipTap
            v-model="calendar.description"
            :search-files="$page.props.search.other"
          />
        </div>

        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <DeleteModelButton :model="calendar" model-route="calendar.destroy" />
          <UpsertModelButton
            :model="calendar"
            model-route="calendar.update"
          ></UpsertModelButton>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { NDatePicker, NInput, NSelect } from "naive-ui";
import { reactive } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps({
  calendar: Object,
  errors: Object,
});

const calendar = reactive(props.calendar);

const options = [
  {
    value: "red",
    label: "Akademinė informacija",
  },
  {
    value: "yellow",
    label: "Socialinė informacija",
  },
  {
    value: "grey",
    label: "Kita informacija",
  },
];
</script>
