<template>
  <article
    class="group relative flex flex-col bg-white/40 p-5 sm:p-6 transition-colors hover:bg-black/[0.03] dark:bg-black/40 dark:hover:bg-black/55"
  >
    <!-- Photo section (no avatars, no initials overlay) -->
    <div v-if="imageUrl" class="relative aspect-[4/3] w-full overflow-hidden bg-secondary">
      <img
        :src="imageUrl"
        :alt="contact?.name"
        class="size-full object-cover transition-transform duration-400 group-hover:scale-103"
        loading="lazy"
        :style="{ objectPosition: focalPoint }"
      >
    </div>
    <div v-else class="relative flex aspect-[4/3] w-full items-center justify-center bg-secondary/60 text-muted-foreground/40">
      <IFluentPerson24Regular class="size-12" />
    </div>

    <!-- Content section -->
    <div class="mt-5 flex flex-1 flex-col justify-between">
      <div>
        <!-- Name -->
        <h3 class="text-base sm:text-lg font-bold leading-tight text-foreground">
          {{ contact.name }}
          <span v-if="contact.show_pronouns" class="text-xs font-normal text-muted-foreground">
            ({{ contact.pronouns }})
          </span>
        </h3>

        <!-- Duties (hidden when the surrounding section already names the duty) -->
        <div v-if="duties && !hideDutyNames" class="mt-1.5 space-y-1">
          <p
            v-for="duty in duties"
            :key="duty.id"
            class="flex items-center gap-1 text-sm leading-relaxed text-muted-foreground"
          >
            <span class="min-w-0">
              {{ changeDutyNameEndings(contact, duty.name, $page.props.app.locale, contact.pronouns, duty.pivot?.use_original_duty_name) }}
              <span v-if="showAdditionalInfo(duty)" class="text-muted-foreground/70">
                {{ showAdditionalInfo(duty) }}
              </span>
            </span>
            <InfoPopover v-if="hasDutyDescription(duty)" compact style="max-width: 400px" trigger="hover" color="gray">
              <span v-html="dutyDescription(duty)" />
            </InfoPopover>
          </p>
        </div>
      </div>

      <!-- Main email in a separate line, below a separator, in brand color + action buttons -->
      <div v-if="primaryEmail || contact.phone || contact.facebook_url" class="mt-5 border-t border-border pt-3">
        <div class="flex items-center justify-between gap-2">
          <div v-if="primaryEmail" class="flex min-w-0 items-center gap-1.5">
            <a
              :href="`mailto:${primaryEmail.email}`"
              class="truncate text-sm font-bold text-brand transition-colors hover:text-foreground"
              :title="primaryEmail.email"
            >
              {{ primaryEmail.email }}
            </a>
            <Popover v-if="shownContactEmail.length > 1">
              <PopoverTrigger as-child>
                <button
                  type="button"
                  :class="[
                    'inline-flex size-5 shrink-0 items-center justify-center',
                    'border border-border bg-secondary text-[0.625rem] font-medium',
                    'text-muted-foreground transition-colors hover:text-foreground',
                  ]"
                >
                  +{{ shownContactEmail.length - 1 }}
                </button>
              </PopoverTrigger>
              <PopoverContent align="start" class="w-auto max-w-xs p-3">
                <div class="flex flex-col gap-2 text-sm">
                  <template v-for="(email, index) in shownContactEmail" :key="email.email">
                    <div>
                      <span class="block text-xs font-medium text-muted-foreground">{{ email.name }}</span>
                      <a
                        :href="`mailto:${email.email}`"
                        class="text-sm font-bold text-brand transition-colors hover:text-foreground"
                      >
                        {{ email.email }}
                      </a>
                    </div>
                    <div v-if="index < shownContactEmail.length - 1" class="h-px bg-border" />
                  </template>
                </div>
              </PopoverContent>
            </Popover>
          </div>

          <!-- Action buttons (Phone, Facebook) -->
          <div v-if="contact.phone || contact.facebook_url" class="flex items-center gap-1.5 shrink-0">
            <TooltipProvider v-if="contact.phone">
              <Tooltip>
                <TooltipTrigger as-child>
                  <a :href="`tel:${contact.phone}`" :aria-label="contact.phone">
                    <button
                      type="button"
                      :class="[
                        'inline-flex size-7 items-center justify-center border border-border',
                        'bg-background text-muted-foreground transition-colors hover:border-brand hover:text-brand',
                      ]"
                    >
                      <IFluentPhone20Regular class="size-3.5" />
                      <span class="sr-only">{{ contact.phone }}</span>
                    </button>
                  </a>
                </TooltipTrigger>
                <TooltipContent side="bottom" class="px-3 py-1.5">
                  <span class="text-xs">{{ contact.phone }}</span>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>

            <a
              v-if="contact.facebook_url"
              :href="contact.facebook_url"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="Facebook"
            >
              <button
                type="button"
                :class="[
                  'inline-flex size-7 items-center justify-center border border-border',
                  'bg-background text-muted-foreground transition-colors hover:border-brand hover:text-brand',
                ]"
              >
                <ISimpleIconsFacebook class="size-3.5" />
                <span class="sr-only">Facebook</span>
              </button>
            </a>
          </div>
        </div>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import InfoPopover from '../Buttons/InfoPopover.vue';

import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { changeDutyNameEndings } from '@/Utils/String';
import IFluentMail20Regular from '~icons/fluent/mail-20-regular';
import IFluentPhone20Regular from '~icons/fluent/phone-20-regular';
import IFluentPerson24Regular from '~icons/fluent/person-24-regular';
import ISimpleIconsFacebook from '~icons/simple-icons/facebook';

const props = defineProps<{
  contact: App.Entities.User;
  duties: App.Entities.Duty[];
  /** Hide duty names — used when contacts are already sectioned by duty. */
  hideDutyNames?: boolean;
}>();

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
  return props.duties.reduce<{ name: string; email: string }[]>((acc, duty) => {
    acc.push({ name: duty.name, email: duty.pivot?.additional_email ?? duty.email ?? props.contact.email });
    return acc;
  }, []);
});

// The first email is shown as a visible mailto link; the rest are reachable via the "+N" popover.
const primaryEmail = computed(() => shownContactEmail.value[0] ?? null);

// The note qualifies the programme rather than replacing it — several curators share one
// programme and are told apart only by their group.
const showAdditionalInfo = (duty: App.Entities.Duty) => {
  const parts = [duty.pivot?.study_program?.name, duty.pivot?.study_program_note].filter(Boolean);

  return parts.length > 0 ? `(${parts.join(', ')})` : null;
};

// Uses the first duty assignment that has a per-assignment photo (dutiable.additional_photo),
// falling back to the profile photo. Iterated via Object.keys since `duties` may not be
// array-like depending on how the contact was serialized.
const imageUrl = computed(() => {
  for (const duty of Object.keys(props.contact.duties ?? [])) {
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
</script>
