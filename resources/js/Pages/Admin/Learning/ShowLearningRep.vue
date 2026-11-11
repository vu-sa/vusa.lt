<script setup lang="ts">
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import LearningPathView from '@/Features/Admin/Learning/LearningPathView.vue';
import type { LearningPathData } from '@/Types/learning';

defineProps<{ path: LearningPathData; rep: { id: string; name: string } }>();
</script>

<template>
  <OverviewPage :title="rep.name" :eyebrow="$t('learning_people.rep_path')">
    <p class="text-sm tabular-nums text-muted-foreground">{{ $t('learning_people.essentials') }} {{ path.totals.essentialDone }}/{{ path.totals.essentialTotal }} · {{ $t('learning_people.total') }} {{ path.totals.allDone }}/{{ path.totals.allTotal }}</p>
    <LearningPathView :path="path" readonly />
  </OverviewPage>
</template>
