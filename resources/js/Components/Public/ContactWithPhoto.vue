<template>
  <figure
    class="grid rounded-lg border border-zinc-200/50 bg-zinc-50 shadow-xs duration-200 hover:shadow-md dark:border-zinc-900/60 dark:bg-zinc-800"
    :class="{
      'gap-2 xl:grid-cols-[2fr__3fr]': imageUrl,
    }">
    <img v-if="imageUrl" :src="imageUrl" class="h-40 w-full object-cover max-xl:rounded-t-md xl:rounded-l-md"
      loading="lazy" style="object-position: 50% 20%" :alt="contact?.name">
    <div class="flex flex-col justify-between gap-4 p-4">
      <div>
        <div class="flex items-center">
          <p class="text-lg font-bold leading-5 text-zinc-800 dark:text-zinc-50 xl:text-xl">
            {{ contact.name }}
            <template v-if="contact.show_pronouns">
              <span class="text-xs text-zinc-400 dark:text-zinc-400">
                ({{ contact.pronouns }})
              </span>
            </template>
          </p>
        </div>
        <div v-if="duties" class="w-fit text-xs text-zinc-600 dark:text-zinc-200">
          <template v-for="duty in duties" :key="duty.id">
            <p class="my-1">
              {{ changeDutyNameEndings(contact, duty.name, $page.props.app.locale, contact.pronouns,
                duty.pivot?.use_original_duty_name) }}
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
        <TooltipProvider v-if="contact.phone">
          <Tooltip>
            <TooltipTrigger as-child>
              <a :href="`tel:${contact.phone}`">
                <Button variant="ghost" size="icon-sm" class="rounded-full">
                  <IFluentPhone20Regular />
                </Button>
              </a>
            </TooltipTrigger>
            <TooltipContent>
              <CopyToClipboardButton size="small" circle text :text-to-copy="contact.phone"
                success-text="Tel. nr. nukopijuotas!" error-text="Nepavyko nukopijuoti tel. nr...">
                <div class="mt-1 inline-flex items-center gap-1 text-zinc-300 hover:text-vusa-red">
                  <IFluentPhone20Regular />
                  {{ contact.phone }}
                </div>
              </CopyToClipboardButton>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
        <a v-if="contact.facebook_url" :href="contact.facebook_url" target="_blank" rel="noopener noreferrer">
          <Button variant="ghost" size="icon-sm" class="rounded-full">
            <IMdiFacebook />
          </Button>
        </a>
        <NPopover v-if="shownContactEmail.length > 1" trigger="hover" placement="bottom-start">
          <template #trigger>
            <Button variant="ghost" size="icon-sm" class="rounded-full">
              <IFluentMail20Regular />
            </Button>
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
        <TooltipProvider v-else-if="shownContactEmail.length === 1">
          <Tooltip>
            <TooltipTrigger as-child>
              <a :key="shownContactEmail[0].email" :href="`mailto:${shownContactEmail[0].email}`">
                <Button variant="ghost" size="icon-sm" class="rounded-full">
                  <IFluentMail20Regular />
                </Button>
              </a>
            </TooltipTrigger>
            <TooltipContent>
              <CopyToClipboardButton size="small" circle text :text-to-copy="shownContactEmail[0].email"
                success-text="El. paštas nukopijuotas!" error-text="Nepavyko nukopijuoti el. pašto...">
                <div class="mt-1 inline-flex items-center text-zinc-300 hover:text-vusa-red">
                  <IFluentMail20Regular class="mr-2" />
                  {{ shownContactEmail[0].email }}
                </div>
              </CopyToClipboardButton>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </div>
    </div>
  </figure>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { changeDutyNameEndings } from "@/Utils/String";
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
      duty.pivot?.description ??
      duty.description
    );
  }

  return duty.pivot?.description ?? duty.description;
};

// Some users have multiple duties, so we need to show all of their emails AND duty name
// alongside the email
const shownContactEmail = computed(() => {
  return props.duties.reduce((acc, duty) => {
    acc.push({ name: duty.name, email: duty.pivot?.additional_email ?? duty.email ?? props.contact.email });
    return acc;
  }, []);
});

const showAdditionalInfo = (duty) => {
  if (!duty.pivot?.study_program) {
    return null;
  }

  return `(${duty.pivot.study_program?.name})`;
};

// NOTE: Nusprendžia, kurią nuotrauką imti, pagal tai, ar url turi "kuratoriai"
const imageUrl = computed(() => {
  // check all duties for duties name which includes kuratorius
  // iterate object simply because it may not be iterable

  for (const duty of Object.keys(props.contact.duties)) {

    // check if props.contact.duties?.[duty] has pivot
    if (!props.contact.duties?.[duty].pivot) {
      continue;
    }

    return (
      props.contact.duties?.[duty].pivot.additional_photo ?? props.contact.profile_photo_path
    );
  }

  if (props.contact.pivot?.additional_photo) {
    return props.contact.pivot?.additional_photo;
  }

  return props.contact.profile_photo_path ?? "";
});

</script>
