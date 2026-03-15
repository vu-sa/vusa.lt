<template>
  <Sheet v-model:open="isOpen">
    <SheetContent class="sm:max-w-lg flex flex-col gap-0 p-0">
      <!-- Header -->
      <SheetHeader class="px-6 pt-6 pb-4">
        <div class="flex items-center gap-3">
          <div class="shrink-0 h-10 w-10 rounded-xl bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
            <FileArchiveIcon class="h-5 w-5 text-primary" />
          </div>
          <div class="flex-1 min-w-0">
            <SheetTitle class="text-base">{{ $t("planning.resources_title") }}</SheetTitle>
            <SheetDescription class="text-xs">
              {{ $t("Mokslo metai") }} {{ selectedYear }}–{{ selectedYear + 1 }}
            </SheetDescription>
          </div>
          <!-- Year selector -->
          <Select v-model="selectedYearStr">
            <SelectTrigger class="w-auto h-8 gap-1.5 text-xs shrink-0">
              <SelectValue />
            </SelectTrigger>
            <SelectContent align="end">
              <SelectItem v-for="year in academicYears" :key="year" :value="String(year)">
                {{ year }}–{{ year + 1 }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </SheetHeader>

      <Separator />

      <!-- Scrollable content -->
      <div class="flex-1 overflow-y-auto">
        <!-- Resources list -->
        <div class="px-6 py-4">
          <div v-if="filteredResources.length > 0" class="flex flex-col gap-2.5">
            <div
              v-for="resource in filteredResources"
              :key="resource.id"
              :class="[
                'group relative rounded-xl border p-3.5 transition-colors',
                'bg-white dark:bg-zinc-900',
                'hover:border-zinc-300 dark:hover:border-zinc-600',
              ]"
            >
              <div class="flex items-start gap-3">
                <!-- Type icon -->
                <div :class="[
                  'shrink-0 h-8 w-8 rounded-lg flex items-center justify-center',
                  resource.type === 'file' ? 'bg-blue-50 dark:bg-blue-950/40' :
                  resource.type === 'url' ? 'bg-violet-50 dark:bg-violet-950/40' :
                  'bg-amber-50 dark:bg-amber-950/40',
                ]">
                  <FileIcon v-if="resource.type === 'file'" class="h-4 w-4 text-blue-500 dark:text-blue-400" />
                  <GlobeIcon v-else-if="resource.type === 'url'" class="h-4 w-4 text-violet-500 dark:text-violet-400" />
                  <AlignLeftIcon v-else class="h-4 w-4 text-amber-500 dark:text-amber-400" />
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-sm leading-tight truncate">{{ resource.title }}</span>
                    <Badge v-if="resource.category" variant="outline" class="text-[10px] px-1.5 py-0 shrink-0 font-semibold">
                      {{ resource.category === 'tip_template' ? 'TĮP' : 'MVP' }}
                    </Badge>
                  </div>

                  <!-- File link -->
                  <a
                    v-if="resource.type === 'file' && resource.file_url"
                    :href="resource.file_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-1.5 inline-flex items-center gap-1.5 rounded-md bg-zinc-50 dark:bg-zinc-800 px-2 py-1 text-xs text-primary hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors"
                  >
                    <DownloadIcon class="h-3 w-3" />
                    <span class="truncate max-w-48">{{ resource.file_name }}</span>
                  </a>

                  <!-- URL link -->
                  <a
                    v-else-if="resource.type === 'url' && resource.content"
                    :href="resource.content"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-1.5 inline-flex items-center gap-1.5 rounded-md bg-zinc-50 dark:bg-zinc-800 px-2 py-1 text-xs text-primary hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors"
                  >
                    <ExternalLinkIcon class="h-3 w-3" />
                    <span class="truncate max-w-48">{{ resource.content }}</span>
                  </a>

                  <!-- Text content -->
                  <p
                    v-else-if="resource.type === 'text' && resource.content"
                    class="mt-1.5 text-xs text-muted-foreground leading-relaxed line-clamp-2"
                  >
                    {{ resource.content }}
                  </p>
                </div>

                <!-- Delete button -->
                <Button
                  v-if="canManage"
                  variant="ghost"
                  size="icon"
                  class="shrink-0 h-7 w-7 opacity-0 group-hover:opacity-100 transition-opacity text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                  :disabled="deleteForm.processing"
                  @click="deleteResource(resource)"
                >
                  <Trash2Icon class="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          </div>

          <!-- Empty state -->
          <div v-else class="flex flex-col items-center justify-center py-12 text-center">
            <div class="h-12 w-12 rounded-full bg-muted flex items-center justify-center mb-3">
              <FileArchiveIcon class="h-6 w-6 text-muted-foreground/60" />
            </div>
            <p class="text-sm font-medium text-muted-foreground">{{ $t("Šablonų ir šaltinių dar nėra") }}</p>
            <p v-if="canManage" class="text-xs text-muted-foreground/70 mt-1">{{ $t("Pridėkite šablonus, nuorodas ar aprašymus") }}</p>
          </div>
        </div>
      </div>

      <!-- Add form footer (coordinator only) -->
      <div v-if="canManage">
        <Separator />
        <div class="px-6 py-4">
          <!-- Collapsed: show add button -->
          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
          >
            <div v-if="!showAddForm" key="button">
              <Button variant="outline" class="w-full gap-2 h-9" @click="showAddForm = true">
                <PlusIcon class="h-4 w-4" />
                {{ $t("planning.add_resource") }}
              </Button>
            </div>

            <!-- Expanded: add form -->
            <div v-else key="form" class="flex flex-col gap-4">
              <!-- Title -->
              <div class="flex flex-col gap-1.5">
                <Label class="text-xs font-medium text-muted-foreground">{{ $t("forms.fields.title") }}</Label>
                <Input
                  v-model="addForm.title"
                  :placeholder="$t('forms.fields.title')"
                  class="h-9"
                />
              </div>

              <!-- Type selector as segmented control -->
              <div class="flex flex-col gap-1.5">
                <Label class="text-xs font-medium text-muted-foreground">{{ $tChoice("forms.fields.type", 0) }}</Label>
                <div class="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1">
                  <button
                    v-for="typeOption in typeOptions"
                    :key="typeOption.value"
                    type="button"
                    :class="[
                      'flex items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-all',
                      addForm.type === typeOption.value
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground',
                    ]"
                    @click="switchType(typeOption.value)"
                  >
                    <component :is="typeOption.icon" class="h-3.5 w-3.5" />
                    {{ typeOption.label }}
                  </button>
                </div>
              </div>

              <!-- Dynamic content field -->
              <div class="flex flex-col gap-1.5">
                <div v-if="addForm.type === 'file'">
                  <Input
                    type="file"
                    accept=".pdf,.doc,.docx,.xls,.xlsx"
                    class="text-xs h-9"
                    @change="onFileChange"
                  />
                  <p class="text-[10px] text-muted-foreground mt-1">PDF, DOC, DOCX, XLS, XLSX ({{ $t("iki") }} 20 MB)</p>
                </div>

                <Input
                  v-else-if="addForm.type === 'url'"
                  v-model="addForm.content"
                  type="url"
                  placeholder="https://..."
                  class="h-9"
                />

                <Textarea
                  v-else-if="addForm.type === 'text'"
                  v-model="addForm.content"
                  :placeholder="$t('forms.fields.description')"
                  rows="3"
                  class="resize-none text-sm"
                />
              </div>

              <!-- Category -->
              <div class="flex flex-col gap-1.5">
                <Label class="text-xs font-medium text-muted-foreground">{{ $t("Kategorija") }}</Label>
                <Select v-model="addForm.category">
                  <SelectTrigger class="h-9">
                    <SelectValue :placeholder="$t('Pasirinkti kategoriją (neprivaloma)')" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">{{ $t("Kita") }}</SelectItem>
                    <SelectItem value="tip_template">TĮP</SelectItem>
                    <SelectItem value="mvp_template">MVP</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Actions -->
              <div class="flex items-center gap-2">
                <Button
                  class="flex-1 h-9"
                  :disabled="addForm.processing || !isFormValid"
                  @click="submitResource"
                >
                  <PlusIcon v-if="!addForm.processing" class="h-4 w-4 mr-1.5" />
                  <Loader2Icon v-else class="h-4 w-4 mr-1.5 animate-spin" />
                  {{ $t("planning.add_resource") }}
                </Button>
                <Button variant="ghost" class="h-9 px-4" @click="resetForm">
                  {{ $t("forms.cancel") }}
                </Button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  AlignLeft as AlignLeftIcon,
  Download as DownloadIcon,
  ExternalLink as ExternalLinkIcon,
  File as FileIcon,
  FileArchive as FileArchiveIcon,
  Globe as GlobeIcon,
  Loader2 as Loader2Icon,
  Plus as PlusIcon,
  Trash2 as Trash2Icon,
} from "lucide-vue-next";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Separator } from "@/Components/ui/separator";
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from "@/Components/ui/sheet";
import { Textarea } from "@/Components/ui/textarea";

