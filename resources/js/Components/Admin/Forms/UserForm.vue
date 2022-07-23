<template>
  <NForm :model="form" label-placement="top">
    <NGrid :span="24" :x-gap="24">
      <NFormItemGi label="Vardas ir Pavardė" :span="12">
        <NInput
          v-model:value="form.name"
          type="text"
          placeholder="Įrašyti vardą ir pavardę"
        />
      </NFormItemGi>

      <NFormItemGi label="Studentinis el. paštas" :span="12">
        <NInput
          v-model:value="form.email"
          placeholder="vardas.pavarde@padalinys.stud.vu.lt"
        />
      </NFormItemGi>

      <NFormItemGi label="Tel. numeris" :span="12">
        <NInput v-model:value="form.phone" placeholder="+370 612 345 678" />
      </NFormItemGi>

      <NFormItemGi label="Administracinė vusa.lt rolė" :span="12">
        <NSelect
          v-model:value="form.role.id"
          :disabled="$page.props.user.role.alias !== 'admin'"
          :options="rolesOptions"
          clearable
          type="text"
          placeholder="Be rolės..."
        />
      </NFormItemGi>

      <NFormItemGi label="Pareigybės" :span="24">
        <NSelect
          v-model:value="form.duties"
          multiple
          filterable
          placeholder="Pasirinkti pareigybes..."
          :options="dutyOptions"
          clearable
          remote
          :clear-filter-after-select="false"
          @search="getDutyOptions"
        />
      </NFormItemGi>

      <NFormItemGi label="Nuotrauka" :span="24">
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

<script lang="ts">
const { message } = createDiscreteApi(["message"]);
</script>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import {
  NForm,
  NFormItemGi,
  NGrid,
  NInput,
  NSelect,
  createDiscreteApi,
} from "naive-ui";
import { debounce } from "lodash";
import { ref } from "vue";
import { useForm, usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import DeleteModelButton from "@/Components/Admin/Buttons/DeleteModelButton.vue";
import UploadImageButtons from "@/Components/Admin/Buttons/UploadImageButtons.vue";
import UpsertModelButton from "@/Components/Admin/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  user: App.Models.User;
  roles: App.Models.Role[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const form = useForm("dutyInstitution", props.user);

form.role = {
  id: form.role?.id,
  name: form.role?.name,
};

const dutyOptions = ref<App.Models.Duty>([]);

const rolesOptions = props.roles.map((role) => ({
  label: role.name,
  value: role.id,
}));

const getDutyOptions = debounce((input) => {
  // get other lang
  if (input.length > 2) {
    message.loading("Ieškoma...");
    Inertia.post(
      route("duties.search"),
      {
        data: {
          name: input,
        },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          dutyOptions.value = usePage().props.value.search.other.map((duty) => {
            return {
              value: duty.id,
              label: `${duty.name} (${duty.institution})`,
            };
          });
        },
      }
    );
  }
}, 500);

////////////////////////////////////////////////////////////////////////////////

if (props.modelRoute !== "users.store") {
  // set options from existing duties
  dutyOptions.value = props.user.duties?.map((duty) => {
    return {
      value: duty.id,
      label: `${duty.name} - ${duty.institution?.short_name} (${duty.institution?.alias})`,
    };
  });
  //
  form.duties = props.user.duties?.map((duty) => {
    return duty.id;
  });
}
</script>
