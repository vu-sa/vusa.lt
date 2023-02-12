<template>
  <figure
    class="relative flex h-auto min-h-fit max-w-md flex-col rounded-sm border dark:border-zinc-700 lg:flex-row"
  >
    <div
      v-if="getImageUrl(contact)"
      class="relative h-48 w-full lg:h-auto lg:w-48"
    >
      <img
        :src="getImageUrl(contact)"
        class="h-full w-full object-cover"
        loading="lazy"
        style="object-position: 50% 25%"
        :alt="contact.name"
      />
    </div>
    <div class="flex flex-col justify-between gap-4 p-4">
      <div>
        <div class="flex items-center gap-2">
          <h2
            class="text-lg leading-6 tracking-tight text-zinc-800 dark:text-zinc-50"
          >
            {{ contact.name }}
          </h2>
        </div>
        <div
          v-if="contact.duties"
          class="w-fit text-xs font-medium text-zinc-600 dark:text-zinc-200"
        >
          <template v-for="duty in contact.duties" :key="duty.id">
            <p class="my-1">
              {{ changeDutyNameEndings(contact, duty) }}
              {{ showAdditionalInfo(duty) }}
              <span v-if="duty.description" class="align-middle">
                <InfoPopover
                  style="max-width: 400px"
                  trigger="hover"
                  color="gray"
                >
                  <span v-html="dutyDescription(duty)"></span>
                </InfoPopover>
              </span>
            </p>
          </template>
        </div>
      </div>
      <div class="flex flex-col gap-2 text-xs text-zinc-600 dark:text-zinc-200">
        <p v-if="contact.phone" class="inline-flex items-center gap-2">
          <NIcon :component="Phone20Regular" />
          <a :href="`tel:${contact.phone}`">{{ contact.phone }}</a>
        </p>
        <template v-for="duty in contact.duties" :key="duty.id">
          <a v-if="duty.email" :href="`mailto:${duty.email}`">
            <NEllipsis style="max-width: 250px">
              <NIcon class="mr-2 align-middle" :component="Mail20Regular" />
              <span class="align-middle">
                {{ duty.email }}
              </span>
            </NEllipsis>
          </a>
          <a v-else :href="`mailto:${contact.email}`">
            <NEllipsis style="max-width: 250px">
              <NIcon class="mr-2 align-middle" :component="Mail20Regular" />
              <span class="align-middle">
                {{ contact.email }}
              </span>
            </NEllipsis>
          </a>
        </template>
      </div>
    </div>
    <Link
      class="absolute -top-2 -right-2"
      :href="route('users.edit', contact.id)"
    >
      <NButton
        v-if="$page.props.auth?.user"
        class="bg-zinc-100 shadow-sm dark:bg-zinc-800"
        circle
        size="tiny"
      >
        <template #icon>
          <NIcon>
            <PersonEdit24Regular />
          </NIcon>
        </template>
      </NButton>
    </Link>
  </figure>
</template>

<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import { NButton, NEllipsis, NIcon } from "naive-ui";
import { PersonEdit24Regular } from "@vicons/fluent";
import InfoPopover from "../Buttons/InfoPopover.vue";

defineProps<{
  contact: App.Entities.User;
}>();

const dutyDescription = (duty) => {
  const locale = usePage().props.app.locale;

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

  const locale = usePage().props.app.locale;

  if (locale === "en") {
    return duty.pivot.extra_attributes?.en?.study_program == null
      ? `(${duty.pivot.extra_attributes?.study_program})`
      : `(${duty.pivot.extra_attributes?.en?.study_program})`;
  }

  return `(${duty.pivot.extra_attributes?.study_program})`;
};

// ! TIK KURATORIAMS: nusprendžia, kurią nuotrauką imti, pagal tai, ar url turi "kuratoriai"
const getImageUrl = (contact: App.Entities.User) => {
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
  contact: App.Entities.User,
  duty: App.Entities.Duty
) => {
  // check for english locale and just return english
  let locale = usePage().props.app.locale;

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
