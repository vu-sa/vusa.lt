<template>
  <div class="flex flex-col gap-6">
    <FadeTransition>
      <SuggestionAlert v-if="props.showHint" :show-alert @alert-closed="showAlert = false">
        <template v-if="$page.props.app.locale === 'lt'">
          <p class="mt-0">
            <span>Kiekvienas posėdis turi</span>
            <ModelChip>
              <template #icon>
                <component :is="IconsRegular.AGENDA_ITEM" class="h-4 w-4" />
              </template>
              darbotvarkės klausimų
            </ModelChip>
          </p>
          <p class="mb-4">
            Įrašyk arba įkopijuok visus klausimus, kurie šiuo metu yra numatomi
            posėdyje. Galite pradėti nuo ankstesnio posėdžio klausimų.
          </p>
        </template>
        <template v-else>
          <p class="mt-0">
            <span>Each meeting has</span>
            <ModelChip>
              <template #icon>
                <component :is="IconsRegular.AGENDA_ITEM" class="h-4 w-4" />
              </template>
              agenda items
            </ModelChip>
          </p>
          <p class="mb-4">
            Enter or paste all the questions that are currently planned for the
            meeting. You can start from a previous meeting's agenda.
          </p>
        </template>
      </SuggestionAlert>
    </FadeTransition>

    <Form ref="agendaForm" v-slot="{ values }" :validation-schema="schema" :initial-values="initialValues.value" @submit="onSubmit">
      <div class="space-y-6">
        <FormField ref="agendaItemField" v-slot="{ componentField, setValue }" name="agendaItemTitles">
          <FieldArray v-slot="{ fields, remove, push, move }" name="agendaItemTitles">
            <FormItem>
              <FormLabel class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <component :is="IconsFilled.AGENDA_ITEM" class="h-4 w-4" />
                  {{ $t("Darbotvarkės klausimai") }}
                </div>
                <div class="text-xs text-muted-foreground">
                  {{ fields.length }} {{ $t('klausimai') }}
                </div>
              </FormLabel>

              <div class="flex flex-col gap-4">
                <!-- Individual items input with drag and drop -->
                <div v-if="!showQuestionInputInTextArea && fields.length > 0" class="space-y-2">
                  <TransitionGroup name="list" tag="div">
                    <div v-for="(field, index) in fields" :key="field.key"
                      class="flex items-start gap-2 p-2 rounded-lg border border-dashed hover:border-solid hover:bg-muted/50 transition-all"
                      :draggable="true" @dragstart="handleDragStart(index)" @dragover="handleDragOver"
                      @drop="handleDrop(index, move)">
                      <GripVertical class="h-4 w-4 mt-2 text-muted-foreground cursor-grab active:cursor-grabbing" />
                      <span class="w-7 flex-shrink-0 mt-2 text-sm font-medium text-muted-foreground">{{ `${index + 1}.`
                      }}</span>
                      <div class="flex-1 space-y-2">
                        <FormControl>
                          <Input v-model="(field as any).value" :placeholder="`Darbotvarkės klausimas nr. ${index + 1}`"
                            @keydown.enter.prevent="addNewItem(index + 1, push)"
                            @keydown.backspace="handleBackspace(index, (field as any).value, remove)" />
                        </FormControl>
                        <!-- Optional description for agenda items -->
                        <FormControl v-if="showDescriptions">
                          <Textarea v-model="(field as any).description"
                            :placeholder="$t('Papildomas aprašymas (neprivalomas)')" class="text-sm resize-none"
                            :rows="2" />
                        </FormControl>
                      </div>
                      <div class="flex items-center gap-1 mt-2">
                        <!-- Brought by students toggle -->
                        <TooltipProvider>
                          <Tooltip>
                            <TooltipTrigger as-child>
                              <Button 
                                type="button" 
                                :variant="broughtByStudentsFlags[index] ? 'default' : 'ghost'" 
                                size="icon" 
                                class="h-6 w-6"
                                :class="broughtByStudentsFlags[index] ? 'bg-vusa-red hover:bg-vusa-red/90' : 'opacity-50 hover:opacity-100'"
                                @click="broughtByStudentsFlags[index] = !broughtByStudentsFlags[index]">
                                <UsersIcon class="h-3 w-3" />
                              </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                              {{ broughtByStudentsFlags[index] ? $t('Studentų atneštas klausimas') : $t('Pažymėti kaip studentų atnešta') }}
                            </TooltipContent>
                          </Tooltip>
                        </TooltipProvider>
                        <Button type="button" variant="ghost" size="icon" class="h-6 w-6 opacity-50 hover:opacity-100"
                          @click="duplicateItem(index, (field as any).value, push)">
                          <Copy class="h-3 w-3" />
                        </Button>
                        <Button type="button" variant="ghost" size="icon"
                          class="h-6 w-6 opacity-50 hover:opacity-100 hover:text-destructive"
                          :disabled="fields.length === 1"
                          @click="remove(index)">
                          <TrashIcon class="h-3 w-3" />
                        </Button>
                      </div>
                    </div>
                  </TransitionGroup>

                  <!-- Action buttons - only when there are items -->
                  <div class="flex flex-wrap gap-2 pt-2">
                    <Button type="button" variant="outline" size="sm" @click="push('')">
                      <PlusIcon class="mr-2 h-4 w-4" />
                      {{ $t("Pridėti klausimą") }}
                    </Button>

                    <Button v-if="props.mode === 'create' && fields.length === 0" type="button" variant="outline" size="sm"
                      @click="showQuestionInputInTextArea = true; agendaInputMode = 'text'">
                      <DocumentIcon class="mr-2 h-4 w-4" />
                      {{ $t('Įkelti iš teksto') }}
                    </Button>

                    <Button v-if="props.mode === 'create'" type="button" variant="outline" size="sm"
                      @click="agendaItemField?.setValue([]); agendaInputMode = null">
                      <ArrowLeft class="mr-2 h-4 w-4" />
                      {{ $t('Grįžti') }}
                    </Button>
                  </div>

                  <!-- Descriptions Toggle -->
                  <div class="flex items-center justify-between pt-3 pb-1 px-1">
                    <div class="space-y-0.5">
                      <Label class="text-sm font-medium">
                        {{ $t('Aprašymai') }}
                      </Label>
                      <p class="text-xs text-muted-foreground">
                        {{ $t('Pridėti papildomus aprašymus prie klausimų') }}
                      </p>
                    </div>
                    <Switch v-model="showDescriptions" />
                  </div>
                </div>

                <!-- Text area input -->
                <div v-else-if="showQuestionInputInTextArea" class="space-y-4">
                  <div class="flex items-center justify-between">
                    <h4 class="font-medium">
                      {{ $t('Masinis klausimų įvedimas') }}
                    </h4>
                    <Button type="button" size="sm" variant="outline" @click="showQuestionInputInTextArea = false; agendaInputMode = null">
                      <ArrowLeft class="mr-2 h-3 w-3" />
                      {{ $t('Grįžti') }}
                    </Button>
                  </div>

                  <FormControl>
                    <Textarea v-model="questionInputInTextArea"
                      :placeholder="$page.props.app.locale === 'lt' ? 'Kiekvienas klausimas turi būti iš naujos eilutės, pvz.:\n\n1. Praėjusio posėdžio protokolo tvirtinimas\n2. Einamųjų reikalų aptarimas\n3. Ateities planų pristatymas\n4. Kiti klausimai' : 'Every question must begin from new line, e.g.\n\n1. Previous meeting protocol approval\n2. Current affairs discussion\n3. Future plans presentation\n4. Other questions'"
                      class="w-full font-mono text-sm" :rows="8" />
                  </FormControl>

                  <div class="flex items-center justify-between">
                    <div class="text-xs text-muted-foreground">
                      {{ $t('Aptikta') }}: {{ getLineCount(questionInputInTextArea) }} {{ $t('eilutės') }}
                    </div>
                    <Button type="button" @click="handleQuestionsFromTextArea">
                      <Upload class="mr-2 h-4 w-4" />
                      {{ $t('Įkelti klausimus') }}
                    </Button>
                  </div>
                </div>

                <!-- 3-button selection for starting agenda (only in create mode) -->
                <div v-if="props.mode === 'create' && fields.length === 0 && !showQuestionInputInTextArea && !agendaInputMode"
                  class="text-center py-8 space-y-6">
                  <div>
                    <component :is="IconsRegular.AGENDA_ITEM" class="h-10 w-10 mx-auto text-muted-foreground/50 mb-3" />
                    <h4 class="font-medium text-lg mb-2">{{ $t('Kaip norite sukurti darbotvarkę?') }}</h4>
                    <p class="text-sm text-muted-foreground">
                      {{ $t('Pasirinkite vieną iš būdų arba praleiskite šį žingsnį') }}
                    </p>
                  </div>

                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 max-w-3xl mx-auto">
                    <!-- Option 1: From previous meeting (disabled if no templates) -->
                    <button type="button"
                      class="flex flex-col items-center gap-3 p-6 rounded-lg border-2 border-dashed transition-all group"
                      :class="recentTemplates.length > 0 
                        ? 'hover:border-primary hover:bg-muted/50 cursor-pointer' 
                        : 'opacity-50 cursor-not-allowed'"
                      :disabled="recentTemplates.length === 0"
                      @click="recentTemplates.length > 0 && showPreviousMeetingSelector()">
                      <History class="h-8 w-8 text-muted-foreground transition-colors" :class="recentTemplates.length > 0 && 'group-hover:text-primary'" />
                      <div class="text-center">
                        <p class="font-medium">{{ $t('Naudoti ankstesnį') }}</p>
                        <p class="text-xs text-muted-foreground mt-1">
                          {{ recentTemplates.length > 0 
                            ? $t('Pasirinkti iš praėjusių posėdžių') 
                            : $t('Nėra ankstesnių posėdžių') }}
                        </p>
                      </div>
                    </button>

                    <!-- Option 2: Add one-by-one -->
                    <button type="button"
                      class="flex flex-col items-center gap-3 p-6 rounded-lg border-2 border-dashed hover:border-primary hover:bg-muted/50 transition-all group"
                      @click="startOneByOne">
                      <PlusIcon class="h-8 w-8 text-muted-foreground group-hover:text-primary transition-colors" />
                      <div class="text-center">
                        <p class="font-medium">{{ $t('Pridėti po vieną') }}</p>
                        <p class="text-xs text-muted-foreground mt-1">{{ $t('Įvesti klausimus paeiliui') }}</p>
                      </div>
                    </button>

                    <!-- Option 3: From text -->
                    <button type="button"
                      class="flex flex-col items-center gap-3 p-6 rounded-lg border-2 border-dashed hover:border-primary hover:bg-muted/50 transition-all group"
                      @click="showQuestionInputInTextArea = true; agendaInputMode = 'text'">
                      <DocumentIcon class="h-8 w-8 text-muted-foreground group-hover:text-primary transition-colors" />
                      <div class="text-center">
                        <p class="font-medium">{{ $t('Įkelti iš teksto') }}</p>
                        <p class="text-xs text-muted-foreground mt-1">{{ $t('Įklijuoti visus klausimus') }}</p>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Previous meeting selector dialog -->
                <div v-if="showPreviousMeetingList && recentTemplates.length > 0" class="space-y-4">
                  <div class="flex items-center justify-between">
                    <h4 class="font-medium">{{ $t('Pasirinkite ankstesnį posėdį') }}</h4>
                    <Button type="button" size="sm" variant="outline" @click="showPreviousMeetingList = false; agendaInputMode = null">
                      <ArrowLeft class="mr-2 h-3 w-3" />
                      {{ $t('Grįžti') }}
                    </Button>
                  </div>

                  <div class="space-y-2">
                    <button v-for="template in recentTemplates" :key="template.id" type="button"
                      class="w-full flex items-center gap-3 p-4 rounded-lg border hover:border-primary hover:bg-muted/50 transition-all text-left"
                      @click="loadFromPreviousMeeting(template.id)">
                      <History class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                      <div class="flex-1 min-w-0">
                        <p class="font-medium truncate">{{ template.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ template.agendaItems.length }} {{ $t('klausimai') }}</p>
                      </div>
                      <ArrowRight class="h-4 w-4 text-muted-foreground" />
                    </button>
                  </div>
                </div>
              </div>
              <FormMessage />
            </FormItem>
          </FieldArray>
        </FormField>

        <!-- Action buttons -->
        <div class="flex items-center justify-between pt-4 border-t">
          <div class="flex items-center gap-2">
            <Button v-if="currentFieldCount > 1 || (currentFieldCount === 1 && hasNonEmptyItems)" type="button" variant="ghost" size="sm" @click="clearAllItems">
              <Trash2 class="mr-2 h-3 w-3" />
              {{ $t('Šalinti visus') }}
            </Button>
          </div>

          <div class="flex items-center gap-2">
            <TooltipProvider v-if="props.showSkipButton && currentFieldCount === 0">
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button type="button" variant="outline" @click="skipAgenda">
                    {{ $t('Praleisti darbotvarkę') }}
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  {{ $t('Darbotvarkę galėsite pridėti vėliau') }}
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>

            <Button type="submit" :disabled="loading || (props.mode === 'add' && currentFieldCount === 0)">
              <span v-if="loading">
                <Loader2 class="mr-2 h-4 w-4 animate-spin" />
                {{ $t('Kuriama...') }}
              </span>
              <span class="inline-flex items-center" v-else>
                {{ props.submitLabel || $t("Sukurti susitikimą") }}
                <CheckCircle class="ml-2 h-4 w-4" />
              </span>
            </Button>
          </div>
        </div>
      </div>
    </Form>
  </div>
