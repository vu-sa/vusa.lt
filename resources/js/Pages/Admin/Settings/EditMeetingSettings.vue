<template>
  <PageContent :title="$t('settings.pages.meetings.title')" :back-url="route('settings.index')">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            {{ $t('settings.meeting_settings.types_title') }}
          </template>
          <template #description>
            {{ $t('settings.meeting_settings.types_description') }}
          </template>
          
          <div class="space-y-2">
            <Label class="inline-flex items-center gap-1">
              <component :is="Icons.TYPE" class="h-4 w-4" />
              {{ $t('settings.meeting_settings.types_label') }}
            </Label>
            
            <MultiSelect
              v-model="selectedTypes"
              :options="available_types"
              label-field="title"
              value-field="id"
              :placeholder="$t('settings.meeting_settings.types_placeholder')"
              :empty-text="$t('settings.meeting_settings.no_types_found')"
            />
          </div>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import Icons from "@/Types/Icons/regular";
import { Label } from "@/Components/ui/label";
import { MultiSelect } from "@/Components/ui/multi-select";

interface InstitutionType {
  id: number;
  title: string;
  slug: string;
}

const props = defineProps<{
  selected_type_ids: number[];
  available_types: InstitutionType[];
}>();

// Initialize selected types from props
const selectedTypes = ref<InstitutionType[]>(
  props.available_types.filter(type => props.selected_type_ids.includes(type.id))
);

const form = useForm({
  type_ids: props.selected_type_ids
});

// Sync selected types to form
watch(selectedTypes, (newTypes) => {
  form.type_ids = newTypes.map(type => type.id);
}, { deep: true });

const handleFormSubmit = () => {
  form.post(route("settings.meetings.update"));
};
</script>
