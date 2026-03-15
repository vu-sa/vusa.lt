<template>
  <div class="flex flex-col gap-4">
  <DeadlineBanner :deadline="deadline ?? null" :is-stage-complete="!!planningProcess.tip_approved_at && !!planningProcess.mvp_approved_at" />
  <Card>
    <CardHeader>
      <div class="flex items-center gap-3">
        <div class="shrink-0 h-9 w-9 rounded-lg bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
          <FileTextIcon class="h-4.5 w-4.5 text-primary" />
        </div>
        <div class="flex-1 min-w-0">
          <CardTitle class="text-base">{{ $t("III etapas: Dokumentai") }}</CardTitle>
          <CardDescription>
            {{ $t("TĮP ir MVP dokumentų įkėlimas ir tvirtinimas") }}
          </CardDescription>
        </div>
      </div>
    </CardHeader>
    <CardContent class="flex flex-col gap-4">
      <!-- Centralized resources section -->
      <div v-if="planningResources.length > 0" class="rounded-lg border border-dashed border-muted-foreground/30 bg-muted/30 p-4 flex flex-col gap-3">
        <div class="flex items-center gap-2">
          <FileArchiveIcon class="h-4 w-4 text-muted-foreground" />
          <h4 class="font-medium text-sm text-muted-foreground">{{ $t("planning.resources_title") }}</h4>
        </div>

        <div class="flex flex-col gap-2">
          <div
            v-for="resource in planningResources"
            :key="resource.id"
            class="flex items-start gap-2.5"
          >
            <FileIcon v-if="resource.type === 'file'" class="h-3.5 w-3.5 mt-0.5 text-muted-foreground shrink-0" />
            <LinkIcon v-else-if="resource.type === 'url'" class="h-3.5 w-3.5 mt-0.5 text-muted-foreground shrink-0" />
            <FileTextSmallIcon v-else class="h-3.5 w-3.5 mt-0.5 text-muted-foreground shrink-0" />

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-1.5">
                <span class="text-sm font-medium">{{ resource.title }}</span>
                <Badge v-if="resource.category" variant="secondary" class="text-[10px]">
                  {{ resource.category === 'tip_template' ? 'TĮP' : 'MVP' }}
                </Badge>
              </div>
              <a
                v-if="resource.type === 'file' && resource.file_url"
                :href="resource.file_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 text-xs text-primary hover:underline mt-0.5"
              >
                <DownloadIcon class="h-3 w-3" />
                {{ resource.file_name }}
                <ExternalLinkIcon class="h-2.5 w-2.5 text-muted-foreground" />
              </a>
              <a
                v-else-if="resource.type === 'url' && resource.content"
                :href="resource.content"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1.5 text-xs text-primary hover:underline mt-0.5"
              >
                <ExternalLinkIcon class="h-3 w-3" />
                {{ resource.content }}
              </a>
              <div
                v-else-if="resource.type === 'text' && resource.content"
                class="text-xs text-muted-foreground whitespace-pre-wrap mt-0.5"
              >
                {{ resource.content }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TIP document -->
      <DocumentSection
        :title="$t('TĮP (Tikslo įgyvendinimo planas)')"
        :icon-class="'text-blue-600 dark:text-blue-400'"
        :documents="tipDocuments"
        :approved-at="planningProcess.tip_approved_at"
        :approved-media-id="planningProcess.tip_approved_media_id"
        :approvals="tipApprovals"
        :can-update="canUpdate"
        :can-approve="canApprove"
        :empty-text="$t('TĮP dokumentas dar neįkeltas')"
        :is-processing-upload="tipForm.processing"
        :is-processing-approve="tipApproveForm.processing"
        :is-processing-reject="tipRejectForm.processing"
        @file-change="onTipFileChange"
        @upload="uploadTip"
        @approve="approveTip"
        @reject="rejectTip"
      />

      <!-- MVP document -->
      <DocumentSection
        :title="$t('MVP (Mokslo metų veiklos planas)')"
        :icon-class="'text-purple-600 dark:text-purple-400'"
        :documents="mvpDocuments"
        :approved-at="planningProcess.mvp_approved_at"
        :approved-media-id="planningProcess.mvp_approved_media_id"
        :approvals="mvpApprovals"
        :can-update="canUpdate"
        :can-approve="canApprove"
        :empty-text="$t('MVP dokumentas dar neįkeltas')"
        :prerequisite-met="tipDocuments.length > 0"
        :prerequisite-text="$t('Pirmiausia įkelkite TĮP dokumentą')"
        :is-processing-upload="mvpForm.processing"
        :is-processing-approve="mvpApproveForm.processing"
        :is-processing-reject="mvpRejectForm.processing"
        @file-change="onMvpFileChange"
        @upload="uploadMvp"
        @approve="approveMvp"
        @reject="rejectMvp"
      />
    </CardContent>
  </Card>

  <StageCommentThread
    :planning-process="planningProcess"
    :stage="3"
    :comments="comments"
  />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import {
  FileText as FileTextIcon,
  FileArchive as FileArchiveIcon,
  File as FileIcon,
  FileText as FileTextSmallIcon,
  Link as LinkIcon,
  Download as DownloadIcon,
  ExternalLink as ExternalLinkIcon,
} from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import DeadlineBanner from "@/Components/PlanningProcess/DeadlineBanner.vue";
import StageCommentThread from "@/Components/PlanningProcess/StageCommentThread.vue";
import DocumentSection from "@/Components/PlanningProcess/DocumentSection.vue";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  deadline?: App.Entities.PlanningStageDeadline | null;
  canUpdate: boolean;
  canApprove: boolean;
  planningResources: App.Entities.PlanningResource[];
  comments: App.Entities.Comment[];
  tipDocuments: App.Entities.DocumentVersion[];
  mvpDocuments: App.Entities.DocumentVersion[];
  tipApprovals: App.Entities.ApprovalRecord[];
  mvpApprovals: App.Entities.ApprovalRecord[];
}>();

