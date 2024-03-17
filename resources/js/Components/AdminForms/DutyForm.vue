<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>{{ $t("forms.context.main_info") }} </template>
        <NFormItem :label="$t('forms.fields.title')" :span="2">
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

        <NFormItem :label="$t('forms.fields.email')">
          <NAutoComplete
            v-model:value="form.email"
            :options="emailOptions"
            placeholder="vusa@vusa.lt"
          />
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

          <NFormItem :label="$t('forms.fields.duty_people_count')" :min="0"
            ><NInputNumber v-model:value="form.places_to_occupy"></NInputNumber
          ></NFormItem>
        </div>
      </FormElement>
      <FormElement>
        <template #title>{{ $t("forms.fields.description") }}</template>
        <template #description
          >Aprašymas yra rodomas vusa.lt puslapyje prie pareigybės</template
        >
        <NFormItem label="Aprašymas" :span="6">
          <template #label>
            <div class="inline-flex items-center gap-2">
              Aprašymas
              <SimpleLocaleButton v-model:locale="locale" />
            </div>
          </template>
          <TipTap html
            v-if="locale === 'lt'"
            v-model="form.description"
            :search-files="$page.props.search.other"
          />
          <TipTap html
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
              <strong>{{ $t("Nariai") }}</strong
              ><a target="_blank" :href="route('users.create')"
                ><NButton text size="tiny"
                  ><template #icon
                    ><NIcon :component="Add24Filled"></NIcon></template
                  >Sukurti naują asmenį</NButton
                ></a
              >
            </div>
          </template>
          <NTransfer
            ref="transfer"
            v-model:value="form.current_users"
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
        <template #description>
          <div class="flex flex-col gap-2">
            <p>
              <strong>Pareigybės tipas</strong> reikalingas tam, kad tam tikrais
              atvejais, nariai būtų rodomi viešame studentų atstovybės
              puslapyje. Pavyzdžiui, studentų atstovo tipui priklausantys
              asmenys rodomi prie institucijos kontaktų.
            </p>
            <p>
              <strong>Administracinė vusa.lt rolė </strong> leidžia
              registruotiems naudotojams atlikti jiems priskirtus veiksmus
              vidiniame mano.vusa.lt tinklalapyje
            </p>
          </div>
        </template>
        <NFormItem label="Pareigybės tipas">
          <NSelect
            v-model:value="form.types"
            multiple
            :options="dutyTypes"
            label-field="title"
            value-field="id"
            placeholder="Pasirinkti kategoriją..."
            clearable
          />
        </NFormItem>

        <NFormItem label="Administracinė vusa.lt rolė">
          <NSelect
            v-model:value="form.roles"
            :options="rolesOptions"
            :disabled="!$page.props.auth?.user.isSuperAdmin"
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
        :disabled="duty.current_users && duty.current_users.length > 0"
        :form="form"
        :model-route="deleteModelRoute"
      ></DeleteModelButton>
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { Add24Filled, Edit16Filled, Eye16Regular } from "@vicons/fluent";
import {
  NAutoComplete,
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
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";

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
form.current_users = props.duty.current_users?.map((user) => user.id);

const userOptions = props.assignableUsers.map((user) => ({
  label: user.name,
  value: user.id,
  user: user,
}));

const emailOptions = computed(() => {
  return usePage().props.auth?.user.padaliniai.map((padalinys) => {
    const prefix = form.email?.split("@")[0];
    return {
      label: `${prefix}@${padalinys.alias}.vusa.lt`,
      value: `${prefix}@${padalinys.alias}.vusa.lt`,
    };
  });
});

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
      <span class="inline-flex gap-2">
        {option.label}

        <a target="_blank" href={route("users.edit", option.value)}>
          <NButton size="tiny" text>
            {{
              icon: <NIcon component={Eye16Regular} />,
            }}
          </NButton>
        </a>
      </span>
    </div>
  );
};
</script>
