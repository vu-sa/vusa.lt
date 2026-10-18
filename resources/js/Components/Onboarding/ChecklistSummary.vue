<template>
  <OverviewSection
    v-if="!checklist.hidden && !checklist.complete"
    :title="$t(checklist.title)"
    :icon="ListChecks"
    variant="home"
    data-slot="onboarding-summary"
  >
    <ChecklistProgress :resolved="checklist.resolved" :total="checklist.total" :label="progressLabel" />
    <p v-if="next" class="text-sm font-semibold">{{ $t(next.title) }}</p>
    <div class="flex flex-wrap items-center gap-2">
      <SpotlightPopover
        :title="$t('onboarding.spotlight_title')"
        :description="$t('onboarding.spotlight_description')"
        :is-dismissed="spotlight.isDismissed.value"
        @dismiss="spotlight.dismiss"
      >
        <Button as-child variant="outline" voice="sentence">
          <Link :href="checklist.nextHref" @click="spotlight.dismiss">{{ $t('onboarding.continue') }}</Link>
        </Button>
      </SpotlightPopover>
      <Button variant="ghost" voice="sentence" :disabled="saving" @click="hide">{{ $t('onboarding.hide') }}</Button>
    </div>
    <p v-if="failed" role="alert" class="text-sm text-destructive">{{ $t('onboarding.save_failed') }}</p>
  </OverviewSection>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ListChecks } from '@lucide/vue';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import ChecklistProgress from './ChecklistProgress.vue';
import type { OnboardingChecklistData } from './types';

import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Button } from '@/Components/ui/button';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { useOnboarding } from '@/Composables/useOnboarding';

const props = defineProps<{ checklist: OnboardingChecklistData }>();
const { checklist, saving, failed, setHidden } = useOnboarding(() => props.checklist);
const spotlight = useFeatureSpotlight('getting-started-v1');
const next = computed(() => checklist.value.items.find(item => item.status === 'pending'));
const progressLabel = computed(() => $t('onboarding.progress', { count: String(checklist.value.resolved), total: String(checklist.value.total) }));

async function hide() {
  if (await setHidden(true)) await spotlight.dismiss();
}
</script>
