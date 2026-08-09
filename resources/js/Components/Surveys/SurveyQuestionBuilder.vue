<template>
  <div class="space-y-4">
    <div v-if="!editable" class="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
      {{ $t('surveys.helpers.locked') }}
    </div>

    <div v-if="editable" class="flex flex-wrap items-center gap-2">
      <Select v-model="templateToAdd">
        <SelectTrigger class="w-72">
          <SelectValue :placeholder="$t('surveys.actions.add_from_template')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="template in questionTemplates" :key="template.id" :value="template.id">
            {{ template.title }} — {{ template.question?.lt }}
          </SelectItem>
        </SelectContent>
      </Select>

      <Button type="button" variant="secondary" :disabled="!templateToAdd" @click="addFromTemplate">
        <Plus class="mr-1 size-4" />
        {{ $t('surveys.actions.add_from_template') }}
      </Button>

      <Button type="button" variant="outline" @click="addCustomQuestion">
        <Plus class="mr-1 size-4" />
        {{ $t('surveys.actions.add_question') }}
      </Button>
    </div>

    <p v-if="questions.length === 0" class="text-sm text-zinc-500 dark:text-zinc-400">
      {{ $t('surveys.helpers.no_questions_yet') }}
    </p>

    <div
      v-for="(question, index) in questions"
      :key="question.key"
      class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"
    >
      <div class="mb-3 flex items-start justify-between gap-2">
        <div class="flex items-center gap-2">
          <Badge variant="secondary">{{ index + 1 }}</Badge>
          <Badge v-if="question.survey_question_template_id" variant="outline">
            {{ $t('surveys.question_bank') }}
          </Badge>
        </div>

        <div v-if="editable" class="flex items-center gap-1">
          <Button type="button" variant="ghost" size="icon" :disabled="index === 0" @click="move(index, -1)">
            <ArrowUp class="size-4" />
          </Button>
          <Button
            type="button"
            variant="ghost"
            size="icon"
            :disabled="index === questions.length - 1"
            @click="move(index, 1)"
          >
            <ArrowDown class="size-4" />
          </Button>
          <Button type="button" variant="ghost" size="icon" @click="remove(index)">
            <Trash2 class="size-4 text-red-600" />
          </Button>
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <div>
          <Label :for="`title-${question.key}`">{{ $t('surveys.fields.title') }}</Label>
          <Input :id="`title-${question.key}`" v-model="question.title" :disabled="!editable" />
          <p class="mt-1 text-xs text-zinc-500">{{ $t('surveys.helpers.question_code') }}</p>
          <p v-if="errorFor(index, 'title')" class="mt-1 text-xs text-red-600">{{ errorFor(index, 'title') }}</p>
        </div>

        <div>
          <Label :for="`type-${question.key}`">{{ $t('surveys.fields.type') }}</Label>
          <Select v-model="question.type" :disabled="!editable">
            <SelectTrigger :id="`type-${question.key}`">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="type in questionTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div class="md:col-span-2">
          <Label>{{ $t('surveys.fields.group_name') }}</Label>
          <MultiLocaleInput v-model:input="question.group_name" :disabled="!editable" />
        </div>

        <div class="md:col-span-2">
          <Label>{{ $t('surveys.fields.question') }}</Label>
          <MultiLocaleInput v-model:input="question.question" :disabled="!editable" />
          <p v-if="errorFor(index, 'question.lt')" class="mt-1 text-xs text-red-600">
            {{ errorFor(index, 'question.lt') }}
          </p>
        </div>

        <div class="md:col-span-2">
          <Label>{{ $t('surveys.fields.help') }}</Label>
          <MultiLocaleInput v-model:input="question.help" :disabled="!editable" />
        </div>

        <div class="flex items-center gap-2 md:col-span-2">
          <Switch :id="`required-${question.key}`" v-model="question.is_required" :disabled="!editable" />
          <Label :for="`required-${question.key}`">{{ $t('surveys.fields.is_required') }}</Label>
        </div>
      </div>

      <!-- Only choice questions carry author-defined options; a rating scale is rendered by LimeSurvey. -->
      <div v-if="typeHasOptions(question.type)" class="mt-4 space-y-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
        <Label>{{ $t('surveys.fields.options') }}</Label>

        <div v-for="(option, optionIndex) in question.options" :key="optionIndex" class="flex items-start gap-2">
          <Input
            v-model="option.code"
            class="w-28"
            :placeholder="$t('surveys.fields.option_code')"
            :disabled="!editable"
          />
          <div class="flex-1">
            <MultiLocaleInput v-model:input="option.label" :disabled="!editable" />
          </div>
          <Button
            v-if="editable"
            type="button"
            variant="ghost"
            size="icon"
            @click="question.options.splice(optionIndex, 1)"
          >
            <Trash2 class="size-4 text-red-600" />
          </Button>
        </div>

        <p v-if="errorFor(index, 'options')" class="text-xs text-red-600">{{ errorFor(index, 'options') }}</p>

        <Button v-if="editable" type="button" variant="outline" size="sm" @click="addOption(question)">
          <Plus class="mr-1 size-4" />
          {{ $t('surveys.actions.add_option') }}
        </Button>
      </div>
    </div>

    <p v-if="form.errors.questions" class="text-sm text-red-600">{{ form.errors.questions }}</p>

    <Button v-if="editable" type="button" :disabled="form.processing" @click="submit">
      <Save class="mr-1 size-4" />
      {{ $t('surveys.actions.save_questions') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Plus, Save, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

type Translatable = Record<string, string>;

interface QuestionOption {
  code: string;
  label: Translatable;
}

interface BuilderQuestion {
  key: string;
  survey_question_template_id: string | null;
  title: string;
  type: string;
  group_name: Translatable;
  question: Translatable;
  help: Translatable;
  is_required: boolean;
  options: QuestionOption[];
}

interface QuestionType {
  value: string;
  label: string;
  hasOptions: boolean;
}

const props = defineProps<{
  surveyId: string;
  initialQuestions: Record<string, any>[];
  questionTypes: QuestionType[];
  questionTemplates: Record<string, any>[];
  editable: boolean;
}>();

/**
 * Rows need a stable identity that survives reordering. Question ids are absent for rows
 * that have never been saved, so a local key is generated instead of leaning on the index.
 */
let keyCounter = 0;
const nextKey = () => `q-${keyCounter++}`;

const emptyTranslatable = (): Translatable => ({ lt: '', en: '' });

const toBuilderQuestion = (source: Record<string, any>): BuilderQuestion => ({
  key: nextKey(),
  survey_question_template_id: source.survey_question_template_id ?? null,
  title: source.title ?? '',
  type: source.type ?? props.questionTypes[0]?.value ?? 'T',
  group_name: { ...emptyTranslatable(), ...(source.group_name ?? {}) },
  question: { ...emptyTranslatable(), ...(source.question ?? {}) },
  help: { ...emptyTranslatable(), ...(source.help ?? {}) },
  is_required: Boolean(source.is_required),
  options: (source.options ?? []).map((option: Record<string, any>) => ({
    code: option.code ?? '',
    label: { ...emptyTranslatable(), ...(option.label ?? {}) },
  })),
});

const questions = ref<BuilderQuestion[]>(props.initialQuestions.map(toBuilderQuestion));
const templateToAdd = ref<string | null>(null);

const form = useForm('SurveyQuestions', { questions: [] as unknown[] });

const typeHasOptions = (type: string) =>
  props.questionTypes.find(candidate => candidate.value === type)?.hasOptions ?? false;

/** Server errors arrive keyed as questions.<index>.<field>. */
const errorFor = (index: number, field: string): string | undefined =>
  (form.errors as Record<string, string>)[`questions.${index}.${field}`];

const addCustomQuestion = () => {
  questions.value.push(toBuilderQuestion({
    title: `Q${questions.value.length + 1}`,
    group_name: { lt: $t('surveys.default_group'), en: $t('surveys.default_group') },
  }));
};

const addFromTemplate = () => {
  const template = props.questionTemplates.find(candidate => candidate.id === templateToAdd.value);

  if (!template) {
    return;
  }

  // Copied, not linked: editing the bank later must not rewrite an existing survey.
  questions.value.push(toBuilderQuestion({
    ...template,
    survey_question_template_id: template.id,
  }));

  templateToAdd.value = null;
};

const addOption = (question: BuilderQuestion) => {
  question.options.push({ code: `A${question.options.length + 1}`, label: emptyTranslatable() });
};

const remove = (index: number) => {
  questions.value.splice(index, 1);
};

const move = (index: number, delta: number) => {
  const target = index + delta;

  if (target < 0 || target >= questions.value.length) {
    return;
  }

  const [moved] = questions.value.splice(index, 1);
  questions.value.splice(target, 0, moved);
};

const submit = () => {
  form.questions = questions.value.map(question => ({
    survey_question_template_id: question.survey_question_template_id,
    title: question.title,
    type: question.type,
    group_name: question.group_name,
    question: question.question,
    help: question.help,
    is_required: question.is_required,
    options: typeHasOptions(question.type) ? question.options : [],
  }));

  form.put(route('surveys.syncQuestions', props.surveyId), { preserveScroll: true });
};
</script>
