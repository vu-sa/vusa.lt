<template>
  <div class="space-y-8" data-slot="institution-overview">
    <section v-if="description" class="space-y-2">
      <h3 class="border-b border-border pb-2 text-base font-semibold text-foreground">
        {{ $t('Apie') }}
      </h3>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="prose prose-sm dark:prose-invert max-w-none" v-html="description" />
    </section>

    <section class="space-y-2">
      <div class="flex items-center justify-between border-b border-border pb-2">
        <h3 class="text-base font-semibold text-foreground">
          {{ $t('Paskutiniai susitikimai') }}
        </h3>
        <button
          v-if="overview.recentMeetings.length > 0"
          type="button"
          class="inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-foreground pointer-coarse:min-h-11"
          @click="$emit('navigate-tab', 'meetings')"
        >
          {{ $t('Visi susitikimai') }}
          <ChevronRight class="size-4" aria-hidden="true" />
        </button>
      </div>

      <InstitutionMeetingsList
        v-if="overview.recentMeetings.length > 0"
        :meetings="recentMeetings"
        :institution-name="institution.name"
        @select="(meeting) => $emit('view-meeting', meeting)"
      />
      <EmptyState
        v-else
        :title="$t('Nėra susitikimų')"
        :description="$t('Šiai institucijai dar nėra suplanuota susitikimų.')"
        :icon="CalendarIcon"
      />
    </section>

    <section v-if="secretaries.length" class="space-y-2">
      <!-- Nominated for the current term (O22). Distinct from the body's members: a secretary
           need not hold a duty here at all. -->
      <h3 class="border-b border-border pb-2 text-base font-semibold text-foreground">
        {{ $t('secretaries.label') }}
      </h3>
      <UsersAvatarGroup :users="(secretaries as unknown as App.Entities.User[])" :max="5" :size="32" />
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Calendar as CalendarIcon, ChevronRight } from 'lucide-vue-next';

import InstitutionMeetingsList from './InstitutionMeetingsList.vue';

import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { EmptyState } from '@/Components/Patterns';
import type { InstitutionOverviewData, InstitutionPageData, InstitutionPageMeeting } from '@/Types/InstitutionPage';

const props = defineProps<{
  institution: InstitutionPageData;
  overview: InstitutionOverviewData;
}>();

defineEmits<{
  'navigate-tab': [tab: string];
  'view-meeting': [meeting: InstitutionPageMeeting];
}>();

// Description (localized string via toArray())
const description = computed(() => {
  const value = props.institution.description;

  return typeof value === 'string' && value.trim() !== '' ? value : null;
});

const secretaries = computed(() => props.institution.secretaries ?? []);

const recentMeetings = computed(() => [...props.overview.recentMeetings]
  .sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())
  .slice(0, 3));
</script>