// Document files
const tipFile = ref<File | null>(null);
const mvpFile = ref<File | null>(null);

// Document forms
const tipForm = useForm({ collection: "tip_document", document: null as File | null });
const mvpForm = useForm({ collection: "mvp_document", document: null as File | null });
const tipApproveForm = useForm({ collection: "tip_document", notes: "" });
const mvpApproveForm = useForm({ collection: "mvp_document", notes: "" });
const tipRejectForm = useForm({ collection: "tip_document", notes: "" });
const mvpRejectForm = useForm({ collection: "mvp_document", notes: "" });

const onTipFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  tipFile.value = input.files?.[0] ?? null;
};

const onMvpFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  mvpFile.value = input.files?.[0] ?? null;
};

const uploadTip = () => {
  if (!tipFile.value) return;
  tipForm.collection = "tip_document";
  tipForm.document = tipFile.value;
  tipForm.post(route("planavimai.uploadDocument", props.planningProcess.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { tipFile.value = null; },
  });
};

const uploadMvp = () => {
  if (!mvpFile.value) return;
  mvpForm.collection = "mvp_document";
  mvpForm.document = mvpFile.value;
  mvpForm.post(route("planavimai.uploadDocument", props.planningProcess.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { mvpFile.value = null; },
  });
};

const approveTip = (notes: string) => {
  tipApproveForm.notes = notes;
  tipApproveForm.patch(route("planavimai.approveDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const approveMvp = (notes: string) => {
  mvpApproveForm.notes = notes;
  mvpApproveForm.patch(route("planavimai.approveDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const rejectTip = (notes: string) => {
  tipRejectForm.notes = notes;
  tipRejectForm.patch(route("planavimai.rejectDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const rejectMvp = (notes: string) => {
  mvpRejectForm.notes = notes;
  mvpRejectForm.patch(route("planavimai.rejectDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};
</script>
