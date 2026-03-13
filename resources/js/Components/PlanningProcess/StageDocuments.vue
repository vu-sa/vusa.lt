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
      <div v-if="hasTemplates || canUpdate" class="rounded-lg border border-dashed border-muted-foreground/30 bg-muted/30 p-4 flex flex-col gap-3">
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
                v-if="canUpdate"
                variant="ghost"
                size="icon"
                class="h-7 w-7 text-muted-foreground hover:text-destructive"
                :disabled="deleteTipTemplateForm.processing"
                @click="deleteTemplate('tip_template')"
              >
                <Trash2Icon class="h-3.5 w-3.5" />
              </Button>
            </div>
            <div v-else-if="canUpdate" class="flex flex-wrap items-center gap-2">
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
                v-if="canUpdate"
                variant="ghost"
                size="icon"
                class="h-7 w-7 text-muted-foreground hover:text-destructive"
                :disabled="deleteMvpTemplateForm.processing"
                @click="deleteTemplate('mvp_template')"
              >
                <Trash2Icon class="h-3.5 w-3.5" />
              </Button>
            </div>
            <div v-else-if="canUpdate" class="flex flex-wrap items-center gap-2">
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
      <div class="rounded-lg border p-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <FileIcon class="h-4 w-4 text-blue-600 dark:text-blue-400" />
            <h4 class="font-medium text-sm">{{ $t("TĮP (Tikslo įgyvendinimo planas)") }}</h4>
          </div>
          <Badge v-if="planningProcess.tip_approved_at" variant="success" class="gap-1">
            <CheckIcon class="h-3 w-3" />
            {{ $t("Patvirtinta") }}
          </Badge>
        </div>

        <div v-if="planningProcess.tip_document_url" class="flex items-center gap-3">
          <a
            :href="planningProcess.tip_document_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm transition-colors hover:bg-muted"
          >
            <DownloadIcon class="h-4 w-4 text-primary" />
            {{ planningProcess.tip_document_name ?? "TĮP.pdf" }}
            <ExternalLinkIcon class="h-3 w-3 text-muted-foreground" />
          </a>
        </div>
        <div v-else class="flex items-center gap-2 text-sm text-muted-foreground">
          <UploadCloudIcon class="h-4 w-4" />
          {{ $t("TĮP dokumentas dar neįkeltas") }}
        </div>

        <div v-if="canUpdate && !planningProcess.tip_approved_at" class="flex flex-wrap items-center gap-2">
          <Input
            type="file"
            accept=".pdf"
            class="w-auto max-w-64"
            @change="onTipFileChange"
          />
          <Button
            size="sm"
            class="gap-1.5"
            :disabled="tipForm.processing || !tipFile"
            @click="uploadTip"
          >
            <UploadIcon class="h-3.5 w-3.5" />
            {{ $t("Įkelti") }}
          </Button>
          <Button
            v-if="planningProcess.tip_document_url"
            variant="outline"
            size="sm"
            class="gap-1.5"
            :disabled="tipApproveForm.processing"
            @click="approveTip"
          >
            <CheckIcon class="h-3.5 w-3.5" />
            {{ $t("Patvirtinti") }}
          </Button>
        </div>
      </div>

      <!-- MVP document -->
      <div class="rounded-lg border p-4 flex flex-col gap-3">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <FileIcon class="h-4 w-4 text-purple-600 dark:text-purple-400" />
            <h4 class="font-medium text-sm">{{ $t("MVP (Mokslo metų veiklos planas)") }}</h4>
          </div>
          <Badge v-if="planningProcess.mvp_approved_at" variant="success" class="gap-1">
            <CheckIcon class="h-3 w-3" />
            {{ $t("Patvirtinta") }}
          </Badge>
        </div>

        <div v-if="planningProcess.mvp_document_url" class="flex items-center gap-3">
          <a
            :href="planningProcess.mvp_document_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm transition-colors hover:bg-muted"
          >
            <DownloadIcon class="h-4 w-4 text-primary" />
            {{ planningProcess.mvp_document_name ?? "MVP.pdf" }}
            <ExternalLinkIcon class="h-3 w-3 text-muted-foreground" />
          </a>
        </div>
        <div v-else class="flex items-center gap-2 text-sm text-muted-foreground">
          <UploadCloudIcon class="h-4 w-4" />
          {{ $t("MVP dokumentas dar neįkeltas") }}
        </div>

        <div
          v-if="canUpdate && !planningProcess.mvp_approved_at"
          class="flex flex-wrap items-center gap-2"
        >
          <template v-if="planningProcess.tip_document_url">
            <Input
              type="file"
              accept=".pdf"
              class="w-auto max-w-64"
              @change="onMvpFileChange"
            />
            <Button
              size="sm"
              class="gap-1.5"
              :disabled="mvpForm.processing || !mvpFile"
              @click="uploadMvp"
            >
              <UploadIcon class="h-3.5 w-3.5" />
              {{ $t("Įkelti") }}
            </Button>
          </template>
          <div v-else class="flex items-center gap-2 text-sm text-amber-600 dark:text-amber-400">
            <AlertCircleIcon class="h-4 w-4" />
            {{ $t("Pirmiausia įkelkite TĮP dokumentą") }}
          </div>
          <Button
            v-if="planningProcess.mvp_document_url"
            variant="outline"
            size="sm"
            class="gap-1.5"
            :disabled="mvpApproveForm.processing"
            @click="approveMvp"
          >
            <CheckIcon class="h-3.5 w-3.5" />
            {{ $t("Patvirtinti") }}
          </Button>
        </div>
      </div>
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
  File as FileIcon,
  FileArchive as FileArchiveIcon,
  Check as CheckIcon,
  Download as DownloadIcon,
  ExternalLink as ExternalLinkIcon,
  Upload as UploadIcon,
  UploadCloud as UploadCloudIcon,
  AlertCircle as AlertCircleIcon,
  Trash2 as Trash2Icon,
} from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import DeadlineBanner from "@/Components/PlanningProcess/DeadlineBanner.vue";
import StageCommentThread from "@/Components/PlanningProcess/StageCommentThread.vue";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  deadline?: App.Entities.PlanningStageDeadline | null;
  canUpdate: boolean;
  comments: App.Entities.Comment[];
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
const tipApproveForm = useForm({ collection: "tip_document" });
const mvpApproveForm = useForm({ collection: "mvp_document" });

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
  });
};

const uploadMvp = () => {
  if (!mvpFile.value) return;
  mvpForm.collection = "mvp_document";
  mvpForm.document = mvpFile.value;
  mvpForm.post(route("planningProcesses.uploadDocument", props.planningProcess.id), {
    preserveScroll: true,
    forceFormData: true,
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

const approveTip = () => {
  tipApproveForm.patch(route("planningProcesses.approveDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};

const approveMvp = () => {
  mvpApproveForm.patch(route("planningProcesses.approveDocument", props.planningProcess.id), {
    preserveScroll: true,
  });
};
</script>
