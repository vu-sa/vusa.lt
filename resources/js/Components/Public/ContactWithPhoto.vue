<template>
  <figure
    class="grid rounded-lg border border-zinc-200/50 bg-zinc-50 shadow-sm duration-200 hover:shadow-md dark:border-zinc-900/60 dark:bg-zinc-800"
    :class="{
      'gap-2 xl:grid-cols-[2fr,_3fr]': imageUrl,
    }">
    <img v-if="imageUrl" :src="imageUrl"
      class="h-40 w-full object-cover max-xl:rounded-t-md xl:rounded-l-md" loading="lazy"
      style="object-position: 50% 20%" :alt="contact?.name">
    <div class="flex flex-col justify-between gap-4 p-4">
      <div>
        <div class="flex items-center">
          <p class="text-lg font-bold leading-5 text-zinc-800 dark:text-zinc-50 xl:text-xl">
            {{ contact.name }}
          </p>
        </div>
        <div v-if="duties" class="w-fit text-xs text-zinc-600 dark:text-zinc-200">
          <template v-for="duty in duties" :key="duty.id">
            <p class="my-1">
              {{ changeDutyNameEndings(contact, duty) }}
              {{ showAdditionalInfo(duty) }}
              <span v-if="duty.description && duty.description !== '<p></p>'" class="align-middle">
                <InfoPopover style="max-width: 400px" trigger="hover" color="gray">
                  <span v-html="dutyDescription(duty)" />
                </InfoPopover>
              </span>
            </p>
          </template>
        </div>
      </div>
      <div class="flex gap-2">
        <NTooltip v-if="contact.phone">
          <template #trigger>
            <a :href="`tel:${contact.phone}`">
              <NButton tertiary size="small" circle>
                <template #icon>
                  <IFluentPhone20Regular />
                </template>
              </NButton>
            </a>
          </template>
          <CopyToClipboardButton size="small" circle text :text-to-copy="contact.phone"
            success-text="Tel. nr. nukopijuotas!" error-text="Nepavyko nukopijuoti tel. nr...">
            <div class="mt-1 inline-flex items-center gap-1 text-zinc-300 hover:text-vusa-red">
              <IFluentPhone20Regular />
              {{ contact.phone }}
            </div>
          </CopyToClipboardButton>
        </NTooltip>
        <a v-if="contact.facebook_url" :href="contact.facebook_url" target="_blank" rel="noopener noreferrer">
          <NButton tertiary size="small" circle>
            <template #icon>
              <IMdiFacebook />
            </template>
          </NButton>
        </a>
        <NPopover v-if="shownContactEmail.length > 1" trigger="hover" placement="bottom-start">
          <template #trigger>
            <NButton tertiary size="small" circle>
              <template #icon>
                <IFluentMail20Regular />
              </template>
            </NButton>
          </template>
          <div class="p-2">
            <div class="flex flex-col text-sm">
              <template v-for="email in shownContactEmail" :key="email.email">
                <span class="font-bold text-zinc-800 dark:text-zinc-100">{{ email.name }}</span>
                <div class="inline-flex items-center">
                  <CopyToClipboardButton size="small" circle text :text-to-copy="email.email"
                    success-text="El. paštas nukopijuotas!" error-text="Nepavyko nukopijuoti el. pašto...">
                    <IFluentMail20Regular />
                    <span>{{ email.email }}</span>
                  </CopyToClipboardButton>
                </div>
                <hr class="my-3 border border-zinc-300 last:hidden dark:border-zinc-500">
              </template>
            </div>
          </div>
        </NPopover>
        <NTooltip v-else-if="shownContactEmail.length === 1">
          <template #trigger>
            <a :key="shownContactEmail[0].email" :href="`mailto:${shownContactEmail[0].email}`">
              <NButton tertiary size="small" circle>
                <template #icon>
                  <IFluentMail20Regular />
                </template>
              </NButton>
            </a>
          </template>
          <CopyToClipboardButton size="small" circle text :text-to-copy="shownContactEmail[0].email"
            success-text="El. paštas nukopijuotas!" error-text="Nepavyko nukopijuoti el. pašto...">
            <div class="mt-1 inline-flex items-center text-zinc-300 hover:text-vusa-red">
              <IFluentMail20Regular class="mr-2" />
              {{ shownContactEmail[0].email }}
            </div>
          </CopyToClipboardButton>
        </NTooltip>
      </div>
    </div>
  </figure>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import CopyToClipboardButton from "../Buttons/CopyToClipboardButton.vue";
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

const shownContactEmail = computed(() => {
  let dutiesHaveEmail = props.duties.some((duty) => duty.email);

  if (dutiesHaveEmail) {
    return props.duties.reduce((acc, duty) => {
      if (duty.email) {
        acc.push({ name: duty.name, email: duty.email });
      }
      return acc;
    }, []);
  } else {
    return [{ name: props.contact.name, email: props.contact.email }];
  }
});

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
  if (url.pathname.includes("kuratoriai") && props.contact.duties) {
    // check all duties for duties name which includes kuratorius
    // iterate object simply because it may not be iterable
    for (const duty of Object.keys(props.contact.duties)) {
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
