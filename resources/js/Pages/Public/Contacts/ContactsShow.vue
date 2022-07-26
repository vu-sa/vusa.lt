<template>
  <PublicLayout
    :title="`${institution.short_name ?? institution.name} kontaktai`"
  >
    <div class="px-16 lg:px-32">
      <div class="grid gap-8 pt-4 sm:grid-cols-2 xl:grid-cols-3">
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
          <h1>{{ institution.name ?? institution.short_name }}</h1>
          <div v-html="institution.description"></div>
        </div>
        <ContactWithPhoto
          v-for="contact in contacts"
          :key="contact.id"
          :contact="contact"
          :image-src="getImageUrl(contact)"
        >
          <template #name> {{ contact.name }} </template>
          <template #duty>
            <template v-for="duty in contact.duties" :key="duty.id">
              <NPopover
                v-if="duty.description"
                trigger="hover"
                :style="{ maxWidth: '250px' }"
                ><template #trigger>
                  <p class="my-1 cursor-pointer">
                    {{ checkIfContactNameEndsWithEDot(contact, duty) }}
                    {{ showStudyProgram(duty) }}
                  </p>
                </template>
                <span
                  v-html="duty.pivot?.attributes?.info_text ?? duty.description"
                ></span>
              </NPopover>
              <p v-else class="my-1">{{ duty.name }}</p>
            </template>
          </template>
          <template #contactInfo>
            <div v-if="contact.phone" class="flex flex-row items-center">
              <NIcon class="mr-2">
                <Phone20Regular />
              </NIcon>
              <a :href="`tel:${contact.phone}`">{{ contact.phone }}</a>
            </div>
            <template v-for="duty in contact.duties" :key="duty.id">
              <div v-if="duty.email" class="flex flex-row items-center">
                <NIcon class="mr-2"> <Mail20Regular /> </NIcon
                ><a :href="`mailto:${duty.email}`">{{ duty.email }}</a>
              </div>
            </template>
          </template>
        </ContactWithPhoto>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import { NIcon, NPopover } from "naive-ui";

import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

defineProps<{
  contacts: Array<App.Models.User>;
  institution: App.Models.DutyInstitution;
}>();

// ! TIK KURATORIAMS: nusprendžia, ar rodyti studijų programą
const showStudyProgram = (duty: App.Models.Duty) => {
  if (!duty.pivot?.attributes?.study_program) {
    return null;
  }

  // check if name includes kuratorius
  if (duty.name.toLowerCase().includes("kuratorius")) {
    return `(${duty.pivot.attributes.study_program})`;
  }

  return null;
};

// ! TIK KURATORIAMS: nusprendžia, kurią nuotrauką imti, pagal tai, ar url turi "kuratoriai"
const getImageUrl = (contact: App.Models.User) => {
  const url = new URL(window.location.href);
  url.search = "";
  if (url.pathname.includes("kuratoriai")) {
    // check all duties for duties name which includes kuratorius

    for (const duty of contact.duties) {
      if (duty.name.toLowerCase().includes("kuratorius")) {
        return duty.pivot.attributes?.additional_photo ?? contact.image;
      }
    }
  }
  return contact.image ?? "";
};

// ! TIK KURATORIAMS: pakeisti galūnes
// check
const checkIfContactNameEndsWithEDot = (
  contact: App.Models.User,
  duty: App.Models.Duty
) => {
  if (contact.name.endsWith("ė")) {
    return duty.name.replace("kuratorius", "kuratorė");
  }

  let firstName = contact.name.split(" ")[0];
  if (contact.name.endsWith("a") && !firstName.endsWith("s")) {
    return duty.name.replace("kuratorius", "kuratorė");
  }

  return duty.name;
};
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
