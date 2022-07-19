<template>
  <AdminLayout :title="role.name">
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
            v-model:value="role.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Trumpinys</label>
          <n-input
            v-model:value="role.alias"
            disabled
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-4">
          <label class="font-bold">Aprašymas</label>
          <n-input
            v-model:value="role.description"
            type="textarea"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <UpsertModelButton :model="role" model-route="roles.update" />
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { NInput } from "naive-ui";
import { reactive } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import UpsertModelButton from "@/Components/Admin/UpsertModelButton.vue";

const props = defineProps({
  role: Object,
  errors: Object,
});

const role = reactive(props.role);
</script>
