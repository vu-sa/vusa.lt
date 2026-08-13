<template>
  <ShowPageLayout
    :title="trainingName"
    :subtitle="dateRange"
    :icon="TrainingIconFilled"
    :model="training"
    audit-subject-type="training"
    :tabs
    tab-storage-key="show-training-tab"
  >
    <template #badge>
      <Badge v-if="training.form === null" variant="warning">
        <MinusCircle class="mr-1 h-3 w-3" />
        {{ $t('Registracija nevykdoma') }}
      </Badge>
      <Badge v-else-if="!userCanRegister" variant="secondary">
        <Ban class="mr-1 h-3 w-3" />
        {{ $t('Negalite registruotis') }}
      </Badge>
      <Badge v-else-if="!userIsRegistered" variant="warning">
        <Circle class="mr-1 h-3 w-3" />
        {{ $t('Registracija vyksta') }}
      </Badge>
      <Badge v-else variant="success">
        <CheckCircle2 class="mr-1 h-3 w-3" />
        {{ $t('Užsiregistruota') }}
      </Badge>
    </template>

    <template #actions>
      <Link v-if="userCanRegister" :href="route('trainings.showRegistration', training.id)">
        <Button>{{ $t('Registruotis') }}</Button>
      </Link>
      <Button v-else-if="userIsRegistered" variant="warning" disabled>
        {{ $t('Atšaukti registraciją') }}
      </Button>
      <Button v-else disabled>
        {{ $t('Registruotis') }}
      </Button>
      <Button variant="secondary" size="icon">
        <Share2 class="h-4 w-4" />
      </Button>
    </template>

    <template #summary>
      <div class="space-y-6">
        <img
          v-if="training.image"
          :src="training.image"
          alt=""
          class="h-48 w-full rounded-lg object-cover"
        >

        <SectionCard v-if="training.description" :title="$t('Aprašymas')" :icon="AlignLeft">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="max-w-none" v-html="training.description" />
        </SectionCard>

        <SectionCard :title="$t('Pagrindinė informacija')" :icon="Info">
          <div class="flex flex-col gap-2">
            <div v-if="training.address" class="flex items-center gap-2">
              <MapPin class="h-4 w-4 shrink-0 text-muted-foreground" />
              <span class="font-bold">{{ training.address }}</span>
            </div>
            <div v-if="training.meeting_url" class="flex items-center gap-2">
              <Link2 class="h-4 w-4 shrink-0 text-muted-foreground" />
              <a :href="training.meeting_url" target="_blank" rel="noopener noreferrer" class="underline">
                {{ $t('Prisijungti nuotoliu') }}
              </a>
            </div>
            <div class="flex items-center gap-2">
              <InstitutionIconFilled class="h-4 w-4 shrink-0 text-muted-foreground" />
              <span>{{ training.institution?.name }}</span>
            </div>
            <div class="inline-flex items-center gap-2">
              <UserIconFilled class="h-4 w-4 shrink-0 text-muted-foreground" />
              {{ $t('Organizuoja') }}:
              <UserPopover show-name :size="20" :user="training.organizer" />
            </div>
            <div class="flex items-center gap-2">
              <Users class="h-4 w-4 shrink-0 text-muted-foreground" />
              <span>{{ $t('Užsiregistravusių') }}: {{ registeredUserCount }}</span>
            </div>
          </div>
        </SectionCard>
      </div>
    </template>

    <template #programme>
      <ProgrammePlanner show-times :programme="training.programmes?.at(0)" />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { AlignLeft, Ban, CheckCircle2, Circle, Info, Link2, MapPin, MinusCircle, Share2, Users } from 'lucide-vue-next';

import UserPopover from '@/Components/Avatars/UserPopover.vue';
import ShowPageLayout from '@/Components/Layouts/ShowPageLayout.vue';
import { SectionCard } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import ProgrammePlanner from '@/Features/Admin/ProgrammePlanner/ProgrammePlanner.vue';
import { formatStaticTime } from '@/Utils/IntlTime';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { InstitutionIconFilled, TrainingIconFilled, UserIconFilled } from '@/Components/icons';

const props = defineProps<{
  training: App.Entities.Training;
  registeredUserCount: number;
  userIsRegistered: boolean;
  userCanRegister: boolean;
}>();

/**
 * The controller serialises with `toArray()`, so translatable fields arrive as
 * localized strings — but the IDE-helper-generated type still calls them arrays.
 */
const trainingName = computed(() => props.training.name as unknown as string);

const dateRange = computed(() => {
  const options = { month: 'long', day: 'numeric' } as const;
  return `${formatStaticTime(props.training.start_time, options)} – ${formatStaticTime(props.training.end_time, options)}`;
});

const tabs = computed(() => [
  { value: 'summary', label: $t('Pagrindinis') },
  { value: 'programme', label: $t('Programa') },
]);

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    'Mokymai',
    'trainings.index',
    {},
    trainingName.value,
    TrainingIconFilled,
    TrainingIconFilled,
  ),
);
</script>
