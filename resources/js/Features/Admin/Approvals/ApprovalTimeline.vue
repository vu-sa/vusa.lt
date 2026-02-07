<template>
  <div class="space-y-3">
    <div v-for="(approval, index) in approvals" :key="approval.id" class="flex items-start gap-3">
      <!-- Timeline indicator -->
      <div class="flex flex-col items-center">
        <div :class="[
          'size-8 rounded-full flex items-center justify-center',
          getDecisionClasses(approval.decision)
        ]">
          <component :is="getDecisionIcon(approval.decision)" class="size-4" />
        </div>
        <!-- Connector line -->
        <div v-if="index < approvals.length - 1" class="w-0.5 h-8 bg-border" />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0 pb-4">
        <div class="flex items-center gap-2 flex-wrap">
          <UserAvatar v-if="approval.user" :user="approval.user" :size="20" />
          <span class="font-medium text-sm">{{ approval.user?.name ?? $t("Nežinomas") }}</span>
          <ApprovalBadge :decision="approval.decision" />
          <span class="text-xs text-muted-foreground">
            {{ formatDate(approval.created_at) }}
          </span>
        </div>
        <p v-if="approval.notes" class="mt-1 text-sm text-muted-foreground">
          {{ approval.notes }}
        </p>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="approvals.length === 0" class="text-center py-4 text-sm text-muted-foreground">
      {{ $t("Nėra patvirtinimų istorijos.") }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";

import ApprovalBadge from "./ApprovalBadge.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";
import IFluentCheckmark24Filled from "~icons/fluent/checkmark-24-filled";
import IFluentDismiss24Filled from "~icons/fluent/dismiss-24-filled";
import IFluentArrowUndo24Filled from "~icons/fluent/arrow-undo-24-filled";

type ApprovalDecision = "approved" | "rejected" | "cancelled";

interface Approval {
  id: string;
  decision: ApprovalDecision;
  step: number;
  notes: string | null;
  created_at: string;
  user?: {
    id: string;
    name: string;
    profile_photo_path: string | null;
  };
}

defineProps<{
  approvals: Approval[];
}>();

const getDecisionClasses = (decision: ApprovalDecision) => {
  switch (decision) {
    case "approved":
      return "bg-success/10 text-success";
    case "rejected":
      return "bg-destructive/10 text-destructive";
    case "cancelled":
      return "bg-warning/10 text-warning";
    default:
      return "bg-muted text-muted-foreground";
  }
};

const getDecisionIcon = (decision: ApprovalDecision) => {
  switch (decision) {
    case "approved":
      return IFluentCheckmark24Filled;
    case "rejected":
      return IFluentDismiss24Filled;
    case "cancelled":
      return IFluentArrowUndo24Filled;
    default:
      return IFluentCheckmark24Filled;
  }
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};
</script>
