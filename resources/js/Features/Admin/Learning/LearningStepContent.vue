<script setup lang="ts">
import WikiProse from '@/Features/Admin/Knowledge/WikiProse.vue';
import type { LearningCoordinator, LearningInstitutionContacts, LearningStepData, WikiPageSummary } from '@/Types/learning';

import BrowserNotificationSetup from './BrowserNotificationSetup.vue';
import InstallationInstructions from './InstallationInstructions.vue';
import LearningCoordinators from './LearningCoordinators.vue';
import LearningAudienceNotes from './LearningAudienceNotes.vue';

withDefaults(defineProps<{
  step: LearningStepData;
  readonly?: boolean;
  coordinators?: LearningCoordinator[];
  institutionContacts?: LearningInstitutionContacts[];
  linkedPages?: Record<string, WikiPageSummary>;
}>(), { coordinators: () => [], institutionContacts: () => [], linkedPages: () => ({}) });
</script>

<template>
  <div class="space-y-5">
    <WikiProse v-if="step.description" :html="step.description" :pages="linkedPages" prose-class="max-w-prose text-foreground [&_iframe]:aspect-video [&_iframe]:h-auto [&_iframe]:w-full" data-slot="step-description" />
    <LearningAudienceNotes :notes="step.audienceNotes" />

    <LearningCoordinators v-if="step.key === 'meet_coordinator'" :coordinators="coordinators" />
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
  </div>
</template>
