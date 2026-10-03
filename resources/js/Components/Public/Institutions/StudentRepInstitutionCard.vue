<template>
  <article
    :class="[
      'group flex h-full flex-col border border-border bg-white/40',
      'transition-colors duration-200 hover:border-brand hover:bg-black/[0.03]',
      'dark:bg-black/40 dark:hover:bg-black/55',
    ]"
    data-slot="student-rep-institution-card"
  >
    <SmartLink :href="institutionUrl" class="plain flex flex-1 flex-col p-5 sm:p-6">
      <!-- Header: Institution Name -->
      <div>
        <h3 class="text-base sm:text-lg font-bold leading-snug text-foreground transition-colors group-hover:text-brand line-clamp-2">
          {{ institution.name }}
        </h3>
      </div>

      <div class="my-4 h-px w-full bg-border" />

      <!-- Team grid: up to 6 reps in a 3-column layout -->
      <div v-if="displayedContacts.length > 0" class="grid grid-cols-3 gap-3 sm:gap-4 flex-1 content-start">
        <div
          v-for="{ user, duty } in displayedContacts"
          :key="`rep-${user.id}-${duty.id}`"
          class="flex flex-col items-center text-center"
        >
          <!-- Square photo container with permanent grayscale -->
          <div class="relative aspect-square w-full overflow-hidden bg-secondary border border-border/60">
            <img
              v-if="getContactPhoto(user, duty)"
              :src="getContactPhoto(user, duty)"
              :alt="user.name"
              class="size-full object-cover grayscale group-hover:grayscale-0 transition duration-400 group-hover:scale-103"
              :style="{ objectPosition: getContactFocalPoint(user, duty) }"
              loading="lazy"
            >
            <div
              v-else
              class="size-full flex items-center justify-center bg-secondary text-muted-foreground font-mono text-xs sm:text-sm font-bold"
            >
              <span>{{ getInitials(user.name) }}</span>
            </div>
          </div>
          <!-- Rep name -->
          <p class="mt-1.5 text-center text-xs font-medium text-foreground line-clamp-2 leading-snug">
            {{ user.name }}
          </p>
        </div>
      </div>

      <div v-else class="flex flex-1 items-center justify-center py-6 text-center text-xs text-muted-foreground">
        {{ $t('Šiuo metu kontaktų nėra') }}
      </div>

      <!-- More contacts indicator -->
      <div
        v-if="remainingContactsCount > 0"
        class="mt-3 text-center text-xs font-mono text-muted-foreground"
      >
        +{{ remainingContactsCount }} {{ $t('daugiau') }}
      </div>

      <!-- Spacer -->
      <div class="flex-1 min-h-4" />

      <!-- Footer action row (following events card: borderless inline link) -->
      <div class="mt-4 border-t border-border pt-4 flex items-center justify-end">
        <span
          class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-foreground transition-colors group-hover:text-brand"
        >
          <span>{{ $t('Plačiau') }}</span>
          <IFluentArrowRight16Regular class="size-3.5 transition-transform duration-200 group-hover:translate-x-0.5" />
        </span>
      </div>
    </SmartLink>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { resolveTenantSubdomain } from '@/Composables/useTenantSubdomain';
import IFluentArrowRight16Regular from '~icons/fluent/arrow-right-16-regular';

const props = withDefaults(defineProps<{
  institution: App.Entities.Institution;
  href?: string | null;
}>(), {
  href: null,
});

const page = usePage();

// Build institution URL
const institutionUrl = computed(() => {
  if (props.href) {
    return props.href;
  }
  const locale = (page.props.app as { locale?: string } | undefined)?.locale || 'lt';
  return route('contacts.institution', {
    institution: props.institution.id,
    subdomain: resolveTenantSubdomain(props.institution.tenant?.id),
    lang: locale,
  });
});

interface ContactItem {
  id: string;
  name: string;
  duty_id?: string;
  duty_name?: string;
  profile_photo_path?: string | null;
  profile_photo_focal_point?: string | null;
  additional_photo?: string | null;
  additional_photo_focal_point?: string | null;
}

// Extract contacts with their duties for this institution
const contactsWithDuties = computed(() => {
  const inst = props.institution as unknown as { contacts?: ContactItem[]; duties?: App.Entities.Duty[] };
  if (inst.contacts && Array.isArray(inst.contacts) && inst.contacts.length > 0) {
    return inst.contacts.map(c => ({
      user: {
        id: c.id,
        name: c.name,
        profile_photo_path: c.profile_photo_path ?? null,
        profile_photo_focal_point: c.profile_photo_focal_point ?? null,
        pivot: {
          additional_photo: c.additional_photo ?? null,
          additional_photo_focal_point: c.additional_photo_focal_point ?? null,
        },
      } as unknown as App.Entities.User,
      duty: {
        id: c.duty_id || 'default',
        name: c.duty_name || '',
        pivot: {
          additional_photo: c.additional_photo ?? null,
          additional_photo_focal_point: c.additional_photo_focal_point ?? null,
        },
      } as unknown as App.Entities.Duty,
    }));
  }

  const result: Array<{ user: App.Entities.User; duty: App.Entities.Duty }> = [];
  props.institution.duties?.forEach((duty) => {
    duty.current_users?.forEach((user) => {
      result.push({ user, duty });
    });
  });
  return result;
});

const displayedContacts = computed(() => contactsWithDuties.value.slice(0, 6));
const remainingContactsCount = computed(() => Math.max(0, contactsWithDuties.value.length - 6));

// Get contact photo from user or duty pivot
const getContactPhoto = (user: App.Entities.User, duty: App.Entities.Duty) => {
  if (duty?.pivot?.additional_photo) return duty.pivot.additional_photo;
  if (user.pivot?.additional_photo) return user.pivot.additional_photo;
  return user.profile_photo_path ?? null;
};

// Get contact focal point from user or duty pivot
const getContactFocalPoint = (user: App.Entities.User, duty: App.Entities.Duty) => {
  if (duty?.pivot?.additional_photo_focal_point) return duty.pivot.additional_photo_focal_point;
  if (user.pivot?.additional_photo_focal_point) return user.pivot.additional_photo_focal_point;
  return user.profile_photo_focal_point ?? '50% 30%';
};

// Get initials from name
const getInitials = (name: string) => {
  const parts = name.split(' ').filter(Boolean);
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return parts[0]?.substring(0, 2).toUpperCase() ?? '?';
};
</script>
