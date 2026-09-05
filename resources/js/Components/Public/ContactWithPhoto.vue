<template>
  <figure
    class="group relative flex flex-col overflow-hidden rounded-xl bg-gradient-to-br from-zinc-50 to-zinc-100/50 ring-1 ring-zinc-200/50 transition-all duration-300 hover:ring-zinc-300 hover:shadow-lg dark:from-zinc-800/80 dark:to-zinc-900 dark:ring-zinc-700/50 dark:hover:ring-zinc-600 sm:rounded-2xl">
    <!-- Photo section -->
    <div v-if="imageUrl" class="relative aspect-[4/3] w-full overflow-hidden">
      <img :src="imageUrl" :alt="contact?.name"
        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy"
        :style="{ objectPosition: focalPoint }">
      <!-- Subtle gradient overlay at bottom for text readability -->
      <div class="absolute inset-x-0 bottom-0 h-12 bg-gradient-to-t from-black/20 to-transparent" />
    </div>

    <!-- Avatar fallback when no photo -->
    <div v-else
      class="flex aspect-[4/3] w-full items-center justify-center bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-700 dark:to-zinc-800">
      <span class="text-2xl font-bold text-zinc-400 dark:text-zinc-500 sm:text-3xl">
        {{ getInitials(contact.name) }}
      </span>
    </div>

    <!-- Content section -->
    <div class="flex flex-1 flex-col justify-between gap-2 p-3 sm:gap-3 sm:p-4">
      <div>
        <!-- Name -->
        <h3 class="text-sm font-bold leading-tight text-zinc-900 dark:text-zinc-50 sm:text-base">
          {{ contact.name }}
          <span v-if="contact.show_pronouns" class="text-[0.625rem] font-normal text-zinc-400 sm:text-xs">
            ({{ contact.pronouns }})
          </span>
        </h3>

        <!-- Duties (hidden when the surrounding section already names the duty) -->
        <div v-if="duties && !hideDutyNames" class="mt-1.5 space-y-0.5 sm:mt-2 sm:space-y-1">
          <p v-for="duty in duties" :key="duty.id"
            class="flex items-center gap-0.5 text-[0.6875rem] leading-relaxed text-zinc-600 dark:text-zinc-400 sm:text-xs">
            <span class="min-w-0">
              {{ changeDutyNameEndings(contact, duty.name, $page.props.app.locale, contact.pronouns,
                duty.pivot?.use_original_duty_name) }}
              <span v-if="showAdditionalInfo(duty)" class="text-zinc-400 dark:text-zinc-500">
                {{ showAdditionalInfo(duty) }}
              </span>
            </span>
            <InfoPopover v-if="hasDutyDescription(duty)" compact style="max-width: 400px" trigger="hover" color="gray">
              <span v-html="dutyDescription(duty)" />
            </InfoPopover>
          </p>
        </div>

        <!-- Primary email (always visible when present) -->
        <div v-if="primaryEmail" class="mt-1.5 flex items-center gap-1.5 sm:mt-2">
          <IFluentMail20Regular class="size-3 shrink-0 text-zinc-500 dark:text-zinc-400 sm:size-3.5" />
          <a :href="`mailto:${primaryEmail.email}`"
            class="truncate text-[0.6875rem] text-zinc-600 hover:text-vusa-red dark:text-zinc-400 dark:hover:text-vusa-red sm:text-xs"
            :title="primaryEmail.email">
            {{ primaryEmail.email }}
          </a>
          <Popover v-if="shownContactEmail.length > 1">
            <PopoverTrigger as-child>
              <button type="button"
                class="inline-flex size-5 shrink-0 items-center justify-center rounded-full text-[0.625rem] font-medium text-zinc-500 transition-colors hover:bg-zinc-200/70 hover:text-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-700/70 dark:hover:text-zinc-200">
                +{{ shownContactEmail.length - 1 }}
              </button>
            </PopoverTrigger>
            <PopoverContent align="start" class="w-auto max-w-xs p-3">
              <div class="flex flex-col gap-2 text-sm">
                <template v-for="(email, index) in shownContactEmail" :key="email.email">
                  <div>
                    <span class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ email.name }}</span>
                    <a :href="`mailto:${email.email}`"
                      class="text-sm font-medium text-zinc-900 hover:text-vusa-red dark:text-zinc-100">
                      {{ email.email }}
                    </a>
                  </div>
                  <div v-if="index < shownContactEmail.length - 1" class="h-px bg-zinc-200 dark:bg-zinc-700" />
                </template>
              </div>
            </PopoverContent>
          </Popover>
        </div>
      </div>

      <!-- Action buttons -->
      <div v-if="contact.phone || contact.facebook_url" class="flex items-center gap-1 pt-0.5 sm:gap-1.5 sm:pt-1">
        <TooltipProvider v-if="contact.phone">
          <Tooltip>
            <TooltipTrigger as-child>
              <a :href="`tel:${contact.phone}`">
                <Button variant="ghost" size="icon-sm" :class="[
                  'size-7 rounded-full sm:size-8',
                  'bg-zinc-100 text-zinc-500 hover:bg-zinc-200 hover:text-zinc-700',
                  'dark:bg-zinc-700/60 dark:text-zinc-400 dark:hover:bg-zinc-600 dark:hover:text-zinc-200',
                ]">
                  <IFluentPhone20Regular class="size-3.5 sm:size-4" />
                </Button>
              </a>
            </TooltipTrigger>
            <TooltipContent side="bottom" class="px-3 py-1.5">
              <span class="text-xs">{{ contact.phone }}</span>
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>

        <a v-if="contact.facebook_url" :href="contact.facebook_url" target="_blank" rel="noopener noreferrer">
          <Button variant="ghost" size="icon-sm" :class="[
            'size-7 rounded-full sm:size-8',
            'bg-zinc-100 text-zinc-500 hover:bg-zinc-200 hover:text-zinc-700',
            'dark:bg-zinc-700/60 dark:text-zinc-400 dark:hover:bg-zinc-600 dark:hover:text-zinc-200',
          ]">
            <ISimpleIconsFacebook class="size-3.5 sm:size-4" />
          </Button>
        </a>
      </div>
    </div>
  </figure>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import InfoPopover from '../Buttons/InfoPopover.vue';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { changeDutyNameEndings } from '@/Utils/String';

