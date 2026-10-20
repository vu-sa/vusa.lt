<template>
  <FormPage
    :title="isCreate ? $t('responsibilities.form.create') : (form.title[activeLocale] || form.title.lt || $t('responsibilities.entity.singular'))"
    :bar-title="isCreate ? $t('responsibilities.form.create') : (responsibility.title?.lt ?? '')"
    :entity-type="ModelEnum.ROLE"
    :back-href="isCreate ? route('responsibilities.index') : route('responsibilities.show', responsibility.id!)"
    :back-label="$t('shell.sections.roles')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    max-width="2xl"
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <p class="text-xs text-muted-foreground">
      {{ $t('forms.required_note') }}
    </p>

    <FormSection :title="$t('responsibilities.form.what')" :description="$t('responsibilities.form.what_description')">
      <FormFieldWrapper
        id="title"
        :label="`${$t('forms.fields.title')} (${activeLocale.toUpperCase()})`"
        :hint="$t('responsibilities.form.title_hint')"
        :required="activeLocale === 'lt'"
        :error="form.errors[`title.${activeLocale}`]"
      >
        <Input id="title" v-model="form.title[activeLocale]" :class="fieldSurfaceClass" :placeholder="$t('responsibilities.form.title_example')" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="description"
        :label="`${$t('forms.fields.description')} (${activeLocale.toUpperCase()})`"
        :hint="$t('responsibilities.form.description_hint')"
        :error="form.errors[`description.${activeLocale}`]"
      >
        <Textarea id="description" v-model="form.description[activeLocale]" rows="4" :class="fieldSurfaceClass" />
      </FormFieldWrapper>

      <p v-if="responsibility.purpose_label" class="text-xs text-muted-foreground">
        {{ $t('responsibilities.form.purpose_note', { purpose: responsibility.purpose_label }) }}
      </p>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useForm, type InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from './FormFieldWrapper.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { FormSection } from '@/Components/Patterns';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { ModelEnum } from '@/Types/enums';

type Translations = { lt: string; en: string };

export interface ResponsibilityFormData {
  title: Translations;
  description: Translations;
}

const { responsibility, rememberKey } = defineProps<{
  responsibility: { id?: string; title?: Partial<Translations> | null; description?: Partial<Translations> | null; purpose_label?: string | null };
  rememberKey?: string;
}>();

const emit = defineEmits<(event: 'submit:form', form: InertiaForm<ResponsibilityFormData>) => void>();

const isCreate = !responsibility.id;
const activeLocale = ref<'lt' | 'en'>('lt');

const fieldIds = { 'title.lt': 'title', 'title.en': 'title', 'description.lt': 'description', 'description.en': 'description' };

const initial: ResponsibilityFormData = {
  title: { lt: '', en: '', ...(responsibility.title ?? {}) } as Translations,
  description: { lt: '', en: '', ...(responsibility.description ?? {}) } as Translations,
};

const form = rememberKey ? useForm<ResponsibilityFormData>(rememberKey, initial) : useForm<ResponsibilityFormData>(initial);
</script>
