<template>
  <FormPage
    :mode="canEdit ? 'edit' : 'view'"
    :title="$t('settings.pages.season.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleFormSubmit"
  >
    <FormSection :title="$t('settings.pages.season.description')">
      <FormFieldWrapper id="enabled" :label="$t('season.settings.enabled')" :error="form.errors.enabled">
        <Switch id="enabled" v-model="form.enabled" :disabled="!canEdit" />
      </FormFieldWrapper>
      <FormFieldWrapper
        v-for="field in dateFields"
        :id="field"
        :key="field"
        :label="$t(`season.settings.${field}`)"
        :error="form.errors[field]"
        required
      >
        <Input :id="field" v-model="form[field]" variant="surface" :disabled="!canEdit" type="text" maxlength="5" placeholder="MM-DD" />
      </FormFieldWrapper>
      <FormFieldWrapper
        v-for="field in timingFields"
        :id="field"
        :key="field"
        :label="$t(`season.settings.${field}`)"
        :error="form.errors[field]"
        required
      >
        <Input :id="field" v-model.number="form[field]" variant="surface" :disabled="!canEdit" type="number" min="0" />
      </FormFieldWrapper>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';

const props = defineProps<{
  canEdit: boolean;
  enabled: boolean;
  starts_on: string;
  tree_locks_on: string;
  ends_on: string;
  ornament_adjust_minutes: number;
  ornament_cooldown_hours: number;
}>();

const dateFields = ['starts_on', 'tree_locks_on', 'ends_on'] as const;
const timingFields = ['ornament_adjust_minutes', 'ornament_cooldown_hours'] as const;
const form = useForm({
  enabled: props.enabled,
  starts_on: props.starts_on,
  tree_locks_on: props.tree_locks_on,
  ends_on: props.ends_on,
  ornament_adjust_minutes: props.ornament_adjust_minutes,
  ornament_cooldown_hours: props.ornament_cooldown_hours,
});

function handleFormSubmit(): void {
  form.post(route('settings.season.update'));
}
</script>
