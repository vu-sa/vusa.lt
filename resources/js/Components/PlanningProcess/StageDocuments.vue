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
      <!-- Templates section -->
      <div v-if="hasTemplates || canManageTemplates" class="rounded-lg border border-dashed border-muted-foreground/30 bg-muted/30 p-4 flex flex-col gap-3">
        <div class="flex items-center gap-2">
          <FileArchiveIcon class="h-4 w-4 text-muted-foreground" />
          <h4 class="font-medium text-sm text-muted-foreground">{{ $t("Šablonai") }}</h4>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
          <!-- TIP template -->
          <div class="flex flex-col gap-2">
            <span class="text-xs font-medium text-muted-foreground">{{ $t("TĮP šablonas") }}</span>
            <div v-if="planningProcess.tip_template_url" class="flex items-center gap-2">
              <a
                :href="planningProcess.tip_template_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-muted"
              >
                <DownloadIcon class="h-3.5 w-3.5 text-primary" />
                {{ planningProcess.tip_template_name }}
                <ExternalLinkIcon class="h-3 w-3 text-muted-foreground" />
              </a>
              <Button
                v-if="canManageTemplates"
                variant="ghost"
                size="icon"
                class="h-7 w-7 text-muted-foreground hover:text-destructive"
                :disabled="deleteTipTemplateForm.processing"
                @click="deleteTemplate('tip_template')"
              >
                <Trash2Icon class="h-3.5 w-3.5" />
              </Button>
            </div>
            <div v-else-if="canManageTemplates" class="flex flex-wrap items-center gap-2">
              <Input
                type="file"
                accept=".pdf,.doc,.docx,.xls,.xlsx"
                class="w-auto max-w-52 text-xs"
                @change="onTipTemplateFileChange"
              />
              <Button
                size="sm"
                variant="outline"
                class="gap-1.5 h-7 text-xs"
                :disabled="tipTemplateForm.processing || !tipTemplateFile"
                @click="uploadTemplate('tip_template')"
              >
                <UploadIcon class="h-3 w-3" />
                {{ $t("Įkelti") }}
              </Button>
            </div>
            <div v-else class="text-xs text-muted-foreground italic">
              {{ $t("Šablonas nepridėtas") }}
            </div>
          </div>

          <!-- MVP template -->
          <div class="flex flex-col gap-2">
            <span class="text-xs font-medium text-muted-foreground">{{ $t("MVP šablonas") }}</span>
            <div v-if="planningProcess.mvp_template_url" class="flex items-center gap-2">
              <a
                :href="planningProcess.mvp_template_url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm transition-colors hover:bg-muted"
              >
                <DownloadIcon class="h-3.5 w-3.5 text-primary" />
                {{ planningProcess.mvp_template_name }}
                <ExternalLinkIcon class="h-3 w-3 text-muted-foreground" />
              </a>
              <Button
                v-if="canManageTemplates"
                variant="ghost"
                size="icon"
                class="h-7 w-7 text-muted-foreground hover:text-destructive"
                :disabled="deleteMvpTemplateForm.processing"
                @click="deleteTemplate('mvp_template')"
              >
                <Trash2Icon class="h-3.5 w-3.5" />
              </Button>
            </div>
            <div v-else-if="canManageTemplates" class="flex flex-wrap items-center gap-2">
              <Input
                type="file"
                accept=".pdf,.doc,.docx,.xls,.xlsx"
                class="w-auto max-w-52 text-xs"
                @change="onMvpTemplateFileChange"
              />
              <Button
                size="sm"
                variant="outline"
                class="gap-1.5 h-7 text-xs"
                :disabled="mvpTemplateForm.processing || !mvpTemplateFile"
                @click="uploadTemplate('mvp_template')"
              >
                <UploadIcon class="h-3 w-3" />
                {{ $t("Įkelti") }}
              </Button>
            </div>
            <div v-else class="text-xs text-muted-foreground italic">
              {{ $t("Šablonas nepridėtas") }}
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
import { ref, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import {
  FileText as FileTextIcon,
  FileArchive as FileArchiveIcon,
  Download as DownloadIcon,
  ExternalLink as ExternalLinkIcon,
  Upload as UploadIcon,
  Trash2 as Trash2Icon,
} from "lucide-vue-next";

import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import DeadlineBanner from "@/Components/PlanningProcess/DeadlineBanner.vue";
import StageCommentThread from "@/Components/PlanningProcess/StageCommentThread.vue";
import DocumentSection from "@/Components/PlanningProcess/DocumentSection.vue";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  deadline?: App.Entities.PlanningStageDeadline | null;
  canUpdate: boolean;
  canApprove: boolean;
  canManageTemplates: boolean;
  comments: App.Entities.Comment[];
  tipDocuments: App.Entities.DocumentVersion[];
  mvpDocuments: App.Entities.DocumentVersion[];
  tipApprovals: App.Entities.ApprovalRecord[];
  mvpApprovals: App.Entities.ApprovalRecord[];
}>();

