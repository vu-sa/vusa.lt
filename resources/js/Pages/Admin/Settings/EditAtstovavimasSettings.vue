<template>
  <PageContent :title="$t('settings.pages.atstovavimas.title')" :back-url="route('settings.index')">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            {{ $t('settings.atstovavimas_settings.manager_role_title') }}
          </template>
          <template #description>
            {{ $t('settings.atstovavimas_settings.manager_role_description') }}
          </template>

          <div class="space-y-2">
            <Label class="inline-flex items-center gap-1">
              <component :is="RoleIcon" class="h-4 w-4" />
              {{ $t('settings.atstovavimas_settings.manager_role_label') }}
            </Label>

            <Select v-model="form.institution_manager_role_id">
              <SelectTrigger>
                <SelectValue :placeholder="$t('settings.atstovavimas_settings.manager_role_placeholder')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="option in roleOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <p class="text-sm text-muted-foreground">
              {{ $t('settings.atstovavimas_settings.manager_role_note') }}
            </p>
          </div>
        </FormElement>

        <FormElement>
          <template #title>
            {{ $t('settings.atstovavimas_settings.student_rep_type_title') }}
          </template>
          <template #description>
            {{ $t('settings.atstovavimas_settings.student_rep_type_description') }}
          </template>

          <div class="space-y-2">
            <Label class="inline-flex items-center gap-1">
              <component :is="TypeIcon" class="h-4 w-4" />
              {{ $t('settings.atstovavimas_settings.student_rep_type_label') }}
            </Label>

            <Select v-model="form.student_rep_root_type_id">
              <SelectTrigger>
                <SelectValue :placeholder="$t('settings.atstovavimas_settings.student_rep_type_placeholder')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="null">
                  {{ $t('settings.atstovavimas_settings.student_rep_type_default') }}
                </SelectItem>
                <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </SelectItem>
              </SelectContent>
            </Select>

            <p class="text-sm text-muted-foreground">
              {{ $t('settings.atstovavimas_settings.student_rep_type_note') }}
            </p>
          </div>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { RoleIcon, TypeIcon } from '@/Components/icons';

interface Role {
  id: string;
  name: string;
}

interface InstitutionType {
  id: number;
  title: string;
  slug: string;
}

const props = withDefaults(defineProps<{
  institution_manager_role_id: string | null;
  student_rep_root_type_id?: number | null;
  roles: Role[];
  institution_types?: InstitutionType[];
}>(), {
  student_rep_root_type_id: null,
  institution_types: () => [],
});

const roleOptions = computed(() =>
  props.roles.map(role => ({
    label: role.name,
    value: role.id,
  })),
);

const typeOptions = computed(() =>
  props.institution_types.map(type => ({
    label: `${type.title} (${type.slug})`,
    value: type.id,
  })),
);

const form = useForm({
  institution_manager_role_id: props.institution_manager_role_id,
  student_rep_root_type_id: props.student_rep_root_type_id,
});

const handleFormSubmit = () => {
  form.post(route('settings.atstovavimas.update'));
};
</script>
