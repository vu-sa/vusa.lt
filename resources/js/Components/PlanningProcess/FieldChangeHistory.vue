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
          <div v-if="changes.length > 0" class="space-y-4">
            <div v-for="[dateLabel, dateChanges] in groupedChanges" :key="dateLabel">
              <p class="text-xs font-medium text-muted-foreground mb-2">
                {{ dateLabel }}
              </p>
              <div class="border-l-2 border-zinc-200 dark:border-zinc-700 ml-1 pl-4 space-y-3">
                <div v-for="change in dateChanges" :key="change.id" class="relative">
                  <div class="absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full border-2 border-zinc-200 dark:border-zinc-700 bg-background" />
                  <div class="flex items-center gap-2 text-xs">
                    <span class="font-medium text-foreground">{{ change.causer_name ?? $t("Sistema") }}</span>
                    <span class="text-muted-foreground">{{ formatTime(change.created_at) }}</span>
                  </div>
                  <div class="mt-1.5 space-y-1">
                    <div
                      v-for="field in getChangedFields(change)"
                      :key="field"
                      class="rounded-md bg-muted/50 px-2.5 py-1.5"
                    >
                      <span class="font-medium text-foreground text-xs">{{ getFieldLabel(field) }}</span>
                      <div class="flex items-center gap-1.5 mt-0.5 text-xs">
                        <span class="line-through text-destructive/70">{{ truncate(String(change.old[field] ?? '—')) }}</span>
                        <ArrowRightIcon class="h-3 w-3 text-muted-foreground shrink-0" />
                        <span class="text-success">{{ truncate(String(change.new[field] ?? '—')) }}</span>
                      </div>
                    </div>
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
import { ref, computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import {
  History as HistoryIcon,
  ChevronDown as ChevronDownIcon,
  ArrowRight as ArrowRightIcon,
} from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/collapsible";

const props = defineProps<{
  changes: App.Entities.FieldChange[];
}>();

const isOpen = ref(false);

const groupedChanges = computed(() => {
  const groups: Record<string, App.Entities.FieldChange[]> = {};
  for (const change of props.changes) {
    const dateKey = new Date(change.created_at).toLocaleDateString("lt-LT", {
      year: "numeric",
      month: "long",
      day: "numeric",
    });
    if (!groups[dateKey]) groups[dateKey] = [];
    groups[dateKey].push(change);
  }
  return Object.entries(groups);
});

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
    return !["updated_at", "locked_at", "tip_approved_by", "mvp_approved_by", "tip_approved_media_id", "mvp_approved_media_id"].includes(key);
  });
};

const truncate = (value: string, maxLength = 80) => {
  if (!value || value === "null" || value === "undefined") return "—";
  if (value.length <= maxLength) return value;
  return value.substring(0, maxLength) + "...";
};

const formatTime = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleTimeString("lt-LT", {
    hour: "2-digit",
    minute: "2-digit",
  });
};
</script>
