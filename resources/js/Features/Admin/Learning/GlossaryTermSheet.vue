<template>
  <SheetForm
    v-model:open="open"
    required-note
    :title="term ? $t('learning_library.glossary.edit_term') : $t('learning_library.glossary.new_term')"
    :description="$t('learning_library.glossary.sheet_description')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <FormFieldWrapper
      id="glossary-term-input"
      :label="$t('learning_library.glossary.term')"
      :error="form.errors['term.lt'] || form.errors['term.en'] || form.errors.term"
      required
    >
      <MultiLocaleInput id="glossary-term-input" v-model:input="form.term" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="glossary-abbreviation-input"
      :label="$t('learning_library.glossary.abbreviation')"
      :hint="$t('learning_library.glossary.abbreviation_hint')"
      :error="form.errors['abbreviation.lt'] || form.errors['abbreviation.en']"
    >
      <MultiLocaleInput id="glossary-abbreviation-input" v-model:input="form.abbreviation" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="glossary-definition-input"
      :label="$t('learning_library.glossary.definition')"
      :error="form.errors['definition.lt'] || form.errors['definition.en'] || form.errors.definition"
      required
    >
      <MultiLocaleInput id="glossary-definition-input" v-model:input="form.definition" input-type="textarea" />
    </FormFieldWrapper>

    <template v-if="$slots['danger-zone']" #danger-zone>
      <slot name="danger-zone" />
    </template>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { toRaw, watch } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import { SheetForm } from '@/Components/Patterns';
import type { GlossaryTermData } from '@/Types/learning';

type TermFields = Pick<GlossaryTermData, 'id' | 'term' | 'abbreviation' | 'definition' | 'translations'>;

interface GlossaryFormState {
  term: { lt: string; en: string };
  abbreviation: { lt: string; en: string };
  definition: { lt: string; en: string };
}

const props = defineProps<{ term: TermFields | null }>();
const open = defineModel<boolean>('open', { required: true });

const fieldsFor = (term: TermFields | null): GlossaryFormState => term
  ? {
      term: structuredClone(toRaw(term.translations?.term ?? { lt: term.term, en: '' })),
      abbreviation: structuredClone(toRaw(term.translations?.abbreviation ?? { lt: term.abbreviation ?? '', en: '' })),
      definition: structuredClone(toRaw(term.translations?.definition ?? { lt: term.definition, en: '' })),
    }
  : { term: { lt: '', en: '' }, abbreviation: { lt: '', en: '' }, definition: { lt: '', en: '' } };

const form = useForm<GlossaryFormState>(fieldsFor(props.term));

watch(open, (isOpen) => {
  if (isOpen) {
    form.defaults(fieldsFor(props.term));
    form.reset();
    form.clearErrors();
  }
});

function reset(): void {
  form.reset();
  form.clearErrors();
}

function submit(): void {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      open.value = false;
    },
  };
  if (props.term) {
    form.put(route('glossaryTerms.update', props.term.id), options);
  }
  else {
    form.post(route('glossaryTerms.store'), options);
  }
}
</script>
