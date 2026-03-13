<template>
  <div class="rounded-lg border p-4 flex flex-col gap-3">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <FileIcon :class="['h-4 w-4', iconClass]" />
        <h4 class="font-medium text-sm">{{ title }}</h4>
      </div>
      <Badge v-if="approvedAt" variant="success" class="gap-1">
        <CheckIcon class="h-3 w-3" />
        {{ $t("Patvirtinta") }}
      </Badge>
      <Badge v-else-if="isRejected" variant="destructive" class="gap-1">
        <XIcon class="h-3 w-3" />
        {{ $t("Atmesta") }}
      </Badge>
    </div>

    <!-- Rejection feedback banner -->
    <div v-if="isRejected && latestRejection" class="rounded-md border border-destructive/30 bg-destructive/5 p-3 flex flex-col gap-1">
      <div class="flex items-center gap-2 text-sm font-medium text-destructive">
        <AlertCircleIcon class="h-4 w-4" />
        {{ $t("Reikia pataisymų") }}
      </div>
      <p class="text-sm text-muted-foreground">{{ latestRejection.notes }}</p>
      <span class="text-xs text-muted-foreground">
        — {{ latestRejection.user?.name ?? $t("Nežinomas") }},
        {{ formatDate(latestRejection.created_at) }}
      </span>
    </div>

    <!-- Document version list -->
    <DocumentVersionList
      :documents="documents"
      :approved-media-id="approvedMediaId"
      :empty-text="emptyText"
    />

    <!-- Upload / Approve / Reject actions -->
    <div v-if="canUpdate" class="flex flex-col gap-3">
      <!-- Prerequisite check (e.g., MVP needs TIP first) -->
      <template v-if="prerequisiteText && !prerequisiteMet">
        <div class="flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
          <AlertCircleIcon class="h-4 w-4" />
          {{ prerequisiteText }}
        </div>
      </template>
      <template v-else>
        <!-- Upload input -->
        <div class="flex flex-wrap items-center gap-2">
          <Input
            type="file"
            accept=".pdf"
            class="w-auto max-w-64"
            @change="$emit('file-change', $event)"
          />
          <Button
            size="sm"
            class="gap-1.5"
            :disabled="isProcessingUpload"
            @click="$emit('upload')"
          >
            <UploadIcon class="h-3.5 w-3.5" />
            {{ documents.length > 0 ? $t("Įkelti naują versiją") : $t("Įkelti") }}
          </Button>
        </div>

        <!-- Approve / Reject buttons (coordinator only, when not yet approved) -->
        <div v-if="canApprove && documents.length > 0 && !approvedAt" class="flex flex-col gap-2">
          <Textarea
            v-model="reviewNotes"
            :placeholder="$t('Pastabos (neprivaloma tvirtinant, privaloma atmetant)...')"
            rows="2"
            class="text-sm"
          />
          <div class="flex flex-wrap items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              class="gap-1.5"
              :disabled="isProcessingReject || !reviewNotes.trim()"
              @click="handleReject"
            >
              <XIcon class="h-3.5 w-3.5 text-destructive" />
              {{ $t("Atmesti") }}
            </Button>
            <Button
              variant="default"
              size="sm"
              class="gap-1.5"
              :disabled="isProcessingApprove"
              @click="handleApprove"
            >
              <CheckIcon class="h-3.5 w-3.5" />
              {{ $t("Patvirtinti") }}
            </Button>
          </div>
        </div>
      </template>
    </div>

    <!-- Approval history timeline -->
    <Collapsible v-if="approvals.length > 0" v-model:open="showHistory">
      <CollapsibleTrigger class="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground transition-colors">
        <HistoryIcon class="h-3.5 w-3.5" />
        {{ $t("Tvirtinimo istorija") }} ({{ approvals.length }})
        <ChevronDownIcon :class="['h-3.5 w-3.5 transition-transform', showHistory && 'rotate-180']" />
      </CollapsibleTrigger>
      <CollapsibleContent class="mt-2">
        <ApprovalTimeline :approvals="approvals" />
      </CollapsibleContent>
    </Collapsible>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import {
  File as FileIcon,
  Check as CheckIcon,
  X as XIcon,
  Upload as UploadIcon,
  AlertCircle as AlertCircleIcon,
  History as HistoryIcon,
  ChevronDown as ChevronDownIcon,
} from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/collapsible";
import DocumentVersionList from "@/Components/PlanningProcess/DocumentVersionList.vue";
import ApprovalTimeline from "@/Features/Admin/Approvals/ApprovalTimeline.vue";

const props = defineProps<{
  title: string;
  iconClass: string;
  documents: App.Entities.DocumentVersion[];
  approvedAt?: string | null;
  approvedMediaId?: number | null;
  approvals: App.Entities.ApprovalRecord[];
  canUpdate: boolean;
  canApprove: boolean;
  emptyText: string;
  prerequisiteMet?: boolean;
  prerequisiteText?: string;
  isProcessingUpload: boolean;
  isProcessingApprove: boolean;
  isProcessingReject: boolean;
}>();

const emit = defineEmits<{
  (e: "file-change", event: Event): void;
  (e: "upload"): void;
  (e: "approve", notes: string): void;
  (e: "reject", notes: string): void;
}>();

const reviewNotes = ref("");
const showHistory = ref(false);

const isRejected = computed(() => {
  if (props.approvedAt) return false;
  return props.approvals.length > 0 && props.approvals[0]?.decision === "rejected";
});

const latestRejection = computed(() => {
  return props.approvals.find((a) => a.decision === "rejected") ?? null;
});

const handleApprove = () => {
  emit("approve", reviewNotes.value);
  reviewNotes.value = "";
};

const handleReject = () => {
  emit("reject", reviewNotes.value);
  reviewNotes.value = "";
};

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString("lt-LT", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
};
</script>
