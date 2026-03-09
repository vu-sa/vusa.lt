<template>
  <PageContent :title="$t('settings.pages.authorization.title')" :back-url="route('settings.index')">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            {{ $t('settings.authorization_form.role_label') }}
          </template>
          <template #description>
            {{ $t('settings.authorization_form.role_description') }}
          </template>
          
          <div class="space-y-4">
            <div class="space-y-2">
              <Label class="inline-flex items-center gap-1">
                <component :is="Icons.ROLE" class="h-4 w-4" />
                {{ $t('settings.authorization_form.role_label') }}
              </Label>
              <Select v-model="selectedRoleId">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('settings.authorization_form.role_placeholder')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__none__">
                    {{ $t('settings.authorization_form.role_placeholder') }}
                  </SelectItem>
                  <SelectItem v-for="role in roles" :key="role.id" :value="role.id">
                    {{ role.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            <Alert>
              <InfoIcon class="h-4 w-4" />
              <AlertDescription>
                {{ $t('settings.authorization_form.super_admin_note') }}
              </AlertDescription>
            </Alert>
          </div>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { InfoIcon } from "lucide-vue-next";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import { Alert, AlertDescription } from "@/Components/ui/alert";
import { Label } from "@/Components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";
import Icons from "@/Types/Icons/regular";

const props = defineProps<{
  settings_manager_role_id: string | null;
  roles: App.Entities.Role[];
}>();

// Use '__none__' as placeholder since empty string is not allowed in SelectItem
const NONE_VALUE = '__none__';
const selectedRoleId = ref<string>(props.settings_manager_role_id ?? NONE_VALUE);

const form = useForm({
  settings_manager_role_id: props.settings_manager_role_id
});

// Sync selected role to form
watch(selectedRoleId, (newValue) => {
  form.settings_manager_role_id = newValue && newValue !== NONE_VALUE ? newValue : null;
});

const handleFormSubmit = () => {
  form.post(route("settings.authorization.update"));
};
</script>