const hasTemplates = computed(
  () => !!props.planningProcess.tip_template_url || !!props.planningProcess.mvp_template_url,
);

// Document files
const tipFile = ref<File | null>(null);
const mvpFile = ref<File | null>(null);

// Template files
const tipTemplateFile = ref<File | null>(null);
const mvpTemplateFile = ref<File | null>(null);

// Document forms
const tipForm = useForm({ collection: "tip_document", document: null as File | null });
const mvpForm = useForm({ collection: "mvp_document", document: null as File | null });
const tipApproveForm = useForm({ collection: "tip_document", notes: "" });
const mvpApproveForm = useForm({ collection: "mvp_document", notes: "" });
const tipRejectForm = useForm({ collection: "tip_document", notes: "" });
const mvpRejectForm = useForm({ collection: "mvp_document", notes: "" });

// Template forms
const tipTemplateForm = useForm({ collection: "tip_template", template: null as File | null });
const mvpTemplateForm = useForm({ collection: "mvp_template", template: null as File | null });
const deleteTipTemplateForm = useForm({ collection: "tip_template" });
const deleteMvpTemplateForm = useForm({ collection: "mvp_template" });

const onTipFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  tipFile.value = input.files?.[0] ?? null;
};

const onMvpFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  mvpFile.value = input.files?.[0] ?? null;
};

const onTipTemplateFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  tipTemplateFile.value = input.files?.[0] ?? null;
};

const onMvpTemplateFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  mvpTemplateFile.value = input.files?.[0] ?? null;
};

const uploadTip = () => {
  if (!tipFile.value) return;
  tipForm.collection = "tip_document";
  tipForm.document = tipFile.value;
  tipForm.post(route("planningProcesses.uploadDocument", props.planningProcess.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { tipFile.value = null; },
  });
};

const uploadMvp = () => {
  if (!mvpFile.value) return;
  mvpForm.collection = "mvp_document";
  mvpForm.document = mvpFile.value;
  mvpForm.post(route("planningProcesses.uploadDocument", props.planningProcess.id), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { mvpFile.value = null; },
  });
};

const uploadTemplate = (collection: "tip_template" | "mvp_template") => {
  const form = collection === "tip_template" ? tipTemplateForm : mvpTemplateForm;
  const file = collection === "tip_template" ? tipTemplateFile.value : mvpTemplateFile.value;

  if (!file) return;

  form.collection = collection;
  form.template = file;
  form.post(route("planningProcesses.uploadTemplate", props.planningProcess.id), {
    preserveScroll: true,
    forceFormData: true,
  });
};

const deleteTemplate = (collection: "tip_template" | "mvp_template") => {
  const form = collection === "tip_template" ? deleteTipTemplateForm : deleteMvpTemplateForm;
  form.collection = collection;
  form.delete(route("planningProcesses.deleteTemplate", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const approveTip = (notes: string) => {
  tipApproveForm.notes = notes;
  tipApproveForm.patch(route("planningProcesses.approveDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const approveMvp = (notes: string) => {
  mvpApproveForm.notes = notes;
  mvpApproveForm.patch(route("planningProcesses.approveDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const rejectTip = (notes: string) => {
  tipRejectForm.notes = notes;
  tipRejectForm.patch(route("planningProcesses.rejectDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const rejectMvp = (notes: string) => {
  mvpRejectForm.notes = notes;
  mvpRejectForm.patch(route("planningProcesses.rejectDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};
</script>