const props = defineProps<{
  planningResources: App.Entities.PlanningResource[];
  academicYears: number[];
  canManage: boolean;
}>();

const isOpen = defineModel<boolean>("open", { default: false });

// Default to the first (most recent) academic year
const selectedYearStr = ref(String(props.academicYears[0] ?? new Date().getFullYear()));
const selectedYear = computed(() => Number(selectedYearStr.value));

// Filter resources by selected year
const filteredResources = computed(() =>
  props.planningResources.filter((r) => r.academic_year_start === selectedYear.value),
);

const showAddForm = ref(false);
const selectedFile = ref<File | null>(null);

const typeOptions = computed(() => [
  { value: "file" as const, label: $t("planning.resource_type_file"), icon: FileIcon },
  { value: "url" as const, label: $t("planning.resource_type_url"), icon: GlobeIcon },
  { value: "text" as const, label: $t("planning.resource_type_text"), icon: AlignLeftIcon },
]);

const addForm = useForm({
  title: "",
  type: "file" as "file" | "url" | "text",
  content: "",
  file: null as File | null,
  academic_year_start: selectedYear.value,
  category: "none" as string,
});

const deleteForm = useForm({});

const isFormValid = computed(() => {
  if (!addForm.title.trim()) return false;
  if (addForm.type === "file" && !selectedFile.value) return false;
  if (addForm.type === "url" && !addForm.content.trim()) return false;
  if (addForm.type === "text" && !addForm.content.trim()) return false;
  return true;
});

// Reset add form when switching years
watch(selectedYear, () => {
  resetForm();
});

const switchType = (type: "file" | "url" | "text") => {
  addForm.type = type;
  addForm.content = "";
  selectedFile.value = null;
};

const onFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  selectedFile.value = input.files?.[0] ?? null;
};

const submitResource = () => {
  addForm.file = selectedFile.value;
  addForm.academic_year_start = selectedYear.value;

  const categoryValue = addForm.category === "none" ? null : addForm.category;

  addForm.transform((data) => ({
    ...data,
    category: categoryValue,
  })).post(route("planningResources.store"), {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => {
      resetForm();
    },
  });
};

const deleteResource = (resource: App.Entities.PlanningResource) => {
  deleteForm.delete(route("planningResources.destroy", resource.id), {
    preserveScroll: true,
  });
};

const resetForm = () => {
  showAddForm.value = false;
  selectedFile.value = null;
  addForm.reset();
};
</script>
