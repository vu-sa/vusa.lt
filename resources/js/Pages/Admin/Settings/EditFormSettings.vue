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
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import Icons from "@/Types/Icons/regular";
import { Label } from "@/Components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";

const props = defineProps<{
  member_registration_form_id: string | null;
  member_registration_notification_recipient_role_id: string | null;
  forms: App.Entities.Form[];
  roles: App.Entities.Role[];
}>();

const form = useForm({
  member_registration_form_id: props.member_registration_form_id,
  member_registration_notification_recipient_role_id: props.member_registration_notification_recipient_role_id,
});

const handleFormSubmit = () => {
  form.post(route("settings.forms.update"));
};
</script>
