<template>
  <PageContent :title="$t('settings.pages.documents.title')" :back-url="route('settings.index')">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            {{ $t('settings.document_settings.important_types_title') }}
          </template>
          <template #description>
            {{ $t('settings.document_settings.important_types_description') }}
          </template>

          <div class="space-y-2">
            <Label class="inline-flex items-center gap-1">
              <component :is="Icons.DOCUMENT" class="h-4 w-4" />
              {{ $t('settings.document_settings.important_types_label') }}
            </Label>

            <MultiSelect
              v-model="selectedTypes"
              :options="contentTypeOptions"
              label-field="label"
              value-field="value"
              :placeholder="$t('settings.document_settings.important_types_placeholder')"
              :empty-text="$t('settings.document_settings.no_types_found')"
            />
          </div>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import Icons from '@/Types/Icons/regular';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';

interface ContentTypeOption {
  value: string;
  label: string;
}

const props = defineProps<{
  selected_content_types: string[];
  available_content_types: string[];
}>();

// Transform available content types to options format
const contentTypeOptions = computed<ContentTypeOption[]>(() => {
  return props.available_content_types.map(type => ({
    value: type,
    label: type,
  }));
});

// Initialize selected types from props
const selectedTypes = ref<ContentTypeOption[]>(
  props.selected_content_types.map(type => ({
    value: type,
    label: type,
  })),
);

const form = useForm({
  important_content_types: props.selected_content_types,
});

// Sync selected types to form
watch(selectedTypes, (newTypes) => {
  form.important_content_types = newTypes.map(type => type.value);
}, { deep: true });

const handleFormSubmit = () => {
  form.post(route('settings.documents.update'));
};
</script>
