<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <NFormItem required>
        <template #label>
          <span class="inline-flex items-center gap-1">
            <NIcon :component="Icons.TITLE" />
            Pavadinimas
          </span>
        </template>
        <MultiLocaleInput v-model:input="form.name" />
      </NFormItem>
      <NFormItem label="Padalinys">
        <NSelect v-model:value="form.tenant_id" :options="assignableTenants" label-field="shortname" value-field="id"
          placeholder="VU SA ..." :default-value="assignableTenants[0].id ?? ''" />
      </NFormItem>
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

      <NUpload ref="upload" :action="route('membershipUsers.import', membership.id)" :default-upload="false"
        :headers="{ 'X-CSRF-TOKEN': $page.props.csrf_token }" @change="handleChange">
        <NButton>Įkelti failą</NButton>
      </NUpload>
      <NButton type="primary" :disabled="fileListLength === 0" style="margin-top: 12px" @click="handleClick">
        Importuoti
      </NButton>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="tsx">
import { useForm } from "@inertiajs/vue3";
import { NIcon, type UploadFileInfo } from "naive-ui";

import FormElement from "./FormElement.vue";
import AdminForm from "./AdminForm.vue";
import Icons from "@/Types/Icons/regular";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import { ref, useTemplateRef } from "vue";

const { membership, rememberKey } = defineProps<{
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

const fileListLength = ref(0);

const upload = useTemplateRef("upload");

const handleChange = (data: { fileList: UploadFileInfo[] }) => {
  fileListLength.value = data.fileList.length;
};

const handleClick = () => {
  upload.value?.submit();
};
</script>
