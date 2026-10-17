<template>
  <article
    class="group relative flex h-full min-w-0 flex-col border border-border bg-card p-5 transition-colors duration-200 hover:border-brand"
    data-slot="student-rep-institution-card"
  >
    <InstitutionCardHeading :eyebrow :href="url" :title="String(institution.name || '')" />

    <InstitutionCardDescription :text="description" />

    <div class="mt-5 border-t border-border pt-4">
      <div class="flex items-baseline justify-between gap-3">
        <p class="text-[0.625rem] font-bold uppercase tracking-[0.18em] text-muted-foreground">
          {{ $t('Atstovai') }}
        </p>
        <span v-if="representatives.length > 0" class="font-mono text-xs text-muted-foreground">
          {{ representatives.length }}
        </span>
      </div>

      <ul v-if="representatives.length > 0" class="mt-3 flex flex-col gap-2.5">
        <li
          v-for="person in shownRepresentatives"
          :key="person.id"
          class="flex min-w-0 items-center gap-3"
        >
          <span class="relative size-9 shrink-0 overflow-hidden border border-border/60 bg-secondary">
            <img
              v-if="person.photo"
              :src="person.photo"
              :alt="person.name"
              class="size-full object-cover"
              :style="{ objectPosition: person.focalPoint }"
              loading="lazy"
            >
            <span
              v-else
              class="flex size-full items-center justify-center font-mono text-xs font-bold text-muted-foreground"
              aria-hidden="true"
            >
              {{ initials(person.name) }}
            </span>
          </span>
          <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-bold text-foreground">{{ person.name }}</span>
            <a
              v-if="person.email"
              :href="`mailto:${person.email}`"
              class="relative z-10 block truncate text-xs text-brand transition-colors hover:text-foreground"
            >
              {{ person.email }}
            </a>
          </span>
        </li>
      </ul>

      <p v-else class="mt-3 text-xs text-muted-foreground">
        {{ $t('Šiuo metu kontaktų nėra') }}
      </p>
    </div>

    <div class="mt-4 flex items-center justify-between gap-3 border-t border-border pt-4">
      <span class="text-xs font-bold text-brand">
        <template v-if="hiddenCount > 0">{{ $tChoice('search.more_representatives', hiddenCount) }}</template>
      </span>
      <InstitutionCardArrow />
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';

import InstitutionCardArrow from './InstitutionCardArrow.vue';
import InstitutionCardDescription from './InstitutionCardDescription.vue';
import InstitutionCardHeading from './InstitutionCardHeading.vue';
import { type InstitutionCardData, useInstitutionCard } from './useInstitutionCard';

/** A search hit flattens every current holder into `contacts`; an entity nests them in duties. */
interface ContactHit {
  id: string;
  name: string;
  email?: string | null;
  profile_photo_path?: string | null;
  profile_photo_focal_point?: string | null;
  additional_photo?: string | null;
  additional_photo_focal_point?: string | null;
}

interface Representative {
  id: string;
  name: string;
  email: string | null;
  photo: string | null;
  focalPoint: string;
}

const SHOWN_REPRESENTATIVES = 3;

const props = defineProps<{
  institution: (InstitutionCardData & { contacts?: ContactHit[]; duties?: App.Entities.Duty[] }) | App.Entities.Institution;
  href?: string | null;
  /** Types the page already names; left out of the card's label. */
  omitTypeSlugs?: string[];
}>();

const { url, eyebrow, description } = useInstitutionCard(
  () => props.institution as InstitutionCardData,
  () => props.href,
  () => props.omitTypeSlugs,
);

const representatives = computed<Representative[]>(() => {
  const { contacts, duties } = props.institution as { contacts?: ContactHit[]; duties?: App.Entities.Duty[] };

  const people: Representative[] = contacts?.length
    ? contacts.map(contact => ({
        id: String(contact.id),
        name: contact.name,
        email: contact.email ?? null,
        photo: contact.additional_photo || contact.profile_photo_path || null,
        focalPoint: contact.additional_photo_focal_point || contact.profile_photo_focal_point || '50% 30%',
      }))
    : (duties ?? []).flatMap(duty => (duty.current_users ?? []).map((user) => {
        const pivot = user.pivot as { additional_photo?: string | null; additional_photo_focal_point?: string | null; additional_email?: string | null } | undefined;

        return {
          id: String(user.id),
          name: user.name,
          email: duty.email || pivot?.additional_email || user.email || null,
          photo: pivot?.additional_photo || user.profile_photo_path || null,
          focalPoint: pivot?.additional_photo_focal_point || user.profile_photo_focal_point || '50% 30%',
        };
      }));

  // One person can hold several duties in the same institution.
  return people.filter((person, index) => people.findIndex(other => other.id === person.id) === index);
});

const shownRepresentatives = computed(() => representatives.value.slice(0, SHOWN_REPRESENTATIVES));
const hiddenCount = computed(() => Math.max(0, representatives.value.length - SHOWN_REPRESENTATIVES));

const initials = (name: string) => {
  const parts = name.split(' ').filter(Boolean);
  return parts.length >= 2
    ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
    : (parts[0]?.substring(0, 2).toUpperCase() ?? '?');
};
</script>
