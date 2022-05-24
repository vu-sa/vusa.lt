<template>
  <PublicLayout>
    <div class="px-16 lg:px-32 grid grid-cols-1 lg:grid-cols-2 gap-4">
      <NButton
        v-for="institution in institutions"
        text
        class="relative min-h-[8em]"
        style="white-space: normal"
        @click="inertiaVisitOnClick(institution.alias)"
      >
        <div v-if="institution.image_url">
          <img
            class="rounded-lg w-full h-full object-cover ease-in-out grayscale hover:grayscale-0 duration-500 shadow-sm hover:shadow-lg"
            :src="institution.image_url"
          />
          <p
            class="absolute text-white font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center bottom-8"
            style="text-shadow: 2px 2px 2px #111111aa"
          >
            {{ institution.name }}
          </p>
        </div>
        <div v-else class="rounded-lg w-full">
          <p class="font-bold text-2xl sm:text-3xl md:text-4xl w-full text-center">
            {{ institution.name }}
          </p>
        </div>
      </NButton>
    </div>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";
import {
  NSelect,
  NCascader,
  NInput,
  NInputGroup,
  useMessage,
  NIcon,
  NTabs,
  NTabPane,
  NButton,
} from "naive-ui";
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  institutions: Array,
});

const inertiaVisitOnClick = (alias) => {
  Inertia.visit(
    route("main.contacts.alias", {
      alias: alias,
      lang: "lt",
    })
  );
};
</script>
