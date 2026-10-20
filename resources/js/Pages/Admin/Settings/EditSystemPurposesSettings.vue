<template>
  <FormPage
    :mode="canEdit ? 'edit' : 'view'"
    :title="$t('settings.pages.purposes.title')"
    :lead="$t('settings.purposes.intro')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="form.post(route('settings.purposes.update'), { preserveScroll: true })"
  >
    <FormSection :title="$t('settings.purposes.responsibilities_title')" :description="$t('settings.purposes.responsibilities_description')">
      <FormFieldWrapper
        v-for="purpose in purposes"
        :key="purpose.key"
        :id="`purpose_${purpose.key}`"
        :label="purpose.label"
        :hint="purpose.drives"
        :error="form.errors[`purposes.${purpose.key}`]"
      >
        <NativeSelect
          :id="`purpose_${purpose.key}`"
          v-model="form.purposes[purpose.key]"
          :options="responsibilityOptions"
          :placeholder="$t('settings.purposes.responsibility_none')"
          placeholder-selectable
        />
      </FormFieldWrapper>
    </FormSection>

    <FormSection :title="$t('settings.purposes.duty_types_title')" :description="$t('settings.purposes.duty_types_description')">
      <FormFieldWrapper
        v-for="part in dutyTypes"
        :key="part.key"
        :id="`duty_type_${part.key}`"
        :label="part.label"
        :hint="fallbackHint(part) ?? part.drives"
        :error="form.errors[`duty_types.${part.key}`]"
      >
        <NativeSelect
          :id="`duty_type_${part.key}`"
          v-model="form.duty_types[part.key]"
          :options="dutyTypeSelectOptions"
          :placeholder="$t('settings.purposes.responsibility_none')"
          placeholder-selectable
        />
      </FormFieldWrapper>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { NativeSelect } from '@/Components/ui/native-select';

interface Purpose { key: string; label: string; drives: string; responsibility_id: string | null }
interface DutyTypePart { key: string; label: string; drives: string; type_id: number | null; effective_type_id: number | null; fallback_slug: string }

const props = defineProps<{
  canEdit: boolean;
  purposes: Purpose[];
  responsibilities: { id: string; title: string }[];
  dutyTypes: DutyTypePart[];
  dutyTypeOptions: { id: number; title: string }[];
}>();

const responsibilityOptions = computed(() => props.responsibilities.map(item => ({ value: item.id, label: item.title })));
const dutyTypeSelectOptions = computed(() => props.dutyTypeOptions.map(type => ({ value: type.id, label: type.title })));

const form = useForm({
  purposes: Object.fromEntries(props.purposes.map(purpose => [purpose.key, purpose.responsibility_id])) as Record<string, string | null>,
  duty_types: Object.fromEntries(props.dutyTypes.map(part => [part.key, part.type_id])) as Record<string, number | null>,
});

/** Until a type is chosen, the contact pages still use the type with the old link. */
function fallbackHint(part: DutyTypePart): string | null {
  if (form.duty_types[part.key] !== null) {
    return null;
  }

  const fallback = props.dutyTypeOptions.find(type => type.id === part.effective_type_id);

  return `${part.drives} ${$t('settings.purposes.fallback', { slug: fallback?.title ?? part.fallback_slug })}`;
}
</script>
