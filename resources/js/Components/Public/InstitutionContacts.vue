<template>
  <div class="grid grid-cols-[4fr,_3fr] gap-12">
    <div class="sticky top-32 h-fit">
      <img
        v-if="institution.image_url"
        :src="institution.image_url"
        :alt="institution.name ?? ''"
        class="mb-4 h-60 w-full object-cover shadow-lg"
        @error="imageError = true"
      />
      <section class="mb-4 ml-4">
        <div class="sticky top-0">
          <h1>
            <template v-if="$page.props.app.locale === 'en'">
              {{ institution.extra_attributes?.en?.name ?? institution.name }}
            </template>
            <template v-else>
              {{ institution.name ?? "" }}
            </template>
          </h1>
          <p
            class="prose prose-sm dark:prose-invert"
            v-html="institutionDescription"
          />
        </div>
      </section>
    </div>
    <section class="flex flex-col gap-6">
      <ContactCard
        v-for="contact in contacts"
        :key="contact.id"
        :contact="contact"
        :duties="contact.duties"
      >
      </ContactCard>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import ContactCard from "@/Components/Public/ContactWithPhoto.vue";

const props = defineProps<{
  contacts: Array<App.Entities.User>;
  institution: App.Entities.Institution;
}>();

const imageError = ref(false);

const institutionDescription = computed(() => {
  if (usePage().props.app.locale === "en") {
    return (
      props.institution.extra_attributes?.en?.description ??
      props.institution.description
    );
  }

  return props.institution.description ?? "";
});

console.log(props.contacts);
</script>
