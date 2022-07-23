<template>
  <PublicLayout>
    <div
      class="grid grid-cols-1 gap-0 gap-y-4 px-16 lg:grid-cols-2 lg:gap-8 lg:px-32 xl:grid-cols-3"
    >
      <button
        v-for="institution in institutions"
        :key="institution.id"
        class="relative min-h-[8em]"
        style="white-space: normal"
        @click="inertiaVisitOnClick(institution.alias)"
      >
        <template v-if="institution.image_url">
          <img
            class="h-full max-h-72 w-full rounded-lg object-cover shadow-sm grayscale duration-500 ease-in-out hover:shadow-lg hover:grayscale-0"
            :src="institution.image_url"
          />
          <p
            class="absolute bottom-8 w-full text-center text-3xl font-bold text-white sm:text-4xl md:text-5xl"
            style="text-shadow: 3px 3px 3px #111111bb"
          >
            {{ institution.name ?? institution.short_name }}
          </p>
        </template>
        <p
          v-else
          class="w-full text-center text-2xl font-bold duration-500 hover:text-vusa-red sm:text-3xl md:text-4xl"
        >
          {{ institution.name ?? institution.short_name }}
        </p>
      </button>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import route from "ziggy-js";

import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

defineProps<{
  institutions: App.Models.DutyInstitution[];
}>();

const inertiaVisitOnClick = (alias: string) => {
  Inertia.visit(
    route("main.contacts.alias", {
      alias: alias,
      lang: "lt",
    }),
    {},
    {
      preserveScroll: false,
    }
  );
};
</script>
