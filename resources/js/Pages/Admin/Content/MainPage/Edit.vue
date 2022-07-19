<template>
  <AdminLayout :title="mainPage.text">
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
            v-model:value="mainPage.text"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Trumpinys</label>
          <n-input
            v-model:value="mainPage.alias"
            disabled
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-4">
          <label class="font-bold">Nuoroda</label>
          <n-input
            v-model:value="mainPage.link"
            type="text"
            placeholder="Įrašyti nuorodą..."
          />
        </div>

        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <UpsertModelButton :model="mainPage" model-route="mainPages.update" />
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { NInput } from "naive-ui";
import { reactive } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import UpsertModelButton from "@/Components/Admin/UpsertModelButton.vue";

const props = defineProps({
  mainPage: Object,
  errors: Object,
});

const mainPage = reactive(props.mainPage);
</script>
