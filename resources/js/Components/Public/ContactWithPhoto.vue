<template>
  <figure
    class="relative grid min-h-fit max-w-md rounded-md border border-zinc-200 bg-zinc-50 shadow-sm duration-200 hover:shadow-md dark:border-zinc-900 dark:bg-zinc-800"
    :class="{
      'xl:grid-cols-[2fr,_3fr]': imageUrl,
    }"
  >
    <img
      v-if="imageUrl"
      :src="imageUrl"
      class="h-44 w-full rounded-t-md object-cover xl:rounded-l-md xl:rounded-tr-none"
      loading="lazy"
      style="object-position: 50% 25%"
      :alt="contact.name"
    />
    <div class="flex flex-col justify-between gap-4 p-4">
      <div>
        <div class="flex items-center">
          <p
            class="text-xl font-bold leading-5 text-zinc-800 dark:text-zinc-50"
          >
            {{ contact.name }}
          </p>
        </div>
        <div
          v-if="duties"
          class="mt-2 w-fit text-xs text-zinc-600 dark:text-zinc-200"
        >
          <template v-for="duty in duties" :key="duty.id">
            <p class="my-1">
              {{ changeDutyNameEndings(contact, duty) }}
              {{ showAdditionalInfo(duty) }}
              <span
                v-if="duty.description && duty.description !== '<p></p>'"
                class="align-middle"
              >
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
        <template v-for="duty in duties" :key="duty.id">
          <NEllipsis style="max-width: 250px">
            <a v-if="duty.email" :href="`mailto:${duty.email}`">
              <NIcon class="mr-2 align-middle" :component="Mail20Regular" />
              <span class="align-middle">
                {{ duty.email }}
              </span>
            </a>
            <a v-else :href="`mailto:${contact.email}`">
              <NIcon class="mr-2 align-middle" :component="Mail20Regular" />
              <span class="align-middle">
                {{ contact.email }}
              </span>
            </a>
          </NEllipsis>
        </template>
      </div>
    </div>
  </figure>
</template>

<script setup lang="ts">
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import { NEllipsis, NIcon } from "naive-ui";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import InfoPopover from "../Buttons/InfoPopover.vue";

const props = defineProps<{
  contact: App.Entities.User;
  duties: App.Entities.Duty[];
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
const imageUrl = computed(() => {
  const url = new URL(window.location.href);
  if (url.pathname.includes("kuratoriai") && contact.duties) {
    // check all duties for duties name which includes kuratorius
    // iterate object simply because it may not be iterable
    for (const duty of Object.keys(contact.duties)) {
      if (
        props.contact.duties?.[duty].name.toLowerCase().includes("kuratorius")
      ) {
        return (
          props.contact.duties?.[duty].pivot.extra_attributes
            ?.additional_photo ?? props.contact.profile_photo_path
        );
      }
    }
  }
  return props.contact.profile_photo_path ?? "";
});

const changeDutyNameEndings = (
  contact: App.Entities.User,
  duty: App.Entities.Duty,
) => {
  // check for english locale and just return english
  let locale = usePage().props.app.locale;

  if (locale === "en" && duty.extra_attributes?.en?.name) {
    return duty.extra_attributes?.en?.name;
  }

  // check if duty name should not be explicitly changed
  if (duty.pivot?.extra_attributes?.use_original_duty_name) return duty.name;

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
