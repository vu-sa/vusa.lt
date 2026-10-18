<template>
  <OverviewPage :title="$t(checklist.title)" :lead="$t('onboarding.lead')">
    <OverviewSection :title="$t('onboarding.your_progress')">
      <ChecklistProgress :resolved="checklist.resolved" :total="checklist.total" :label="progressLabel" />
      <p v-if="checklist.complete" role="status" class="text-sm">{{ $t('onboarding.finished') }}</p>
      <div class="flex flex-wrap items-center gap-2">
        <Button class="h-auto min-h-11 max-w-full whitespace-normal text-left" v-if="!checklist.complete" variant="ghost" voice="sentence" :disabled="saving" @click="setHidden(!checklist.hidden)">
          {{ $t(checklist.hidden ? 'onboarding.show_on_home' : 'onboarding.hide_on_home') }}
        </Button>
        <Button class="h-auto min-h-11 max-w-full whitespace-normal text-left" as-child variant="outline" voice="sentence"><Link :href="route('dashboard')">{{ $t('onboarding.back_home') }}</Link></Button>
      </div>
    </OverviewSection>

    <p v-if="failed" role="alert" class="text-sm text-destructive">{{ $t('onboarding.save_failed') }}</p>

    <ChecklistItems :items="checklist.items" :saving="saving" :initial-item="initialItem" @update="updateItem">
      <template #access>
        <Button class="h-auto min-h-11 max-w-full whitespace-normal text-left" as-child variant="link" voice="sentence"><Link :href="route('mySupportRequests.create')">{{ $t('onboarding.report_access') }}</Link></Button>
      </template>
      <template #installation>
        <InstallationInstructions :tutorials="installationTutorials" />
      </template>
      <template #push="{ item }">
        <BrowserNotificationSetup :needs-confirmation="item.status === 'pending'" :saving @confirm="updateItem('push', 'done')" />
      </template>
      <template #orientation>
        <p class="text-sm text-muted-foreground">{{ $t('onboarding.orientation.tasks') }}</p>
        <ul class="flex flex-wrap gap-x-5 gap-y-2">
          <li v-for="workspace in workspaces" :key="workspace.key">
            <Link :href="sectionHref(workspace.sections[0])" class="inline-block py-2 text-sm underline underline-offset-4">{{ $t(workspace.label) }}</Link>
          </li>
        </ul>
        <div class="flex flex-wrap items-center gap-2">
          <Button class="h-auto min-h-11 max-w-full whitespace-normal text-left" as-child variant="outline" voice="sentence"><Link :href="route('dashboard', { tour: 'welcome' })">{{ $t('onboarding.orientation.tour') }}</Link></Button>
          <Button class="h-auto min-h-11 max-w-full whitespace-normal text-left" as-child variant="link" voice="sentence"><a :href="guideHref" target="_blank" rel="noopener noreferrer">{{ $t('onboarding.orientation.guide') }}</a></Button>
          <Button class="h-auto min-h-11 max-w-full whitespace-normal text-left" as-child variant="link" voice="sentence"><Link :href="route('mySupportRequests.create')">{{ $t('onboarding.orientation.help') }}</Link></Button>
        </div>
      </template>
    </ChecklistItems>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import OverviewSection from '@/Components/Patterns/OverviewSection.vue';
import ChecklistProgress from '@/Components/Onboarding/ChecklistProgress.vue';
import ChecklistItems from '@/Components/Onboarding/ChecklistItems.vue';
import InstallationInstructions from '@/Components/Onboarding/InstallationInstructions.vue';
import BrowserNotificationSetup from '@/Components/Onboarding/BrowserNotificationSetup.vue';
import type { InstallationPlatform, InstallationTutorial, OnboardingChecklistData } from '@/Components/Onboarding/types';
import { Button } from '@/Components/ui/button';
import { useOnboarding } from '@/Composables/useOnboarding';
import { usePWA } from '@/Composables/usePWA';
import { sectionHref } from '@/Composables/useAdminNavigation';
import type { PageProps } from '@/Types/index';

const props = defineProps<{
  checklist: OnboardingChecklistData;
  installationTutorials: Partial<Record<InstallationPlatform, InstallationTutorial>>;
}>();
const page = usePage<PageProps>();
const { checklist, saving, failed, updateItem, setHidden } = useOnboarding(() => props.checklist);
const { isInstalled, hasPushSubscription } = usePWA();
const progressLabel = computed(() => $t('onboarding.progress', { count: String(checklist.value.resolved), total: String(checklist.value.total) }));
const workspaces = computed(() => (page.props.adminNavigation?.workspaces ?? []).filter(workspace => workspace.sections.length));
const initialItem = new URL(page.url, 'http://localhost').searchParams.get('item') ?? undefined;
const guideHref = computed(() => new URL('/gidas/mano/paskyra-ir-prieiga', page.props.app.url).href);

watch([isInstalled, hasPushSubscription, saving], () => {
  if (saving.value || failed.value) return;
  const detected = [['installation', isInstalled.value], ['push', hasPushSubscription.value]] as const;
  const next = detected.find(([key, ready]) => ready && checklist.value.items.some(item => item.key === key && item.status === 'pending'));
  if (next) void updateItem(next[0], 'done');
}, { immediate: true });
</script>
