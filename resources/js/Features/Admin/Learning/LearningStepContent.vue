<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, ArrowRight } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import type { LearningCoordinator, LearningInstitutionContacts, LearningStepData } from '@/Types/learning';
import { safeHref } from '@/Utils/safeHref';

import BrowserNotificationSetup from './BrowserNotificationSetup.vue';
import InstallationInstructions from './InstallationInstructions.vue';
import LearningCoordinators from './LearningCoordinators.vue';
import LearningStepProgress from './LearningStepProgress.vue';
import LearningAudienceNotes from './LearningAudienceNotes.vue';

withDefaults(defineProps<{
  step: LearningStepData;
  readonly?: boolean;
  detailsOnly?: boolean;
  coordinators?: LearningCoordinator[];
  institutionContacts?: LearningInstitutionContacts[];
}>(), { coordinators: () => [], institutionContacts: () => [] });
</script>

<template>
  <div class="space-y-4">
    <p v-if="!detailsOnly && step.description" class="max-w-prose whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ step.description }}</p>
    <LearningAudienceNotes v-if="!detailsOnly" :notes="step.audienceNotes" />
    <Button v-if="!detailsOnly && safeHref(step.source?.url)" as-child variant="default" voice="sentence" class="h-auto min-h-11 max-w-full whitespace-normal">
      <a :href="safeHref(step.source?.url)" target="_blank" rel="noopener noreferrer">
        {{ $t('learning.path.open') }}<ArrowUpRight class="size-4 shrink-0" aria-hidden="true" />
      </a>
    </Button>
    <p v-else-if="!detailsOnly && step.source?.type === 'document'" class="text-sm text-muted-foreground">{{ $t('learning.path.document_private') }}</p>
    <Button v-if="!detailsOnly && safeHref(step.actionUrl)" as-child variant="default" voice="sentence" class="h-auto min-h-11 max-w-full whitespace-normal">
      <Link :href="safeHref(step.actionUrl)!"><ArrowRight class="size-4" aria-hidden="true" />{{ $t(step.isSystem && step.key ? `learning.system_steps.${step.key}.action` : 'learning.path.open') }}</Link>
    </Button>

    <LearningCoordinators v-if="!detailsOnly && step.key === 'meet_coordinator'" :coordinators="coordinators" />
    <template v-if="step.key === 'meet_institution_contacts'">
      <div v-for="institution in institutionContacts" :key="institution.id" class="space-y-2">
        <h4 class="text-sm font-semibold">{{ institution.title }}</h4>
        <ul class="divide-y divide-border">
          <li v-for="(person, index) in institution.contacts" :key="index" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 py-2">
            <div class="min-w-0">
              <p class="text-sm font-semibold">{{ person.name }}</p>
              <p class="text-xs text-muted-foreground">{{ person.role }}</p>
            </div>
            <a v-if="person.email" :href="`mailto:${person.email}`" class="inline-flex min-h-11 max-w-full items-center break-all text-sm underline underline-offset-4">{{ person.email }}</a>
          </li>
        </ul>
        <p v-if="!institution.contacts.length" class="text-sm text-muted-foreground">{{ $t('learning.path.no_contacts') }}</p>
      </div>
    </template>
    <InstallationInstructions v-if="!readonly && step.key === 'install_app'" />
    <BrowserNotificationSetup v-if="!readonly && step.key === 'browser_push'" />
    <LearningStepProgress v-if="!detailsOnly" :step="step" :readonly="readonly" />
  </div>
</template>
