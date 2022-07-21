<template>
  <AdminLayout :title="banner.title">
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
            v-model:value="banner.title"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Ar aktyvus?</label>
          <div>
            <NSwitch
              v-model:value="banner.is_active"
              :checked-value="1"
              :unchecked-value="0"
            />
          </div>
        </div>

        <div class="lg:col-span-4">
          <label class="font-bold">Nuoroda į puslapį</label>
          <n-input
            v-model:value="banner.link_url"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Logotipas</label>
          <UploadImage
            v-model="banner.image_url"
            :path="'banners'"
          ></UploadImage>
        </div>

        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <UpsertModelButton :model="banner" model-route="banners.update" />
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { NInput, NSwitch } from "naive-ui";
import { reactive } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import UploadImage from "@/Components/Admin/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps({
  banner: Object,
  errors: Object,
});

const banner = reactive(props.banner);
</script>
