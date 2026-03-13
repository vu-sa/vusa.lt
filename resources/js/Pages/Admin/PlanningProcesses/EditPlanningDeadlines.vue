<template>
  <PageContent
    :title="`${$t('planning.deadlines')} ${academicYear}–${academicYear + 1}`"
    :back-url="route('planningProcesses.index')"
    :heading-icon="CalendarLtr24Regular"
  >
    <UpsertModelLayout>
      <!-- Year selector -->
      <div class="flex items-center gap-3 mb-6">
        <Label class="text-sm font-medium shrink-0">{{ $t("Mokslo metai") }}</Label>
        <div class="flex items-center gap-1">
          <Button
            variant="outline"
            size="icon"
            class="h-8 w-8"
            as-child
          >
            <Link :href="route('planningDeadlines.edit', academicYear - 1)" preserve-scroll>
              <ChevronLeftIcon class="h-4 w-4" />
            </Link>
          </Button>
          <span class="text-sm font-semibold tabular-nums px-3">
            {{ academicYear }}–{{ academicYear + 1 }}
          </span>
          <Button
            variant="outline"
            size="icon"
            class="h-8 w-8"
            as-child
          >
            <Link :href="route('planningDeadlines.edit', academicYear + 1)" preserve-scroll>
              <ChevronRightIcon class="h-4 w-4" />
            </Link>
          </Button>
        </div>
      </div>

      <!-- Visual timeline -->
      <div class="mb-8">
        <div class="relative flex items-center">
          <div class="absolute top-4 left-4 right-4 h-0.5 bg-zinc-200 dark:bg-zinc-700" />
          <div class="relative flex justify-between w-full">
            <div
              v-for="stage in localDeadlines"
              :key="stage.stage"
              class="flex flex-col items-center z-10"
            >
              <div
                class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold ring-2 ring-offset-2 ring-offset-background transition-colors"
                :class="stageTimelineClasses(stage)"
              >
                <CheckIcon v-if="stage.starts_at && stage.ends_at" class="h-4 w-4" />
                <span v-else>{{ stage.stage }}</span>
              </div>
              <span class="text-[10px] text-muted-foreground mt-1 hidden sm:block">
                {{ stageLabels[stage.stage - 1] }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <form @submit.prevent="submit">
        <div class="flex flex-col gap-4">
          <Card
            v-for="stage in localDeadlines"
            :key="stage.stage"
            :class="[
              'transition-all',
              stage.starts_at && stage.ends_at
                ? 'border-green-200 dark:border-green-900'
                : 'border-dashed',
            ]"
          >
            <CardContent class="p-4">
              <div class="flex flex-col gap-4">
                <!-- Stage header -->
                <div class="flex items-center gap-3">
                  <div
                    class="shrink-0 h-9 w-9 rounded-lg flex items-center justify-center"
                    :class="stageIconClasses(stage)"
                  >
                    <component :is="stageIcons[stage.stage - 1]" class="h-4.5 w-4.5" />
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold">
                      {{ stageRomanNumerals[stage.stage - 1] }}. {{ stageLabels[stage.stage - 1] }}
                    </h3>
                    <p class="text-xs text-muted-foreground">{{ stageDescriptions[stage.stage - 1] }}</p>
                  </div>
                  <Badge v-if="stage.starts_at && stage.ends_at" variant="success" class="shrink-0 gap-1">
                    <CheckIcon class="h-3 w-3" />
                    {{ $t("Nustatytas") }}
                  </Badge>
                  <Badge v-else variant="outline" class="shrink-0 text-muted-foreground">
                    {{ $t("Nenustatytas") }}
                  </Badge>
                </div>

                <!-- Date inputs -->
                <div class="grid sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <Label :for="`stage-${stage.stage}-starts`" class="text-xs font-medium flex items-center gap-1.5">
                      <CalendarIcon class="h-3.5 w-3.5 text-muted-foreground" />
                      {{ $t("Pradžia") }}
                    </Label>
                    <Input
                      :id="`stage-${stage.stage}-starts`"
                      v-model="stage.starts_at"
                      type="date"
                    />
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <Label :for="`stage-${stage.stage}-ends`" class="text-xs font-medium flex items-center gap-1.5">
                      <CalendarIcon class="h-3.5 w-3.5 text-muted-foreground" />
                      {{ $t("Pabaiga") }}
                    </Label>
                    <Input
                      :id="`stage-${stage.stage}-ends`"
                      v-model="stage.ends_at"
                      type="date"
                    />
                  </div>
                </div>

                <!-- Duration indicator -->
                <div v-if="stage.starts_at && stage.ends_at" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                  <ClockIcon class="h-3.5 w-3.5" />
                  {{ durationText(stage.starts_at, stage.ends_at) }}
                  <template v-if="stageStatus(stage) === 'active'">
                    <span class="text-primary font-medium">· {{ $t("Vykdoma") }}</span>
                  </template>
                  <template v-else-if="stageStatus(stage) === 'overdue'">
                    <span class="text-red-500 font-medium">· {{ $t("Pasibaigė") }}</span>
                  </template>
                  <template v-else-if="stageStatus(stage) === 'upcoming'">
                    <span class="text-muted-foreground">· {{ $t("Būsimas") }}</span>
                  </template>
                </div>
              </div>
            </CardContent>
          </Card>

          <div class="flex justify-end gap-2 mt-2">
            <Button type="button" variant="outline" as-child>
              <Link :href="route('planningProcesses.index')">{{ $t("Atšaukti") }}</Link>
            </Button>
            <Button type="submit" :disabled="form.processing" class="gap-1.5">
              <SaveIcon class="h-4 w-4" />
              {{ $t("Išsaugoti") }}
            </Button>
          </div>
        </div>
      </form>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { reactive, computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import CalendarLtr24Regular from "~icons/fluent/calendar-ltr24-regular";
import {
  Calendar as CalendarIcon,
  Check as CheckIcon,
  ChevronLeft as ChevronLeftIcon,
  ChevronRight as ChevronRightIcon,
  Clock as ClockIcon,
  Save as SaveIcon,
  Sparkles,
  Users,
  FileText,
  LayoutGrid,
  BarChart3,
} from "lucide-vue-next";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import Icons from "@/Types/Icons/regular";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";

interface LocalDeadline {
  stage: number;
  starts_at: string;
  ends_at: string;
}

const props = defineProps<{
  academicYear: number;
  deadlines: App.Entities.PlanningStageDeadline[];
}>();

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm(
    $tChoice("entities.planningProcess.model", 2),
    "planningProcesses.index",
    `${$t("planning.deadlines")} ${props.academicYear}–${props.academicYear + 1}`,
    Icons.PLANNING_PROCESS,
  )
);