const props = withDefaults(defineProps<{
  contact: App.Entities.User;
  duties: App.Entities.Duty[];
  /** Hide duty names — used when contacts are already sectioned by duty. */
  hideDutyNames?: boolean;
}>(), {
  hideDutyNames: false,
});

// Pivot-level description takes precedence over the duty-level one, matching the
// data model where a duty assigned to a specific user can override the generic text.
const dutyDescription = (duty: App.Entities.Duty) => {
  return duty.pivot?.description ?? duty.description;
};

// The popover should only appear when there is visible text content. Empty HTML
// wrappers such as "<p></p>" or "<p><br></p>" must not trigger it.
const hasDutyDescription = (duty: App.Entities.Duty): boolean => {
  const desc = dutyDescription(duty);
  if (!desc) {
    return false;
  }

  const container = document.createElement('div');
  container.innerHTML = desc;
  return (container.textContent ?? '').trim().length > 0;
};

// Some users have multiple duties, so we need to show all of their emails AND duty name
// alongside the email
const shownContactEmail = computed(() => {
  return props.duties.reduce((acc, duty) => {
    acc.push({ name: duty.name, email: duty.pivot?.additional_email ?? duty.email ?? props.contact.email });
    return acc;
  }, []);
});

// The first email is shown as a visible mailto link; the rest are reachable via the "+N" popover.
const primaryEmail = computed(() => shownContactEmail.value[0] ?? null);

// The note qualifies the programme rather than replacing it — several curators share one
// programme and are told apart only by their group.
const showAdditionalInfo = (duty) => {
  const parts = [duty.pivot?.study_program?.name, duty.pivot?.study_program_note].filter(Boolean);

  return parts.length > 0 ? `(${parts.join(', ')})` : null;
};

// Uses the first duty assignment that has a per-assignment photo (dutiable.additional_photo),
// falling back to the profile photo. Iterated via Object.keys since `duties` may not be
// array-like depending on how the contact was serialized.
const imageUrl = computed(() => {
  for (const duty of Object.keys(props.contact.duties)) {
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

  return props.contact.profile_photo_path ?? '';
});

const focalPoint = computed(() => {
  for (const duty of Object.keys(props.contact.duties ?? [])) {
    if (!props.contact.duties?.[duty].pivot) {
      continue;
    }

    return (
      props.contact.duties?.[duty].pivot.additional_photo_focal_point
      ?? props.contact.profile_photo_focal_point
      ?? '50% 30%'
    );
  }

  if (props.contact.pivot?.additional_photo_focal_point) {
    return props.contact.pivot.additional_photo_focal_point;
  }

  return props.contact.profile_photo_focal_point ?? '50% 30%';
});

// Get initials from name for avatar fallback
const getInitials = (name: string): string => {
  const parts = name.split(' ').filter(Boolean);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return parts[0]?.substring(0, 2).toUpperCase() ?? '?';
};

</script>
