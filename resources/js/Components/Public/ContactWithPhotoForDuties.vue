<template>
  <div
    class="flex h-auto min-h-fit max-w-xl flex-col rounded-lg bg-white dark:bg-zinc-700 lg:flex-row"
  >
    <div
      v-if="getImageUrl(contact)"
      :id="`contact-photo-${index}`"
      class="relative h-60 w-auto flex-none lg:h-auto lg:w-40"
    >
      <NImage
        :src="getImageUrl(contact)"
        lazy
        :intersection-observer-options="{
          root: `#contact-photo-${index}`,
        }"
        object-fit="cover"
        :show-toolbar="false"
        class="absolute inset-0 rounded-t-lg lg:rounded-t-none lg:rounded-l-lg"
        style="object-position: 50% 25%"
        :alt="contact.name"
      />
    </div>
    <div class="flex flex-auto flex-col justify-between gap-4 p-4">
      <div class="flex flex-col flex-wrap">
        <h2
          class="flex flex-auto items-center gap-2 px-2 text-gray-900 dark:text-zinc-50"
        >
          <span>{{ contact.name }}</span>
          <NButton
            v-if="$page.props.auth.user"
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
        <div
          v-if="contact.duties"
          class="w-fit p-2 text-sm font-medium text-gray-600 dark:text-zinc-200"
        >
          <template v-for="duty in contact.duties" :key="duty.id">
            <NPopover
              v-if="duty.description"
              trigger="hover"
              :style="{ maxWidth: '250px' }"
              ><template #trigger>
                <p class="my-1 cursor-pointer">
                  {{ changeDutyNameEndings(contact, duty) }}
                  {{ showAdditionalInfo(duty) }}
                </p>
              </template>
              <span v-html="dutyDescription(duty)"></span>
            </NPopover>
            <p v-else class="my-1">
              {{ changeDutyNameEndings(contact, duty) }}
              {{ showAdditionalInfo(duty) }}
            </p>
          </template>
        </div>
      </div>
      <div class="flex flex-col gap-2 text-sm text-gray-500 dark:text-zinc-200">
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
import { usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

defineProps<{
  contact: App.Models.User;
  index: string;
}>();

const openEdit = (contact: App.Models.User) => {
  window.open(route("users.edit", { user: contact.id }), "_blank");
};

const dutyDescription = (duty) => {
  const locale = usePage().props.value.locale;

  if (locale === "en") {
    return (
      duty.extra_attributes?.en?.description ??
      duty.pivot?.extra_attributes?.info_text ??
      duty.description
    );
  }

  return duty.pivot?.extra_attributes?.info_text ?? duty.description;
};

const showAdditionalInfo = (duty) => {
  if (!duty.pivot?.extra_attributes?.study_program) {
    return null;
  }

  const locale = usePage().props.value.locale;

  if (locale === "en") {
    return duty.pivot.extra_attributes?.en?.study_program == null
      ? `(${duty.pivot.extra_attributes?.study_program})`
      : `(${duty.pivot.extra_attributes?.en?.study_program})`;
  }

  return `(${duty.pivot.extra_attributes?.study_program})`;
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
          contact.duties[duty].pivot.extra_attributes?.additional_photo ??
          contact.profile_photo_path
        );
      }
    }
  }
  return contact.profile_photo_path ?? "";
};

const changeDutyNameEndings = (
  contact: App.Models.User,
  duty: App.Models.Duty
) => {
  // check for english locale and just return english
  let locale = usePage().props.value.locale;

  if (locale === "en" && duty.extra_attributes?.en?.name) {
    return duty.extra_attributes?.en?.name;
  }

  // check if duty name should not be explicitly changed
  if (duty.pivot.extra_attributes?.use_original_duty_name) return duty.name;

  // replace duty.name ending 'ius' with 'ė', but only on end of string
  let womanizedTitle = duty.name
    .replace(/ius$/, "ė")
    .replace(/as$/, "ė")
    .replace(/ys$/, "ė");
  let firstName = contact.name.split(" ")[0];

  let namesToWomanize = ["Katrin"];
  if (namesToWomanize.includes(firstName)) {
    return womanizedTitle;
  }

  let namesNotToWomanize = ["German"];
  if (namesNotToWomanize.includes(firstName)) {
    return duty.name;
  }

  if (contact.name.endsWith("ė") || firstName.endsWith("ė")) {
    return womanizedTitle;
  }

  // check for first name ending with 's'
  if (contact.name.endsWith("a") && !firstName.endsWith("s")) {
    return womanizedTitle;
  }

  return duty.name ?? "";
};
</script>