const stageLabels = computed(() => [
  $t("Pasiruošimas"),
  $t("Susitikimai"),
  $t("Dokumentai"),
  $t("MVT"),
  $t("Monitoringas"),
]);

const stageDescriptions = computed(() => [
  $t("Lūkesčių suformulavimas moderatoriui"),
  $t("Susitikimai, problemos pasirinkimas, tikslo formulavimas"),
  $t("TĮP ir MVP dokumentų įkėlimas ir tvirtinimas"),
  $t("Padalinio veiklų sąrašas su lygmenimis"),
  $t("Ketvirtinė stebėsena ir metų pabaigos refleksija"),
]);

const stageRomanNumerals = ["I", "II", "III", "IV", "V"];
const stageIcons = [Sparkles, Users, FileText, LayoutGrid, BarChart3];

const toDateInput = (value: string | null | undefined): string => {
  if (!value) return "";
  return value.substring(0, 10);
};

const localDeadlines = reactive<LocalDeadline[]>(
  [1, 2, 3, 4, 5].map((stage) => {
    const existing = props.deadlines.find((d) => d.stage === stage);
    return {
      stage,
      starts_at: toDateInput(existing?.starts_at),
      ends_at: toDateInput(existing?.ends_at),
    };
  })
);

const form = useForm({ deadlines: localDeadlines });

const submit = () => {
  form.deadlines = localDeadlines;
  form.patch(route("planningDeadlines.update", props.academicYear), {
    preserveScroll: true,
  });
};

const today = new Date();
today.setHours(0, 0, 0, 0);

const stageStatus = (stage: LocalDeadline): "active" | "overdue" | "upcoming" | "past" | null => {
  if (!stage.starts_at || !stage.ends_at) return null;
  const start = new Date(stage.starts_at);
  const end = new Date(stage.ends_at);
  start.setHours(0, 0, 0, 0);
  end.setHours(0, 0, 0, 0);
  if (today > end) return "overdue";
  if (today >= start && today <= end) return "active";
  return "upcoming";
};

const stageTimelineClasses = (stage: LocalDeadline) => {
  if (!stage.starts_at || !stage.ends_at) {
    return "bg-zinc-100 text-zinc-400 ring-zinc-200/50 dark:bg-zinc-800 dark:text-zinc-500 dark:ring-zinc-700/50";
  }
  const status = stageStatus(stage);
  if (status === "active") return "bg-primary text-primary-foreground ring-primary/30";
  if (status === "overdue") return "bg-red-500/10 text-red-600 ring-red-500/20 dark:text-red-400";
  return "bg-green-500 text-white ring-green-500/30 dark:ring-green-500/20";
};

const stageIconClasses = (stage: LocalDeadline) => {
  if (!stage.starts_at || !stage.ends_at) {
    return "bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500";
  }
  const status = stageStatus(stage);
  if (status === "active") return "bg-primary/10 text-primary dark:bg-primary/20";
  if (status === "overdue") return "bg-red-500/10 text-red-600 dark:bg-red-500/20 dark:text-red-400";
  return "bg-green-500/10 text-green-600 dark:bg-green-500/20 dark:text-green-400";
};

const durationText = (start: string, end: string) => {
  const s = new Date(start);
  const e = new Date(end);
  const days = Math.ceil((e.getTime() - s.getTime()) / (1000 * 60 * 60 * 24));
  return `${days} ${$t("d.")}`;
};
</script>
