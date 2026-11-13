<template>
  <ul :class="people.length > 1 && 'divide-y divide-border/60'" data-slot="people-contact-list">
    <li
      v-for="person in people"
      :key="person.id ?? person.name"
      :class="['flex flex-wrap items-center gap-x-4 gap-y-3', people.length > 1 && 'py-3']"
    >
      <div class="flex min-w-0 flex-1 basis-56 items-center gap-3">
        <UserAvatar :user="{ name: person.name, profile_photo_path: person.profile_photo_path }" :size="compact ? 32 : 40" />
        <div class="min-w-0">
          <p class="truncate font-bold text-foreground">
            {{ person.name }}
          </p>
          <slot name="details" :person />
        </div>
      </div>
      <Button v-if="person.email" as-child variant="outline" size="sm" voice="sentence" class="w-full sm:w-auto pointer-coarse:h-11">
        <a :href="`mailto:${person.email}`">
          <Mail aria-hidden="true" />
          {{ $t('Parašyti') }}
        </a>
      </Button>
    </li>
  </ul>
</template>

<script setup lang="ts" generic="T extends ContactPerson">
import { Mail } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';

import UserAvatar from './UserAvatar.vue';

export interface ContactPerson {
  id?: string | number;
  name: string;
  email?: string | null;
  profile_photo_path?: string | null;
}

/** People to reach: a face, a name, a line about why them, and "Parašyti" when there is an address. */
defineProps<{
  people: T[];
  /** A quieter size for screens where the people are a footnote, not a panel. */
  compact?: boolean;
}>();

defineSlots<{
  details?: (props: { person: T }) => unknown;
}>();
</script>
