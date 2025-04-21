<template>
  <div>
    <Form v-slot="{ values, }" :validation-schema="schema" @submit="onSubmit" :initial-values="initialValues">
      <FadeTransition>
        <SuggestionAlert :show-alert="showAlert" @alert-closed="showAlert = false">
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
              posėdyje.
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
              meeting.
            </p>
          </template>
        </SuggestionAlert>
      </FadeTransition>

      <div class="space-y-6">
        <FormField ref="agendaItemField" v-slot="{ componentField, setValue }" name="agendaItemTitles">
          <FieldArray v-slot="{ fields, remove, push }" name="agendaItemTitles">
            <FormItem>
              <FormLabel class="mb-2 inline-flex items-center gap-1">
                <component :is="IconsFilled.AGENDA_ITEM" class="h-4 w-4" />
                {{ $t("Darbotvarkės klausimai") }}
              </FormLabel>

              <div class="flex flex-col gap-4">
                <!-- Individual items input -->
                <div v-if="!showQuestionInputInTextArea" class="space-y-2">
                  <div v-for="(field, index) in fields" :key="field.key" class="flex items-center gap-2">
                    <span class="ml-1 w-7 flex-shrink-0">{{ `${index + 1}.` }}</span>
                    <FormControl>
                      <Input v-model="field.value" :placeholder="`Darbotvarkės klausimas nr. ${index + 1}`"
                        @keydown.enter.prevent />
                    </FormControl>
                    <Button type="button" variant="ghost" size="icon" class="h-8 w-8" @click="remove(index)">
                      <TrashIcon class="h-4 w-4" />
                    </Button>
                  </div>

                  <Button type="button" variant="outline" size="sm" class="mt-2" @click="push('')">
                    <PlusIcon class="mr-2 h-4 w-4" />
                    {{ $t("Pridėti po vieną") }}
                  </Button>
                </div>

                <!-- Text area input -->
                <div v-else class="flex flex-col items-start gap-2">
                  <FormControl>
                    <Textarea v-model="questionInputInTextArea"
                      :placeholder="$page.props.app.locale === 'lt' ? 'Kiekvienas klausimas turi būti iš naujos eilutės, pvz.:\n\nKlausimas nr. 1\nKlausimas nr. 2' : 'Every question must begin from new line, e.g.\n\nQuestion no. 1\nQuestion no. 2'"
                      class="w-full" :rows="5" />
                  </FormControl>

                  <div class="flex w-full justify-between gap-2">
                    <Button type="button" size="sm" variant="outline" @click="showQuestionInputInTextArea = false">
                      {{ $t('Grįžti') }}
                    </Button>
                    <Button type="button" size="sm" @click="handleQuestionsFromTextArea">
                      {{ $t('Įkelti iš teksto') }}
                    </Button>
                  </div>
                </div>

                <!-- Show text area option -->
                <Button v-if="fields.length === 0 && !showQuestionInputInTextArea" type="button" variant="outline"
                  @click="showQuestionInputInTextArea = true">
                  <DocumentIcon class="mr-2 h-4 w-4" />
                  {{ $t('Įkelti iš teksto') }}
                </Button>
              </div>
              <FormMessage />
            </FormItem>
          </FieldArray>
        </FormField>

        <Button type="submit" :loading="loading">
          {{ $t("forms.submit") }}
        </Button>
      </div>
    </Form>
  </div>
</template>

<script setup lang="ts">
import { ref, inject, useTemplateRef } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { Form, FieldArray } from "vee-validate";
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import IconsFilled from "@/Types/Icons/filled";
import IconsRegular from "@/Types/Icons/regular";
import ModelChip from "@/Components/Tag/ModelChip.vue";
import SuggestionAlert from "@/Components/Alerts/SuggestionAlert.vue";

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
import {
  Trash as TrashIcon,
  Plus as PlusIcon,
  FileText as DocumentIcon,
} from "lucide-vue-next";

const emit = defineEmits<{
  (e: "submit", data: Record<string, any>): void;
}>();

const props = defineProps<{
  loading: boolean;
}>();

// Access shared form state from parent component
const formState = inject('meetingFormState');
const agendaItemField = useTemplateRef<typeof FormField>("agendaItemField");

const showAlert = ref(true);
const showQuestionInputInTextArea = ref(false);
const questionInputInTextArea = ref("");

// Define validation schema using zod
const schema = toTypedSchema(z.object({
  agendaItemTitles: z.array(z.string())
    // .min(1, { message: $t("Pasirink (arba įrašyk) bent vieną klausimą") })
    .refine(items => {
      // Check that no items are empty
      return !items.some(item => !item || item.trim() === '');
    }, { message: $t("Klausimas negali būti tuščias") }),
}));

// Set initial values from shared state
const initialValues = {
  agendaItemTitles: formState?.agendaItemsData.agendaItemTitles || [],
};

// Process items from textarea
const handleQuestionsFromTextArea = () => {
  const questions = questionInputInTextArea.value
    .split('\n')
    .map(q => q.trim())
    .filter(q => q !== '');

  if (questions.length > 0) {
    // Apply the questions from textarea to the form's agendaItemTitles field
    agendaItemField.value?.setValue(questions);
    showQuestionInputInTextArea.value = false;
  }
};

// Submit form handler
const onSubmit = (values) => {
  const formData = {
    agendaItemTitles: values.agendaItemTitles.filter(item => item.trim() !== ''),
  };

  // Update shared state
  if (formState) {
    formState.agendaItemsData = formData;
  }
  emit("submit", formData);
};
</script>
