<template>
  <div
    v-if="deadline && !isStageComplete"
    class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm"
    :class="bannerClasses"
  >
    <ClockIcon class="h-4 w-4 shrink-0" />
    <span class="flex-1">
      {{ formatDateRange }}
    </span>
    <Badge :variant="badgeVariant" class="shrink-0 text-xs">
      {{ statusText }}
    </Badge>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { Clock as ClockIcon } from "lucide-vue-next";
import { Badge } from "@/Components/ui/badge";

const props = defineProps<{
  deadline: App.Entities.PlanningStageDeadline | null;
  isStageComplete: boolean;
}>();

const today = computed(() => {
  const now = new Date();
  now.setHours(0, 0, 0, 0);
  return now;
});

const endsAt = computed(() => {
  if (!props.deadline) return null;
  const d = new Date(props.deadline.ends_at);
  d.setHours(0, 0, 0, 0);
  return d;
});

const daysRemaining = computed(() => {
  if (!endsAt.value) return 0;
  return Math.ceil((endsAt.value.getTime() - today.value.getTime()) / (1000 * 60 * 60 * 24));
});

const isOverdue = computed(() => daysRemaining.value < 0);
const isApproaching = computed(() => !isOverdue.value && daysRemaining.value <= 7);

const bannerClasses = computed(() => {
  if (isOverdue.value) {
    return "border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300";
  }
  if (isApproaching.value) {
    return "border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300";
  }
  return "border-green-200 bg-green-50 text-green-800 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300";
});

const badgeVariant = computed(() => {
  if (isOverdue.value) return "destructive";
  if (isApproaching.value) return "warning";
  return "success";
});

const formatDate = (value: string) => new Date(value).toLocaleDateString("lt-LT");

const formatDateRange = computed(() => {
  if (!props.deadline) return "";
  return `${formatDate(props.deadline.starts_at)} – ${formatDate(props.deadline.ends_at)}`;
});

const statusText = computed(() => {
  if (isOverdue.value) {
    return $t("planning.deadline_overdue", { days: Math.abs(daysRemaining.value) });
  }
  return $t("planning.deadline_days_remaining", { days: daysRemaining.value });
});
</script>
