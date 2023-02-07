<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>Pagrindinė informacija</template>
        <NFormItem label="Pareigų pavadinimas" :span="2">
          <NInput
            v-if="locale === 'lt'"
            v-model:value="form.name"
            type="text"
            placeholder="Prezidentė"
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
          <NInput
            v-else
            v-model:value="form.extra_attributes.en.name"
            type="text"
            placeholder="President"
            ><template #suffix
              ><SimpleLocaleButton
                v-model:locale="locale"
              ></SimpleLocaleButton></template
          ></NInput>
        </NFormItem>

        <NFormItem label="Pareigybinis el. paštas">
          <NInput v-model:value="form.email" placeholder="vusa@vusa.lt" />
        </NFormItem>

        <div class="grid gap-4 lg:grid-cols-2">
          <NFormItem label="Institucija">
            <NSelect
              v-model:value="form.institution_id"
              filterable
              placeholder="Pasirink instituciją pagal pavadinimą..."
              :options="institutionsFromDatabase"
              clearable
            />
          </NFormItem>

          <NFormItem label="Pareigybę užimančių žmonių skaičius" :min="0"
            ><NInputNumber v-model:value="form.places_to_occupy"></NInputNumber
          ></NFormItem>
        </div>
      </FormElement>
      <FormElement>
        <template #title>Detalesnis aprašymas</template>
        <template #description
          >Aprašymas yra rodomas vusa.lt puslapyje prie pareigybės</template
        >
        <NFormItem label="Aprašymas" :span="6">
          <template #label>
            <div class="inline-flex items-center gap-2">
              Aprašymas
              <SimpleLocaleButton v-model:locale="locale"></SimpleLocaleButton>
            </div>
          </template>
          <TipTap
            v-if="locale === 'lt'"
            v-model="form.description"
            :search-files="$page.props.search.other"
          />
          <TipTap
            v-else
            v-model="form.extra_attributes.en.description"
            :search-files="$page.props.search.other"
          />
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Asmenys</template>
        <template #description
          ><p class="mb-4">Pareigybę gali užimti daug naudotojų.</p>
          <p>
            Jeigu sąraše nėra asmens, kuris užima pareigybę, šį asmenį reikia
            sukurti.
          </p></template
        >
        <NFormItem>
          <template #label>
            <div class="inline-flex items-center gap-2">
              <span><strong>Pareigybės</strong></span
              ><a target="_blank" :href="route('users.create')"
                ><NButton text size="tiny"
                  ><template #icon
                    ><NIcon :component="Add24Filled"></NIcon></template
                  >Sukurti naują asmenį?</NButton
                ></a
              >
            </div>
          </template>
          <NTransfer
            ref="transfer"
            v-model:value="form.users"
            virtual-scroll
            :options="userOptions"
            :render-source-label="renderSourceLabel"
            :render-target-label="renderTargetLabel"
            source-filterable
          ></NTransfer>
        </NFormItem>
      </FormElement>
      <FormElement>
        <template #title>Papildoma informacija</template>
        <template #description
          >Šiuo metu šie nustatymai tik rodomi, jų negalima keisti...</template
        >
        <NFormItem label="Pareigybės tipas" :span="2">
          <NSelect
            v-model:value="form.types"
            :disabled="!$page.props.auth.user.isSuperAdmin"
            multiple
            :options="dutyTypes"
            label-field="title"
            value-field="id"
            placeholder="Pasirinkti kategoriją..."
            clearable
          />
        </NFormItem>

        <NFormItem label="Administracinė vusa.lt rolė" :span="2">
          <NSelect
            v-model:value="form.roles"
            :disabled="!$page.props.auth.user.isSuperAdmin"
            :options="rolesOptions"
            clearable
            multiple
            type="text"
            placeholder="Be rolės..."
          />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton
        v-if="deleteModelRoute"
        :disabled="duty.users && duty.users.length > 0"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { Add24Filled, Edit16Filled } from "@vicons/fluent";
import {
  NButton,
  NForm,
  NFormItem,
  NIcon,
  NInput,
  NInputNumber,
  NSelect,
  NTransfer,
  type TransferRenderSourceLabel,
  type TransferRenderTargetLabel,
} from "naive-ui";
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TipTap from "@/Components/TipTap/OriginalTipTap.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";
import UserAvatar from "../Avatars/UserAvatar.vue";

const props = defineProps<{
  duty: App.Entities.Duty;
  dutyTypes: App.Entities.Type[];
  assignableUsers: App.Entities.User[];
  roles: App.Entities.Role[];
  institutions: App.Entities.Institution[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const locale = ref("lt");

const form = useForm("institution", props.duty);

form.roles = props.duty.roles?.map((role) => role.id);
form.types = props.duty.types?.map((type) => type.id);
form.users = props.duty.users?.map((user) => user.id);

const userOptions = props.assignableUsers.map((user) => ({
  label: user.name,
  value: user.id,
  user: user,
}));

const rolesOptions = props.roles.map((role) => ({
  label: role.name,
  value: role.id,
}));

const institutionsFromDatabase = props.institutions.map((institution) => ({
  label: `${institution.name} (${institution.padalinys?.shortname})`,
  value: institution.id,
}));

const renderSourceLabel: TransferRenderSourceLabel = ({ option }) => {
  return (
    <div class="flex items-center gap-2">
      <span>{option.label}</span>
      <a target="_blank" href={route("users.edit", option.value)}>
        <NButton text size="tiny">
          {{ icon: <NIcon component={Edit16Filled}></NIcon> }}
        </NButton>
      </a>
    </div>
  );
};

const renderTargetLabel: TransferRenderTargetLabel = ({ option }) => {
  return (
    <div class="flex items-center gap-2">
      <UserAvatar size={24} user={option.user}></UserAvatar>
      <span>{option.label}</span>
    </div>
  );
};
</script>
