<template>
  <Collapsible v-model:open="isOpen">
    <Card>
      <CardHeader class="pb-3">
        <CollapsibleTrigger class="flex w-full items-center justify-between">
          <div class="flex items-center gap-2">
            <HistoryIcon class="h-4 w-4 text-muted-foreground" />
            <CardTitle class="text-sm">{{ $t("Pakeitimų istorija") }}</CardTitle>
            <Badge variant="outline" class="text-xs">{{ changes.length }}</Badge>
          </div>
          <ChevronDownIcon :class="['h-4 w-4 text-muted-foreground transition-transform', isOpen && 'rotate-180']" />
        </CollapsibleTrigger>
      </CardHeader>
      <CollapsibleContent>
        <CardContent class="pt-0">
          <div v-if="changes.length > 0" class="space-y-3">
            <div v-for="change in changes" :key="change.id" class="flex items-start gap-3 text-sm">
              <div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-muted-foreground/40" />
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-medium">{{ change.causer_name ?? $t("Sistema") }}</span>
                  <span class="text-xs text-muted-foreground">{{ formatDate(change.created_at) }}</span>
                </div>
                <div class="mt-1 flex flex-col gap-1">
                  <div
                    v-for="field in getChangedFields(change)"
                    :key="field"
                    class="text-muted-foreground"
                  >
                    <span class="font-medium text-foreground">{{ getFieldLabel(field) }}:</span>
                    <span class="line-through text-destructive/70 ml-1">{{ truncate(String(change.old[field] ?? '—')) }}</span>
                    <span class="mx-1">&rarr;</span>
                    <span class="text-success">{{ truncate(String(change.new[field] ?? '—')) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-muted-foreground text-center py-3">
            {{ $t("Pakeitimų nėra.") }}
          </div>
        </CardContent>
      </CollapsibleContent>
    </Card>
  </Collapsible>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { History as HistoryIcon, ChevronDown as ChevronDownIcon } from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/collapsible";

defineProps<{
  changes: App.Entities.FieldChange[];
}>();

const isOpen = ref(false);

const fieldLabels: Record<string, string> = {
  expectations_text: "Lūkesčiai",
  goal_text: "Tikslas",
  meeting_1_notes: "1 susitikimo pastabos",
  meeting_1_date: "1 susitikimo data",
  meeting_2_notes: "2 susitikimo pastabos",
  meeting_2_date: "2 susitikimo data",
  reflection_text: "Refleksija",
  current_stage: "Etapas",
  goal_approved_at: "Tikslo patvirtinimas",
  tip_approved_at: "TĮP patvirtinimas",
  mvp_approved_at: "MVP patvirtinimas",
  moderator_user_id: "Moderatorius",
};

const getFieldLabel = (field: string) => {
  return fieldLabels[field] ?? field;
};

const getChangedFields = (change: App.Entities.FieldChange) => {
  return Object.keys(change.new).filter((key) => {
    // Skip internal tracking fields
    return !["updated_at", "locked_at", "tip_approved_by", "mvp_approved_by", "tip_approved_media_id", "mvp_approved_media_id"].includes(key);
  });
};

const truncate = (value: string, maxLength = 80) => {
  if (!value || value === "null" || value === "undefined") return "—";
  if (value.length <= maxLength) return value;
  return value.substring(0, maxLength) + "...";
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
