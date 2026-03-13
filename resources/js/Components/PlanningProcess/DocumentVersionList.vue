<template>
  <div v-if="documents.length > 0" class="flex flex-col gap-2">
    <div
      v-for="(doc, index) in documents"
      :key="doc.id"
      :class="[
        'flex items-center gap-3 rounded-md border px-3 py-2 text-sm transition-colors',
        doc.id === approvedMediaId ? 'border-success/50 bg-success/5' : 'hover:bg-muted',
      ]"
    >
      <DownloadIcon class="h-4 w-4 shrink-0 text-primary" />
      <a
        :href="doc.url"
        target="_blank"
        rel="noopener noreferrer"
        class="flex-1 min-w-0 truncate hover:underline"
      >
        {{ doc.file_name }}
      </a>
      <div class="flex items-center gap-2 shrink-0">
        <Badge v-if="doc.id === approvedMediaId" variant="success" class="gap-1">
          <CheckIcon class="h-3 w-3" />
          {{ $t("Patvirtinta") }}
        </Badge>
        <Badge v-else-if="index === documents.length - 1" variant="outline">
          {{ $t("Naujausia") }}
        </Badge>
        <span class="text-xs text-muted-foreground whitespace-nowrap">
          {{ formatDate(doc.created_at) }}
        </span>
        <span class="text-xs text-muted-foreground">
          {{ formatSize(doc.size) }}
        </span>
      </div>
    </div>
  </div>
  <div v-else class="flex items-center gap-2 text-sm text-muted-foreground">
    <UploadCloudIcon class="h-4 w-4" />
    {{ emptyText }}
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  Download as DownloadIcon,
  Check as CheckIcon,
  UploadCloud as UploadCloudIcon,
} from "lucide-vue-next";
import { Badge } from "@/Components/ui/badge";

defineProps<{
  documents: App.Entities.DocumentVersion[];
  approvedMediaId?: number | null;
  emptyText: string;
}>();

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

const formatSize = (bytes: number) => {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};
</script>
