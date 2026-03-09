<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <FormFieldWrapper id="name" label="Pavadinimas" required :error="form.errors.name">
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>
      <FormFieldWrapper id="tenant_id" label="Padalinys" :error="form.errors.tenant_id">
        <Select v-model="tenantIdString">
          <SelectTrigger>
            <SelectValue placeholder="VU SA ..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
              {{ tenant.shortname }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormElement>
    <FormElement v-if="canImportMemberships">
      <template #title>
        Importuoti narius
      </template>
      <template #description>
        <p>
          Galima importuoti narius į šią narystę. Importuojamas laikotarpis nėra perrašomas, o tik pridedamas prie
          egzistuojančio.
        </p>
        <p> Egzistuojančių narių informacija nėra perrašoma. Ar narys (-ė) egzistuoja, tikrinama pagal el. paštą. </p>
        <p>
          Importavimo failą rasite <a class="font-bold" href="/imports/import_membershipuser_20241201.xlsx">čia</a>.
        </p>
      </template>

      <div class="flex items-center gap-3">
        <Button variant="outline" as="label" class="cursor-pointer">
          Įkelti failą
          <input ref="fileInput" type="file" class="hidden" accept=".xlsx,.xls,.csv"
            @change="handleFileChange" />
        </Button>
        <span v-if="selectedFile" class="text-sm text-muted-foreground">{{ selectedFile.name }}</span>
      </div>
      <Button :disabled="!selectedFile" class="mt-3" @click="handleImport">
        Importuoti
      </Button>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm, usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref } from "vue";

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Button } from "@/Components/ui/button";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import AdminForm from "./AdminForm.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";

const { membership, assignableTenants, rememberKey } = defineProps<{
  membership: App.Entities.Membership;
  canImportMemberships?: boolean;
  assignableTenants: Array<App.Entities.Tenant>;
  rememberKey?: "CreateMembership";
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = rememberKey
  ? useForm(rememberKey, membership)
  : useForm(membership);

const page = usePage();

// Shadcn Select requires string values
const tenantIdString = computed({
  get: () => form.tenant_id != null ? String(form.tenant_id) : (assignableTenants[0]?.id != null ? String(assignableTenants[0].id) : ''),
  set: (val: string) => { form.tenant_id = val ? Number(val) : null; },
});

const selectedFile = ref<File | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const handleFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  selectedFile.value = input.files?.[0] ?? null;
};

const handleImport = async () => {
  if (!selectedFile.value) return;

  const formData = new FormData();
  formData.append('file', selectedFile.value);

  await fetch(route('membershipUsers.import', membership.id), {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': page.props.csrf_token as string,
    },
    body: formData,
  });
};
</script>
