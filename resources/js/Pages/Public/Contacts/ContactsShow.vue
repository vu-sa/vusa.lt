<template>
  <Head :title="`${institution.short_name ?? institution.name}`"></Head>
  <FadeTransition appear>
    <div class="px-16 lg:px-32">
      <div class="grid gap-8 pt-4 md:grid-cols-6">
        <div
          v-if="institution.image_url"
          class="group relative my-4 md:col-span-4"
        >
          <img
            :src="institution.image_url"
            class="h-64 w-full rounded-md object-cover duration-200 hover:opacity-90 lg:h-96"
            style="object-position: 0% 35%"
          />
        </div>
        <div
          :class="
            !institution.image_url
              ? 'md:col-span-3 2xl:col-span-2'
              : 'md:col-span-2'
          "
          class="prose prose-sm my-auto dark:prose-invert"
        >
          <h1>
            {{ institutionName }}
          </h1>
          <div v-html="institutionDescription"></div>
        </div>
        <!-- <template v-for="duty in institution"> -->
        <ContactWithPhotoForDuties
          v-for="(contact, index) in contacts"
          :key="contact.id"
          class="md:col-span-3 2xl:col-span-2"
          :contact="contact"
          :index="index"
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
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

const props = defineProps<{
  contacts: Array<App.Models.User>;
  institution: App.Models.Institution;
}>();

const institutionName = computed(() => {
  const locale = usePage().props.value.locale;

  if (locale === "en") {
    return (
      props.institution.extra_attributes?.en?.name ?? props.institution.name
    );
  }

  return props.institution.name ?? "";
});

const institutionDescription = computed(() => {
  const locale = usePage().props.value.locale;

  if (locale === "en") {
    return (
      props.institution.extra_attributes?.en?.description ??
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
