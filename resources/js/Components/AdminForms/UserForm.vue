<template>
  <NForm :model="form" label-placement="top">
    <NGrid cols="1 s:4 l:6" responsive="screen" :x-gap="24">
      <NFormItemGi label="Vardas ir Pavardė" :span="2" required>
        <NInput
          v-model:value="form.name"
          type="text"
          placeholder="Įrašyti vardą ir pavardę"
        />
      </NFormItemGi>

      <NFormItemGi label="Studentinis el. paštas" :span="2" required>
        <NInput
          v-model:value="form.email"
          placeholder="vardas.pavarde@padalinys.stud.vu.lt"
        />
      </NFormItemGi>

      <NFormItemGi label="Tel. numeris" :span="2">
        <NInput v-model:value="form.phone" placeholder="+370 612 34 567" />
      </NFormItemGi>

      <NFormItemGi label="Administracinė vusa.lt rolė" :span="2">
        <NSelect
          v-model:value="form.roles"
          :disabled="!$page.props.auth.user.isSuperAdmin"
          :options="rolesOptions"
          clearable
          multiple
          type="text"
          placeholder="Be rolės..."
        />
      </NFormItemGi>

      <NFormItemGi label="Pareigybės" :span="6">
        <NTransfer
          ref="transfer"
          v-model:value="form.duties"
          :options="dutyOptions"
          source-filterable
          source-filter-placeholder="Ieškoti pareigų..."
          size="small"
        ></NTransfer>
      </NFormItemGi>

      <NFormItemGi label="Nuotrauka" :span="2">
        <UploadImageButtons
          v-model="form.profile_photo_path"
          :path="'contacts'"
        ></UploadImageButtons>
      </NFormItemGi>
    </NGrid>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="ts">
import {
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSelect,
  NTransfer,
} from "naive-ui";
import { useForm } from "@inertiajs/inertia-vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import UploadImageButtons from "@/Components/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  user: App.Models.User;
  roles: App.Models.Role[];
  duties: App.Models.Duty[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("institution", props.user);

const dutyOptions = props.duties.map((duty) => ({
  label: `${duty.name} (${duty.institution?.padalinys?.shortname})`,
  value: duty.id,
}));

const rolesOptions = props.roles.map((role) => ({
  label: role.name,
  value: role.name,
}));

form.duties = props.user.duties?.map((duty) => duty.id);
</script>
