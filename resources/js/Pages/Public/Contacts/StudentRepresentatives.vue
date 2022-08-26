<template>
  <div class="flex flex-col px-8 pt-8 last:pb-2 lg:px-40">
    <p class="prose-sm prose mb-4 transition-colors">
      Studentų atstovus VU kamieniniuose akademiniuose padaliniuose galima rasti
      <Link href="/kontaktai/kategorija/padaliniai">čia</Link>.
    </p>
    <FadeTransition
      v-for="institution in institutions"
      :key="institution.id"
      appear
    >
      <div class="flex flex-col">
        <div class="flex flex-col gap-8 lg:flex-row">
          <div class="flex flex-col justify-center lg:w-1/2">
            <h2>{{ institution.name }}</h2>
            <div class="prose" v-html="institution.description"></div>
          </div>
          <div class="flex gap-4 lg:w-1/2">
            <template v-for="duty in institution.duties">
              <ContactWithPhotoForDuties
                v-for="contact in duty.users"
                :key="contact.id"
                :contact="contact"
                :index="`${institution.id}-${contact.id}`"
              >
              </ContactWithPhotoForDuties>
            </template>
          </div>
        </div>
        <NDivider></NDivider>
      </div>
    </FadeTransition>
  </div>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { NDivider } from "naive-ui";

import ContactWithPhotoForDuties from "@/Components/Public/ContactWithPhotoForDuties.vue";
import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";

defineProps<{
  institutions: App.Models.DutyInstitution[];
}>();
</script>
