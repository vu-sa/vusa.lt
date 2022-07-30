<template>
  <div class="flex h-auto min-h-fit flex-col rounded-lg bg-white lg:flex-row">
    <div
      v-if="getImageUrl(contact)"
      :id="`contact-photo-${contact.id}`"
      class="relative h-60 w-auto flex-none lg:h-auto lg:w-40"
    >
      <NImage
        :src="getImageUrl(contact)"
        lazy
        :intersection-observer-options="{
          root: `#contact-photo-${contact.id}`,
        }"
        object-fit="cover"
        :show-toolbar="false"
        class="absolute inset-0 h-full w-full rounded-t-lg object-cover lg:rounded-t-none lg:rounded-l-lg"
        style="object-position: 50% 25%"
      />
    </div>
    <div class="flex flex-auto flex-col justify-between gap-4 p-4">
      <div class="flex flex-col flex-wrap">
        <h2 class="flex flex-auto items-center gap-2 px-2 text-slate-900">
          <span>{{ contact.name }}</span>
          <NButton
            v-if="$page.props.user"
            secondary
            circle
            size="tiny"
            @click="openEdit(contact)"
          >
            <NIcon>
              <PersonEdit24Regular />
            </NIcon>
          </NButton>
        </h2>
        <div class="w-fit p-2 text-sm font-medium text-gray-500">
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
            <p v-else class="my-1">
              {{ checkIfContactNameEndsWithEDot(contact, duty) }}
              {{ showStudyProgram(duty) }}
            </p>
          </template>
        </div>
      </div>
      <div class="flex flex-col gap-2 text-sm text-neutral-500">
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
          <div v-else>
            <NIcon class="mr-2"> <Mail20Regular /> </NIcon
            ><a :href="`mailto:${contact.email}`">{{ contact.email }}</a>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import { NButton, NIcon, NImage, NPopover } from "naive-ui";

import { PersonEdit24Regular } from "@vicons/fluent";
import route from "ziggy-js";

const props = defineProps<{
  contact: App.Models.User;
}>();

const openEdit = (contact: App.Models.User) => {
  window.open(route("users.edit", { user: contact.id }), "_blank");
};

// ! TIK KURATORIAMS: nusprendžia, kurią nuotrauką imti, pagal tai, ar url turi "kuratoriai"
const getImageUrl = (contact: App.Models.User) => {
  const url = new URL(window.location.href);
  if (url.pathname.includes("kuratoriai") && contact.duties) {
    // check all duties for duties name which includes kuratorius
    // iterate object simply because it may not be iterable
    for (const duty of Object.keys(contact.duties)) {
      if (contact.duties[duty].name.toLowerCase().includes("kuratorius")) {
        return (
          contact.duties[duty].pivot.attributes?.additional_photo ??
          contact.image
        );
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
    // replace duty.name ending 'ius' with 'ė', but only on end
    return duty.name.replace(/ius$/, "ė");
  }

  let firstName = contact.name.split(" ")[0];
  if (contact.name.endsWith("a") && !firstName.endsWith("s")) {
    return duty.name.replace(/ius$/, "ė");
  }

  let namesToWomanize = ["Katrin"];
  if (namesToWomanize.includes(firstName)) {
    return duty.name.replace(/ius$/, "ė");
  }

  return duty.name;
};

// ! TIK KURATORIAMS: nusprendžia, ar rodyti studijų programą
const showStudyProgram = (duty: App.Models.Duty) => {
  if (!duty.pivot?.attributes?.study_program) {
    return null;
  }

  // check if name includes kuratorius
  if (duty.type?.alias === "kuratoriai") {
    return `(${duty.pivot.attributes.study_program})`;
  }

  return null;
};
</script>
