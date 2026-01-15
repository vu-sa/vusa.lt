<template>
  <PageContent :title="$t('settings.pages.atstovavimas.title')" :back-url="route('settings.index')">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            {{ $t('settings.atstovavimas_settings.global_roles_title') }}
          </template>
          <template #description>
            {{ $t('settings.atstovavimas_settings.global_roles_description') }}
          </template>
          
          <div class="space-y-2">
            <Label class="inline-flex items-center gap-1">
              <component :is="Icons.ROLE" class="h-4 w-4" />
              {{ $t('settings.atstovavimas_settings.global_roles_label') }}
            </Label>
            
            <MultiSelect
              v-model="selectedGlobalRoles"
              :options="props.roles"
              label-field="name"
              value-field="id"
              :placeholder="$t('settings.atstovavimas_settings.global_roles_placeholder')"
              :empty-text="$t('settings.atstovavimas_settings.no_roles_found')"
            />
            
            <p class="text-sm text-muted-foreground">
              {{ $t('settings.atstovavimas_settings.global_roles_note') }}
            </p>
          </div>
        </FormElement>

        <FormElement>
          <template #title>
            {{ $t('settings.atstovavimas_settings.tenant_roles_title') }}
          </template>
          <template #description>
            {{ $t('settings.atstovavimas_settings.tenant_roles_description') }}
          </template>

          <div class="space-y-2">
            <Label class="inline-flex items-center gap-1">
              <component :is="Icons.ROLE" class="h-4 w-4" />
              {{ $t('settings.atstovavimas_settings.tenant_roles_label') }}
            </Label>

            <MultiSelect
              v-model="selectedTenantRoles"
              :options="props.roles"
              label-field="name"
              value-field="id"
              :placeholder="$t('settings.atstovavimas_settings.tenant_roles_placeholder')"
              :empty-text="$t('settings.atstovavimas_settings.no_roles_found')"
            />

            <p class="text-sm text-muted-foreground">
              {{ $t('settings.atstovavimas_settings.tenant_roles_note') }}
            </p>
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

interface Role {
  id: string;
  name: string;
}

const props = defineProps<{
  global_visibility_role_ids: string[];
  tenant_visibility_role_ids: string[];
  roles: Role[];
}>();

const selectedGlobalRoles = ref<Role[]>(
  props.roles.filter(role => props.global_visibility_role_ids.includes(role.id))
);

const selectedTenantRoles = ref<Role[]>(
  props.roles.filter(role => props.tenant_visibility_role_ids.includes(role.id))
);

const form = useForm({
  global_visibility_role_ids: props.global_visibility_role_ids,
  tenant_visibility_role_ids: props.tenant_visibility_role_ids
});

watch(selectedGlobalRoles, (newRoles) => {
  form.global_visibility_role_ids = newRoles.map(role => role.id);
}, { deep: true });

watch(selectedTenantRoles, (newRoles) => {
  form.tenant_visibility_role_ids = newRoles.map(role => role.id);
}, { deep: true });

const handleFormSubmit = () => {
  form.post(route("settings.atstovavimas.update"));
};
</script>