</template>

<script setup lang="ts">
import { ref, useTemplateRef, computed, onMounted, onUnmounted, watch, nextTick } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { Form, FieldArray } from "vee-validate";
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';
import {
  Trash as TrashIcon,
  Plus as PlusIcon,
  FileText as DocumentIcon,
  ArrowRight,
  History,
  GripVertical,
  Copy,
  ArrowLeft,
  Upload,
  Trash2,
  CheckCircle,
  Loader2,
  Users as UsersIcon,
} from "lucide-vue-next";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import IconsFilled from "@/Types/Icons/filled";
import IconsRegular from "@/Types/Icons/regular";
import ModelChip from "@/Components/Tag/ModelChip.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";
import { useMeetingTemplates } from "@/Composables/useMeetingTemplates";
// Import Shadcn components
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import {
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from "@/Components/ui/form";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { Switch } from "@/Components/ui/switch";
import { Label } from "@/Components/ui/label";

const emit = defineEmits<(e: "submit", data: Record<string, any>) => void>();

const props = withDefaults(defineProps<{
  loading: boolean;
  institutionId?: string;
  agendaItems?: Array<{ title: string; description?: string; order: number }>;
  recentMeetings?: Array<{ id: string; title: string; start_time: string; institution_id: string; institution_name: string; agenda_items: { title: string }[] }>;
  /** 'create' shows template selection, 'add' starts directly in one-by-one mode */
  mode?: 'create' | 'add';
  /** Custom label for submit button */
  submitLabel?: string;
  /** Whether to show skip button */
  showSkipButton?: boolean;
  /** Whether to show the hint/suggestion alert */
  showHint?: boolean;
}>(), {
  mode: 'create',
  submitLabel: undefined,
  showSkipButton: true,
  showHint: true,
});

// Debug logging
console.log('[AgendaItemsForm] Props received:', {
  institutionId: props.institutionId,
  recentMeetings: props.recentMeetings,
  mode: props.mode,
})

// Composables
const {
  templates,
  getTemplatesForInstitution,
  applyTemplate
} = useMeetingTemplates(props.recentMeetings);

const agendaItemField = useTemplateRef<typeof FormField>("agendaItemField");
const agendaForm = useTemplateRef<typeof Form>("agendaForm");

// Local state
const showAlert = ref(true);
const showQuestionInputInTextArea = ref(false);
const showDescriptions = ref(false);
const questionInputInTextArea = ref("");
const draggedIndex = ref<number | null>(null);
const agendaInputMode = ref<'previous' | 'one-by-one' | 'text' | null>(null);
const showPreviousMeetingList = ref(false);
// Track brought_by_students flag for each item by index
const broughtByStudentsFlags = ref<Record<number, boolean>>({});

// Computed properties
const recentTemplates = computed(() => 
  getTemplatesForInstitution(props.institutionId).slice(0, 5)
);

const currentAgendaItems = computed(() =>
  props.agendaItems?.map(item => item.title) || []
);

// Track the current field count for use outside FieldArray scope
const currentFieldCount = ref(0);

// Check if there are any non-empty items (for showing clear button)
const hasNonEmptyItems = computed(() => {
  const fieldValue = agendaItemField.value?.value;
  if (!Array.isArray(fieldValue)) return false;
  return fieldValue.some((item: string) => item && item.trim() !== '');
});

// Define validation schema using zod
// Validation is lenient during input - we filter out empty items on submit
const schema = toTypedSchema(z.object({
  agendaItemTitles: z.array(z.string()),
}));

// Set initial values from props
const initialValues = computed(() => ({
  agendaItemTitles: currentAgendaItems.value,
}));

// Form interaction methods
const addNewItem = (index: number, push: Function) => {
  push('');
  // Focus on the new item after it's added
  setTimeout(() => {
    const inputs = document.querySelectorAll('input[placeholder*="Darbotvarkės klausimas"]');
    const newInput = inputs[index] as HTMLInputElement;
    newInput?.focus();
  }, 100);
};

const handleBackspace = (index: number, value: string, remove: Function) => {
  if (value === '' && index > 0) {
    remove(index);
    // Focus on previous item
    setTimeout(() => {
      const inputs = document.querySelectorAll('input[placeholder*="Darbotvarkės klausimas"]');
      const prevInput = inputs[index - 1] as HTMLInputElement;
      prevInput?.focus();
    }, 100);
  }
};

const duplicateItem = (index: number, value: string, push: Function) => {
  push(value);
};

const clearAllItems = () => {
  if (agendaItemField.value) {
    agendaItemField.value.setValue([]);
    agendaInputMode.value = null;
  }
};

// Drag and drop methods
const handleDragStart = (index: number) => {
  draggedIndex.value = index;
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
};

const handleDrop = (targetIndex: number, move: Function) => {
  if (draggedIndex.value !== null && draggedIndex.value !== targetIndex) {
    move(draggedIndex.value, targetIndex);
  }
  draggedIndex.value = null;
};

// Text area methods
const getLineCount = (text: string): number => {
  return text.split('\n').filter(line => line.trim() !== '').length;
};

const handleQuestionsFromTextArea = () => {
  const questions = questionInputInTextArea.value
    .split('\n')
    .map(q => q.trim().replace(/^\d+\.\s*/, '')) // Remove numbering like "1. "
    .filter(q => q !== '');

  if (questions.length > 0) {
    agendaItemField.value?.setValue(questions);
    showQuestionInputInTextArea.value = false;
    agendaInputMode.value = 'one-by-one'; // Switch to one-by-one view after parsing
  }
};

// Utility methods
const skipAgenda = () => {
  // Submit with empty agenda
  const formData = {
    agendaItemTitles: [],
    broughtByStudentsFlags: [],
  };

  emit("submit", formData);
};

// New methods for 3-button flow
const showPreviousMeetingSelector = () => {
  agendaInputMode.value = 'previous';
  showPreviousMeetingList.value = true;
};

const startOneByOne = () => {
  agendaInputMode.value = 'one-by-one';
  // Start with one empty item
  if (agendaItemField.value) {
    agendaItemField.value.setValue(['']);
  }
};

const loadFromPreviousMeeting = (templateId: string) => {
  const template = templates.value.find(t => t.id === templateId);
  if (template && agendaItemField.value) {
    agendaItemField.value.setValue(template.agendaItems);
    showPreviousMeetingList.value = false;
    agendaInputMode.value = 'one-by-one'; // Switch to one-by-one view after loading
  }
};

// Submit form handler
const onSubmit = (values: any) => {
  const filteredItems = values.agendaItemTitles
    .map((title: string, index: number) => ({ title, index }))
    .filter((item: { title: string }) => item.title.trim() !== '');
  
  const formData = {
    agendaItemTitles: filteredItems.map((item: { title: string; index: number }) => item.title),
    broughtByStudentsFlags: filteredItems.map((item: { title: string; index: number }) => 
      broughtByStudentsFlags.value[item.index] || false
    ),
  };

  emit("submit", formData);
};

// Watch for field count changes
watch(() => currentAgendaItems.value, (newItems) => {
  currentFieldCount.value = newItems.length;
}, { immediate: true });

// Watch for form field value changes to update field count
watch(() => agendaItemField.value?.value, (newValue) => {
  if (Array.isArray(newValue)) {
    currentFieldCount.value = newValue.length;
  }
}, { deep: true });

// Auto-set display mode when component receives existing agenda items
watch(() => currentAgendaItems.value, (newItems) => {
  // Only auto-set the mode if:
  // 1. Items exist (user is editing, not creating from scratch)
  // 2. Mode hasn't been explicitly set yet (preserve user's choice if they've made one)
  if (newItems.length > 0 && agendaInputMode.value === null) {
    agendaInputMode.value = 'one-by-one';

    // Explicitly synchronize VeeValidate form state
    nextTick(() => {
      agendaForm.value?.setValues({ agendaItemTitles: newItems });
    });
  }
}, { immediate: true });

// Lifecycle
onMounted(() => {
  // In 'add' mode, start directly in one-by-one mode with an empty item
  if (props.mode === 'add' && currentAgendaItems.value.length === 0) {
    startOneByOne();
  }
});
</script>

<style scoped>
/* Transition styles for drag and drop */
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-10px);
}

.list-leave-active {
  position: absolute;
  right: 0;
  left: 0;
}
</style>
