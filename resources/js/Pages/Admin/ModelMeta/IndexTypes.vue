<template>
  <OverviewPage :eyebrow="$t('shell.workspaces.sistema.title')" :title="$t('types.directory_title')" :lead="$t('types.directory_lead')">
    <SpotlightPopover
      :title="$t('types.directory_title')"
      :description="$t('types.spotlight')"
      :is-dismissed="spotlight.isDismissed.value"
      class="w-full"
      @dismiss="spotlight.dismiss"
    >
      <NavigationTiles :items="tiles" :columns="3" @navigate="spotlight.dismiss" />
    </SpotlightPopover>
  </OverviewPage>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { NavigationTiles } from '@/Components/Patterns';
import type { AdminSection } from '@/Composables/useAdminNavigation';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { sectionTile } from '@/Constants/adminSections';

const props = defineProps<{ destinations: AdminSection[] }>();
const tiles = computed(() => props.destinations.map(sectionTile));
const spotlight = useFeatureSpotlight('types-directory-v1');
</script>
