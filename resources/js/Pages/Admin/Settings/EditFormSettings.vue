<template>
  <PageContent :title="$t('settings.pages.forms.title')" :back-url="route('settings.index')">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            {{ $t('settings.form_settings.registration_form_title') }}
          </template>
          <template #description>
            {{ $t('settings.form_settings.registration_form_description') }}
          </template>

          <div class="space-y-4">
            <div class="space-y-2">
              <Label class="inline-flex items-center gap-1">
                <component :is="Icons.FORM" class="h-4 w-4" />
                {{ $t('settings.form_settings.form_label') }}
              </Label>
              <Select v-model="form.member_registration_form_id">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('settings.form_settings.form_placeholder')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="formOption in forms" :key="formOption.id" :value="formOption.id">
                    {{ formOption.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <Label class="inline-flex items-center gap-1">
                <component :is="Icons.ROLE" class="h-4 w-4" />
                {{ $t('settings.form_settings.role_label') }}
              </Label>
              <Select v-model="form.member_registration_notification_recipient_role_id">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('settings.form_settings.role_placeholder')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="role in roles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </FormElement>

        <!-- Student Representative Registration Form Settings -->
        <FormElement>
          <template #title>
            {{ $t('settings.form_settings.student_rep_title') }}
          </template>
          <template #description>
            {{ $t('settings.form_settings.student_rep_description') }}
          </template>

          <div class="space-y-4">
            <div class="space-y-2">
              <Label class="inline-flex items-center gap-1">
                <component :is="Icons.FORM" class="h-4 w-4" />
                {{ $t('settings.form_settings.student_rep_form_label') }}
              </Label>
              <Select v-model="form.student_rep_registration_form_id">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('settings.form_settings.form_placeholder')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="null">
                    {{ $t('settings.form_settings.no_form_selected') }}
                  </SelectItem>
                  <SelectItem v-for="formOption in forms" :key="formOption.id" :value="formOption.id">
                    {{ formOption.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-2">
              <Label class="inline-flex items-center gap-1">
                <component :is="Icons.TYPE" class="h-4 w-4" />
                {{ $t('settings.form_settings.student_rep_types_label') }}
              </Label>
              <p class="text-sm text-muted-foreground">
                {{ $t('settings.form_settings.student_rep_types_description') }}
              </p>
              <MultiSelect
                v-model="selectedTypes"
                :options="props.institution_types"
                label-field="title"
                value-field="id"
                :placeholder="$t('settings.form_settings.student_rep_types_placeholder')"
                :empty-text="$t('settings.form_settings.no_types_found')"
              />
            </div>
          </div>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import Icons from '@/Types/Icons/regular';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

interface InstitutionType {
  id: number;
  title: string;
  slug: string;
}

const props = defineProps<{
  member_registration_form_id: string | null;
  member_registration_notification_recipient_role_id: string | null;
  student_rep_registration_form_id: string | null;
  student_rep_institution_type_ids: number[];
  forms: App.Entities.Form[];
  roles: App.Entities.Role[];
  institution_types: InstitutionType[];
}>();

// Initialize selected types from props
const selectedTypes = ref<InstitutionType[]>(
  props.institution_types.filter(type => props.student_rep_institution_type_ids.includes(type.id)),
);

const form = useForm({
  member_registration_form_id: props.member_registration_form_id,
  member_registration_notification_recipient_role_id: props.member_registration_notification_recipient_role_id,
  student_rep_registration_form_id: props.student_rep_registration_form_id,
  student_rep_institution_type_ids: props.student_rep_institution_type_ids ?? [],
});

// Sync selected types to form
watch(selectedTypes, (newTypes) => {
  form.student_rep_institution_type_ids = newTypes.map(type => type.id);
}, { deep: false });

const handleFormSubmit = () => {
  form.post(route('settings.forms.update'));
};
</script>
