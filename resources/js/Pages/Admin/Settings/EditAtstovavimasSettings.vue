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
              <component :is="Icons.ROLE" class="h-4 w-4" />
              {{ $t('settings.atstovavimas_settings.manager_role_label') }}
            </Label>
            
            <NSelect
              v-model:value="form.institution_manager_role_id"
              :options="roleOptions"
              clearable
              :placeholder="$t('settings.atstovavimas_settings.manager_role_placeholder')"
            />
            
            <p class="text-sm text-muted-foreground">
              {{ $t('settings.atstovavimas_settings.manager_role_note') }}
            </p>
          </div>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { computed } from "vue";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import Icons from "@/Types/Icons/regular";
import { Label } from "@/Components/ui/label";

interface Role {
  id: string;
  name: string;
}

const props = defineProps<{
  institution_manager_role_id: string | null;
  roles: Role[];
}>();

const roleOptions = computed(() => 
  props.roles.map(role => ({
    label: role.name,
    value: role.id
  }))
);

const form = useForm({
  institution_manager_role_id: props.institution_manager_role_id
});

const handleFormSubmit = () => {
  form.post(route("settings.atstovavimas.update"));
};
</script>
