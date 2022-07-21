<template>
  <AdminLayout :title="navigation.name">
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
            v-model:value="navigation.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <!-- <div class="lg:col-span-2">
          <label class="font-bold">Trumpinys</label>
          <n-input
            disabled
            v-model:value="navigation.alias"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div> -->

        <div class="lg:col-span-4">
          <label class="font-bold">Nuoroda</label>
          <n-input
            v-model:value="navigation.url"
            type="text"
            placeholder="Įrašyti nuorodą..."
          />
        </div>

        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <UpsertModelButton
            :model="navigation"
            model-route="navigation.update"
          />
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { NInput } from "naive-ui";
import { reactive } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps({
  navigation: Object,
  errors: Object,
});

const navigation = reactive(props.navigation);
</script>
