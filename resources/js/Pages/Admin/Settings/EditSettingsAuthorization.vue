<template>
  <FormPage
    :title="$t('settings.pages.authorization.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleFormSubmit"
  >
    <FormSection
      :title="$t('settings.authorization_form.role_label')"
      :description="$t('settings.authorization_form.role_description')"
    >
      <FormFieldWrapper
        id="settings_manager_role_id"
        :label="$t('settings.authorization_form.role_label')"
        :error="form.errors.settings_manager_role_id"
      >
        <Select v-model="selectedRoleId">
          <SelectTrigger id="settings_manager_role_id">
            <SelectValue :placeholder="$t('settings.authorization_form.role_placeholder')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="NONE_VALUE">
              {{ $t('settings.authorization_form.role_placeholder') }}
            </SelectItem>
            <SelectItem v-for="role in roles" :key="role.id" :value="role.id">
              {{ role.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>

      <p class="flex items-start gap-2 border-l-2 border-status-info bg-status-info-surface px-4 py-3 text-sm text-status-info">
        <Info class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
        {{ $t('settings.authorization_form.super_admin_note') }}
      </p>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Info } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

const props = defineProps<{
  settings_manager_role_id: string | null;
  roles: App.Entities.Role[];
}>();

// Use '__none__' as placeholder since empty string is not allowed in SelectItem
const NONE_VALUE = '__none__';
const selectedRoleId = ref<string>(props.settings_manager_role_id ?? NONE_VALUE);

const form = useForm({
  settings_manager_role_id: props.settings_manager_role_id,
});

// Sync selected role to form
watch(selectedRoleId, (newValue) => {
  form.settings_manager_role_id = newValue && newValue !== NONE_VALUE ? newValue : null;
});

const handleFormSubmit = () => {
  form.post(route('settings.authorization.update'));
};
</script>
