<template>
  <OverviewSection
    :title="sectionTitle"
    :icon="Landmark"
    variant="home"
    :empty="institutions.length === 0"
    :empty-text="$t('visos institucijos posėdžius fiksuoja laiku')"
  >
    <template v-if="askable" #actions>
      <SpotlightPopover
        :title="$t('visak.activity_request.spotlight_title')"
        :description="$t('visak.activity_request.spotlight_body')"
        :is-dismissed="askSpotlight.isDismissed.value"
        @dismiss="askSpotlight.dismiss()"
      >
        <Button
          variant="outline"
          size="sm"
          class="pointer-coarse:h-11"
          data-slot="institutions-needing-attention-ask"
          @click="ask"
        >
          <MailQuestion aria-hidden="true" />
          {{ $t('visak.activity_request.ask') }}
        </Button>
      </SpotlightPopover>
    </template>

    <InstitutionAttentionList :institutions="visibleInstitutions" @record="emit('record', $event)" />

    <!-- A padalinys can have hundreds: the rest open in a searchable dialog, not inline. -->
    <template v-if="hiddenCount > 0">
      <Button
        variant="outline"
        size="sm"
        class="self-start pointer-coarse:h-11"
        data-slot="institutions-needing-attention-more"
        @click="dialogOpen = true"
      >
        <List aria-hidden="true" />
        {{ $t('Rodyti visas (:count)', { count: String(institutions.length) }) }}
      </Button>

      <Dialog v-model:open="dialogOpen">
        <DialogContent class="flex max-h-[85vh] flex-col gap-4 sm:max-w-2xl">
          <DialogHeader>
            <DialogTitle>{{ sectionTitle }} · {{ institutions.length }}</DialogTitle>
            <DialogDescription class="sr-only">
              {{ $t('Ieškoti institucijos') }}
            </DialogDescription>
          </DialogHeader>
          <div class="relative">
            <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
            <Input v-model="query" type="search" class="pl-9" :placeholder="$t('Ieškoti institucijos')" :aria-label="$t('Ieškoti institucijos')" />
          </div>
          <div class="-mx-6 min-h-0 flex-1 overflow-y-auto px-6" data-slot="institutions-needing-attention-dialog-list">
            <InstitutionAttentionList :institutions="filteredInstitutions" @record="recordFromDialog" />
            <p v-if="filteredInstitutions.length === 0" class="py-6 text-sm text-muted-foreground">
              {{ $t('Nieko nerasta') }}
            </p>
          </div>
        </DialogContent>
      </Dialog>
    </template>
  </OverviewSection>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Landmark, List, MailQuestion, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import InstitutionAttentionList from './InstitutionAttentionList.vue';
import type { InstitutionActivityInsight } from './types';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';

const props = defineProps<{
  institutions: InstitutionActivityInsight[];
  title?: string;
  /** Rows shown on the page; the rest open in a dialog. Unset shows all. */
  limit?: number;
  /** Offers to email the representatives of every listed institution "Ar vyko posėdis?". */
  askable?: boolean;
}>();

const emit = defineEmits<{
  record: [institution: InstitutionActivityInsight];
  ask: [institutions: InstitutionActivityInsight[]];
}>();

const askSpotlight = useFeatureSpotlight('activity-requests-v1');

function ask(): void {
  askSpotlight.dismiss();
  emit('ask', props.institutions);
}

const sectionTitle = computed(() => props.title ?? $t('Tavo institucijos'));

const visibleInstitutions = computed(() =>
  props.limit === undefined ? props.institutions : props.institutions.slice(0, props.limit),
);

const hiddenCount = computed(() =>
  props.limit === undefined ? 0 : Math.max(0, props.institutions.length - props.limit),
);

const dialogOpen = ref(false);
const query = ref('');

const filteredInstitutions = computed(() => {
  const needle = query.value.trim().toLocaleLowerCase();

  return needle === ''
    ? props.institutions
    : props.institutions.filter(institution =>
        `${institution.name} ${institution.tenant_name ?? ''}`.toLocaleLowerCase().includes(needle));
});

// The window opens over the page, so the dialog steps aside first.
function recordFromDialog(institution: InstitutionActivityInsight): void {
  dialogOpen.value = false;
  emit('record', institution);
}
</script>
