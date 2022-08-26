<template>
  <Head :title="`${institution.short_name ?? institution.name}`"></Head>
  <FadeTransition appear>
    <div class="px-16 lg:px-32">
      <div class="grid gap-8 pt-4 sm:grid-cols-2 2xl:grid-cols-3">
        <div v-if="institution.image_url" class="group relative sm:col-span-2">
          <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
          <ShapeDivider1
            class="absolute -bottom-1 z-10 rotate-180"
          ></ShapeDivider1>
          <img
            :src="institution.image_url"
            class="my-4 h-64 w-full object-cover duration-200 hover:opacity-90 lg:h-96"
            style="object-position: 0% 35%"
          />
        </div>
        <div
          :class="{ 'sm:row-span-2': !institution.image_url }"
          class="prose-sm my-auto sm:prose"
        >
          <h1>
            {{ dutyInstitutionName }}
          </h1>
          <div v-html="dutyInstitutionDescription"></div>
        </div>
        <!-- <template v-for="duty in institution"> -->
        <ContactWithPhotoForDuties
          v-for="contact in contacts"
          :key="contact.id"
          :contact="contact"
        >
        </ContactWithPhotoForDuties>
        <!-- </template> -->
      </div>
    </div>
  </FadeTransition>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { computed } from "vue";

import ContactWithPhotoForDuties from "@/Components/Public/ContactWithPhotoForDuties.vue";
import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

const props = defineProps<{
  contacts: Array<App.Models.User>;
  institution: App.Models.DutyInstitution;
}>();

const dutyInstitutionName = computed(() => {
  const locale = usePage().props.value.locale;

  if (locale === "en") {
    return props.institution.attributes?.en?.name ?? props.institution.name;
  }

  return props.institution.name ?? "";
});

const dutyInstitutionDescription = computed(() => {
  const locale = usePage().props.value.locale;

  if (locale === "en") {
    return (
      props.institution.attributes?.en?.description ??
      props.institution.description
    );
  }

  return props.institution.description ?? "";
});
</script>

<style>
.list-enter-active {
  transition: all 0.3s ease-out;
}

.list-leave-active {
  transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
