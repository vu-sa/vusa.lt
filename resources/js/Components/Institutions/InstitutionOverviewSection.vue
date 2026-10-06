<template>
  <div class="grid gap-10 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] xl:gap-16" data-slot="institution-overview">
    <div class="flex min-w-0 flex-col gap-10">
      <OverviewSection v-if="description" variant="home" :title="$t('Apie')" :icon="Info">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="prose prose-sm dark:prose-invert max-w-none" v-html="description" />
      </OverviewSection>

      <OverviewSection variant="home" :title="$t('Paskutiniai posėdžiai')" :icon="CalendarIcon">
        <template v-if="overview.recentMeetings.length > 0" #actions>
          <button
            type="button"
            class="inline-flex items-center gap-1 text-sm font-semibold text-brand transition-colors hover:text-foreground pointer-coarse:min-h-11"
            @click="$emit('navigate-tab', 'meetings')"
          >
            {{ $t('Visi posėdžiai') }}
            <ChevronRight class="size-4" aria-hidden="true" />
          </button>
        </template>

        <InstitutionMeetingsList
          v-if="overview.recentMeetings.length > 0"
          :meetings="recentMeetings"
          :institution-name="institution.name"
          @select="(meeting) => $emit('view-meeting', meeting)"
        />
        <EmptyState
          v-else-if="overview.meetings_hidden"
          :title="$t('Posėdžiai nėra vieši')"
          :description="hiddenMeetingsDescription"
          :icon="Lock"
          data-testid="institution-meetings-hidden"
        />
        <EmptyState
          v-else
          :title="$t('Nėra susitikimų')"
          :description="$t('Šiai institucijai dar nėra suplanuota susitikimų.')"
          :icon="CalendarIcon"
        />
      </OverviewSection>
    </div>

    <!-- Nominated for the current term (O22). Distinct from the body's members: a secretary
         need not hold a duty here at all. -->
    <OverviewSection v-if="secretaries.length" variant="home" :title="$t('secretaries.label')" :icon="UserCheck">
      <UsersAvatarGroup :users="(secretaries as unknown as App.Entities.User[])" :max="5" size="xxs" />
    </OverviewSection>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Calendar as CalendarIcon, ChevronRight, Info, Lock, UserCheck } from 'lucide-vue-next';

import InstitutionMeetingsList from './InstitutionMeetingsList.vue';

import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { EmptyState, OverviewSection } from '@/Components/Patterns';
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
const hiddenMeetingsDescription = computed(() => $t(
  'Šios institucijos posėdžiai nėra vieši, todėl jų čia nematai. Jei reikia informacijos apie posėdžius, susisiek su institucijos nariais ar koordinatoriais – jie nurodyti viršuje.',
));

const recentMeetings = computed(() => [...props.overview.recentMeetings]
  .sort((a, b) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime())
  .slice(0, 3));
</script>
