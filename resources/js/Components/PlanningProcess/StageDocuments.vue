<template>
  <Card>
    <CardHeader>
      <CardTitle class="text-base">{{ $t("III etapas: Dokumentai") }}</CardTitle>
      <CardDescription>
        {{ $t("TĮP ir MVP dokumentų įkėlimas ir tvirtinimas") }}
      </CardDescription>
    </CardHeader>
    <CardContent class="flex flex-col gap-6">
      <!-- TIP document -->
      <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
          <h4 class="font-medium text-sm">{{ $t("TĮP (Tikslo įgyvendinimo planas)") }}</h4>
          <Badge v-if="planningProcess.tip_approved_at" variant="success" class="text-xs">
            {{ $t("Patvirtinta") }}
          </Badge>
        </div>
        <div v-if="planningProcess.tip_document_url" class="flex items-center gap-3">
          <a
            :href="planningProcess.tip_document_url"
            target="_blank"
            rel="noopener noreferrer"
            class="text-sm text-primary hover:underline flex items-center gap-1"
          >
            <FileTextIcon class="w-4 h-4 shrink-0" />
            {{ planningProcess.tip_document_name ?? "TĮP.pdf" }}
          </a>
        </div>
        <div v-else class="text-sm text-muted-foreground">
          {{ $t("TĮP dokumentas dar neįkeltas") }}
        </div>

        <div v-if="canUpdate && !planningProcess.tip_approved_at" class="flex flex-wrap gap-2">
          <div class="flex items-center gap-2">
            <Input
              type="file"
              accept=".pdf"
              class="w-auto"
              @change="onTipFileChange"
            />
            <Button
              size="sm"
              :disabled="tipForm.processing || !tipFile"
              @click="uploadTip"
            >
              {{ $t("Įkelti TĮP") }}
            </Button>
          </div>
          <Button
            v-if="planningProcess.tip_document_url"
            variant="outline"
            size="sm"
            :disabled="tipApproveForm.processing"
            @click="approveTip"
          >
            {{ $t("Patvirtinti") }}
          </Button>
        </div>
      </div>

      <!-- MVP document -->
      <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
          <h4 class="font-medium text-sm">{{ $t("MVP (Mokslo metų veiklos planas)") }}</h4>
          <Badge v-if="planningProcess.mvp_approved_at" variant="success" class="text-xs">
            {{ $t("Patvirtinta") }}
          </Badge>
        </div>
        <div v-if="planningProcess.mvp_document_url" class="flex items-center gap-3">
          <a
            :href="planningProcess.mvp_document_url"
            target="_blank"
            rel="noopener noreferrer"
            class="text-sm text-primary hover:underline flex items-center gap-1"
          >
            <FileTextIcon class="w-4 h-4 shrink-0" />
            {{ planningProcess.mvp_document_name ?? "MVP.pdf" }}
          </a>
        </div>
        <div v-else class="text-sm text-muted-foreground">
          {{ $t("MVP dokumentas dar neįkeltas") }}
        </div>

        <div
          v-if="canUpdate && !planningProcess.mvp_approved_at"
          class="flex flex-wrap gap-2"
        >
          <div
            v-if="planningProcess.tip_document_url"
            class="flex items-center gap-2"
          >
            <Input
              type="file"
              accept=".pdf"
              class="w-auto"
              @change="onMvpFileChange"
            />
            <Button
              size="sm"
              :disabled="mvpForm.processing || !mvpFile"
              @click="uploadMvp"
            >
              {{ $t("Įkelti MVP") }}
            </Button>
          </div>
          <p v-else class="text-sm text-muted-foreground">
            {{ $t("Pirmiausia įkelkite TĮP dokumentą") }}
          </p>
          <Button
            v-if="planningProcess.mvp_document_url"
            variant="outline"
            size="sm"
            :disabled="mvpApproveForm.processing"
            @click="approveMvp"
          >
            {{ $t("Patvirtinti") }}
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { FileText as FileTextIcon } from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";

const props = defineProps<{
  planningProcess: App.Entities.PlanningProcess;
  canUpdate: boolean;
}>();

const tipFile = ref<File | null>(null);
const mvpFile = ref<File | null>(null);

const tipForm = useForm({ collection: "tip_document", document: null as File | null });
const mvpForm = useForm({ collection: "mvp_document", document: null as File | null });
const tipApproveForm = useForm({ collection: "tip_document" });
const mvpApproveForm = useForm({ collection: "mvp_document" });

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
  const formData = new FormData();
  formData.append("collection", "tip_document");
  formData.append("document", tipFile.value);
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
