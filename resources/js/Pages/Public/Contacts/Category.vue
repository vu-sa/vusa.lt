<template>
  <PublicLayout>
    <div
      class="px-16 lg:px-32 grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-0 gap-y-4 lg:gap-8"
    >
      <button
        v-for="institution in institutions"
        :key="institution.id"
        class="min-h-[8em] relative"
        style="white-space: normal"
        @click="inertiaVisitOnClick(institution.alias)"
      >
        <template v-if="institution.image_url">
          <img
            class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg max-h-72"
            :src="institution.image_url"
          />
          <p
            class="absolute text-white font-bold text-3xl sm:text-4xl md:text-5xl w-full text-center bottom-8"
            style="text-shadow: 3px 3px 3px #111111bb"
          >
            {{ institution.name ?? institution.short_name }}
          </p>
        </template>
        <p
          v-else
          class="font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center hover:text-vusa-red duration-500"
        >
          {{ institution.name ?? institution.short_name }}
        </p>
      </button>
    </div>
  </PublicLayout>
</template>

<script setup>
import { Inertia } from "@inertiajs/inertia";
// import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
// import {
//   NButton,
//   NCascader,
//   NIcon,
//   NInput,
//   NInputGroup,
//   NSelect,
//   NTabPane,
//   NTabs,
//   createDiscreteApi,
// } from "naive-ui";
// import { ref } from "vue";
// import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import PublicLayout from "@/Layouts/PublicLayout.vue";
// import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

defineProps({
  institutions: Array,
});

const inertiaVisitOnClick = (alias) => {
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
