<template>
  <SectionCard :title="$t('Pareigų istorija')" :icon="GitBranch" :count="groups.length || undefined">
    <ol class="relative space-y-5 border-l border-border pl-5">
      <li
        v-for="group in groups"
        :key="group.year"
        class="relative"
      >
        <span
          :class="[
            'absolute -left-[1.5625rem] top-1 h-3 w-3 rounded-full ring-4 ring-background',
            group.isCurrent ? 'bg-primary' : 'bg-muted-foreground/40',
          ]"
        />
        <div class="flex items-center gap-2">
          <span class="text-sm font-semibold text-foreground">{{ group.label }}</span>
          <Badge v-if="group.isCurrent" variant="secondary" class="text-[10px] uppercase">
            {{ $t('Dabartiniai') }}
          </Badge>
        </div>

        <div v-if="group.members.length > 0" class="mt-1.5">
          <UsersAvatarGroup :users="group.members" :max="6" :size="28" />
        </div>
        <p v-else class="mt-1 text-xs text-muted-foreground">
          {{ $t('Neužimta') }}
        </p>
      </li>
    </ol>
  </SectionCard>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { GitBranch } from 'lucide-vue-next';

import { SectionCard } from '@/Components/ui/section-card';
import { Badge } from '@/Components/ui/badge';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';
import { academicYear, currentAcademicYear, formatAcademicYearLabel } from '@/Utils/IntlTime';

interface LineageGroup {
  year: number;
  label: string;
  isCurrent: boolean;
  members: App.Entities.User[];
}

const props = defineProps<{
  members: App.Entities.User[];
}>();

/**
 * Bucket members into academic years their tenure overlapped, from the earliest
 * recorded membership through the current academic year (newest first).
 */
const groups = computed<LineageGroup[]>(() => {
  const currentYear = currentAcademicYear();
  const buckets = new Map<number, App.Entities.User[]>();

  let earliestYear = currentYear;

  for (const member of props.members) {
    const start = member.pivot?.start_date;
    if (!start) {
      continue;
    }
    const startYear = academicYear(new Date(start));
    const end = member.pivot?.end_date;
    const endYear = end ? Math.min(academicYear(new Date(end)), currentYear) : currentYear;

    earliestYear = Math.min(earliestYear, startYear);

    for (let year = startYear; year <= endYear; year++) {
      const bucket = buckets.get(year) ?? [];
      if (!bucket.some((existing) => existing.id === member.id)) {
        bucket.push(member);
      }
      buckets.set(year, bucket);
    }
  }

  const result: LineageGroup[] = [];
  for (let year = currentYear; year >= earliestYear; year--) {
    result.push({
      year,
      label: formatAcademicYearLabel(year),
      isCurrent: year === currentYear,
      members: buckets.get(year) ?? [],
    });
  }
  return result;
});
</script>
