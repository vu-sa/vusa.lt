<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>
          {{ $t("forms.context.main_info") }}
        </template>
        <template #description>
          <p class="mb-4">
            Pagrindinė informacija apie naudotoją (dažniausiai, tai bus
            studentas, VU SA narys).
          </p>
          <p>
            Naudotojai iš vusa.lt/mano
            <strong> netrinami bei negalima keisti jų vardų pavardžių. </strong>
            Jeigu pasikeitė koordinatorius, studentų atstovas:
          </p>
          <ol>
            <li>Pašalink pareigybes iš šio profilio</li>
            <li>Sukurk naują naudotojo profilį</li>
            <li>Priskirk jam jo pareigybes</li>
          </ol>
        </template>
        <NFormItem :label="$t('forms.fields.name_and_surname')" required>
          <NInput v-model:value="form.name" :disabled="user.name !== ''" type="text"
            placeholder="Įrašyti vardą ir pavardę" />
        </NFormItem>

        <NFormItem required>
          <template #label>
            <div class="inline-flex items-center gap-2">
              <span>Studentinis el. paštas</span>
              <InfoPopover v-if="isUserEmailMaybeDutyEmail">Jeigu <strong>{{ user.email }}</strong> yra pareigybinis el.
                paštas (ir panašu, kad šiuo atveju taip ir yra 😊), jį reikėtų
                pakeisti į studentinį.</InfoPopover>
            </div>
          </template>
          <NAutoComplete v-model:value="form.email" :options="emailOptions"
            placeholder="vardas.pavarde@padalinys.stud.vu.lt" />
        </NFormItem>

        <div class="grid gap-4 lg:grid-cols-2">
          <NFormItem :label="$t('forms.fields.phone')">
            <NInput v-model:value="form.phone" placeholder="+370 612 34 567" />
          </NFormItem>
          <NFormItem :label="$t('forms.fields.facebook_url')">
            <NInput v-model:value="form.facebook_url" placeholder="https://www.facebook.com/..." />
          </NFormItem>
        </div>

        <NFormItem :label="$t('forms.fields.picture')">
          <UploadImageWithCropper v-model:url="form.profile_photo_path" folder="contacts" />
        </NFormItem>

        <NFormItem v-if="$page.props.auth?.user?.isSuperAdmin" :label="$t('forms.fields.admin_role')">
          <NSelect v-model:value="form.roles" :options="rolesOptions" clearable multiple type="text"
            placeholder="Be rolės..." />
        </NFormItem>
      </FormElement>

      <FormElement>
        <template #title>
          {{ $t("Įvardžiai") }}
        </template>
        <template #description>
          <p>
            Jei nurodytas įvardis, asmens pareigybių pavadinimo galūnė automatiškai bus pakeista (nebent tai išjungta
            asmens-pareigybės įraše.
          </p>
          <p> Taip pat, pasirinkus įvardžių rodymą viešai, jis bus rodomas prie asmens vardo, pavardės
          </p>
        </template>
        <div class="grid gap-4 lg:grid-cols-2">
          <NFormItem :label="$t('forms.fields.pronouns')">
            <MultiLocaleInput v-model:input="form.pronouns" :placeholder="{ lt: 'Jie/jų', en: 'They/them' }" />
          </NFormItem>
          <NFormItem :label="$t('forms.fields.show_pronouns')">
            <NSwitch v-model:value="form.show_pronouns" :disabled="form.pronouns === ''">
              <template #checked>
                <span>Įvardžiai rodomi viešai</span>
              </template>
              <template #unchecked>
                <span>Įvardžiai nerodomi viešai</span>
              </template>
            </NSwitch>
          </NFormItem>
        </div>
      </FormElement>

      <FormElement>
        <template #title>
          {{ $t("forms.context.user_duties") }}
        </template>
        <template #description>
          <p>
            Kiekvienas asmuo gali turėti daugiau nei vieną pareigybę, pagal
            kurią gali atlikti veiksmus platformoje, taip pat būti rodomas (-a)
            viešame vusa.lt puslapyje.
          </p>
          <p class="mt-4">
            Pareigybės turėtų būti kuriamos tik tada, jeigu institucijoje tokios
            pareigybės nėra.
          </p>
        </template>
        <NFormItem>
          <template #label>
            <div class="flex items-center gap-2">
              <span><strong>{{ $t("Pareigybės") }}</strong></span><a target="_blank" :href="route('duties.create')">
                <NButton size="tiny" round secondary><template #icon>
                    <IFluentAdd24Filled />
                  </template>Sukurti naują pareigybę?</NButton>
              </a>
              <NButton class="ml-auto" size="tiny" round @click="handleChangeDutyShowMode">Pakeisti rodymo būdą
              </NButton>
            </div>
          </template>
          <NTransfer ref="transfer" v-model:value="form.current_duties" :options="flattenDutyOptions"
            :render-source-list="dutyShowMode === 'tree' ? renderSourceList : undefined
              " :render-target-label="renderTargetLabel" source-filterable />
        </NFormItem>
        <NCard class="mb-4">
          <h4>Užimamos pareigos</h4>
          <NDataTable :data="user.current_duties" :columns="existingDutyColumns" :bordered="false" size="small" />
        </NCard>
        <NCard class="mb-4">
          <h4>Buvusios pareigos</h4>
          <NDataTable :data="user.previous_duties" :columns="previousDutyColumns" :bordered="false" size="small" />
        </NCard>
        <!-- <template v-if="users.previous_duties.length > 0">
          <p>Praėjusios pareigos:</p>
        </template> -->
      </FormElement>
      <FormElement>
        <template #title>
          {{ $t("forms.context.additional_info") }}
        </template>
        <template v-if="user.last_action">
          <p>
            Paskutinį kartą prisijungė {{ formatStaticTime(user.last_action) }}.
          </p>
        </template>
        <!-- <template v-else-if="modelRoute === 'users.update'">
          <p class="mb-2">
            Šis asmuo dar niekada neprisijungė prie sistemos.
          </p>
          <NPopconfirm style="max-width: 400px" @positive-click="sendWelcomeEmail">
            <span>Bus išsiųstas atstovo rolę supažindinantis laiškas apie
              mano.vusa.lt, paštu&nbsp;
              <span class="underline">{{ user.email }}</span>
            </span>
            <template #trigger>
              <NButton>Siųsti laišką</NButton>
            </template>
          </NPopconfirm>
          <NButton tag="a" size="tiny" text :href="route('users.renderWelcomeEmail', user.id)" target="_blank"
            class="ml-2 align-middle">
            <template #icon>
              <IFluentEye24Filled />
            </template>
          </NButton>
        </template> -->
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <DeleteModelButton v-if="deleteModelRoute" :form="form" :model-route="deleteModelRoute" />
      <UpsertModelButton :form="form" :model-route="modelRoute" />
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import {
  type DataTableColumns,
  NButton,
  NIcon,
  NTree,
  type TransferRenderSourceList,
  type TreeOption,
} from "naive-ui";
import { computed, h, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

import Delete24Regular from "~icons/fluent/delete24-regular";
import Eye16Regular from "~icons/fluent/eye16-regular";
import PersonEdit24Regular from "~icons/fluent/person-edit24-regular";

import { formatStaticTime } from "@/Utils/IntlTime";
import DeleteModelButton from "@/Components/Buttons/DeleteModelButton.vue";
import FormElement from "./FormElement.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import UploadImageWithCropper from "../Buttons/UploadImageWithCropper.vue";
import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";

const props = defineProps<{
  user: App.Entities.User;
  roles: App.Entities.Role[];
  padaliniaiWithDuties: App.Entities.Padalinys[];
  permissablePadaliniai: App.Entities.Padalinys[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const dutyShowMode = ref<"tree" | "transfer">("tree");
const handleChangeDutyShowMode = () => {
  dutyShowMode.value = dutyShowMode.value === "tree" ? "transfer" : "tree";
};

const form = useForm("user", props.user);
form.roles = props.user.roles?.map((role) => role.id);

if (Array.isArray(form.pronouns)) {
  form.pronouns = { lt: "", en: "" };
}

const dutyOptions: TreeOption[] = props.padaliniaiWithDuties.map(
  (padalinys) => {
    return ({
      label: padalinys.shortname,
      value: padalinys.id,
      checkboxDisabled: true,
      children: padalinys.institutions?.map((institution) => ({
        label: institution.name,
        value: institution.id,
        checkboxDisabled: true,
        children: institution.duties?.map((duty) => ({
          label: duty.name,
          value: duty.id,
        })),
      })),
    });
  },
).filter((padalinys) => props.permissablePadaliniai.some((permissable) => permissable.id === padalinys.value));

// check if email contains "vusa.lt"
const isUserEmailMaybeDutyEmail = computed(() => {
  return props.user.email.includes("vusa.lt");
});

const existingDutyColumns: DataTableColumns = [
  {
    title: "Pavadinimas",
    key: "name",
    render(row) {
      return (
        <a
          target="_blank"
          href={route("duties.edit", { id: row.id })}
          class="flex-inline gap-2"
        >
          {row.name}
        </a>
      );
    },
  },
  {
    title: "Pradžia",
    key: "pivot.start_date",
  },
  {
    title: "Pabaiga",
    key: "pivot.end_date",
  },
  {
    key: "actions",
    render(row) {
      return (
        <NButton
          secondary
          size="tiny"
          tag="a"
          href={route("dutiables.edit", row.pivot.id as string)}
          target="_blank"
        >
          {{
            icon: () => <NIcon component={PersonEdit24Regular} />,
          }}
        </NButton>
      );
    },
  },
];

const previousDutyColumns: DataTableColumns = [
  ...existingDutyColumns,
  {
    key: "delete",
    render(row) {
      return (
        <NButton
          size="tiny"
          type="error"
          onClick={() =>
            router.delete(route("dutiables.destroy", row.pivot.id), {
              preserveState: true,
              preserveScroll: true,
            })
          }
        >
          {{
            icon: () => <NIcon component={Delete24Regular} />,
          }}
        </NButton>
      );
    },
  },
];

const renderLabel = ({ option }: { option: TreeOption }) => {
  // jsx element
  // if value is integer then it's a padalinys and doesn't have additional button
  if (typeof option.value === "number") {
    return <span>{option.label}</span>;
  }

  // jsx element with button
  // ! assumption that if checkbox is enabled then it's a duty
  return (
    <span class="inline-flex items-center gap-2">
      {option.label}
      <a
        target="_blank"
        href={
          option.checkboxDisabled
            ? route("institutions.edit", option.value as string)
            : route("duties.edit", option.value as string)
        }
      >
        <NButton size="tiny" text>
          {{
            icon: <NIcon component={Eye16Regular} />,
          }}
        </NButton>
      </a>
    </span>
  );
};

const renderTargetLabel = ({ option }: { option: TreeOption }) => {
  // jsx element
  // if value is integer then it's a padalinys and doesn't have additional button
  if (typeof option.value === "number") {
    return <span>{option.label}</span>;
  }

  // jsx element with button
  // ! assumption that if checkbox is enabled then it's a duty
  return (
    <span class="inline-flex items-center gap-2">
      {option.label}
      <a target="_blank" href={route("duties.edit", option.value as string)}>
        <NButton size="tiny" text>
          {{
            icon: <NIcon component={Eye16Regular} />,
          }}
        </NButton>
      </a>
    </span>
  );
};

const flattenDutyOptions = computed(() => {
  return dutyOptions.flatMap(
    (padalinys) =>
      padalinys.children?.flatMap(
        (institution) =>
          institution.children?.map((duty) => {
            return {
              label:
                dutyShowMode.value === "tree"
                  ? duty.label
                  : `${duty.label} (${institution.label})`,
              value: duty.value,
              padalinysId: padalinys.value,
            };
          }),
      ),
  ).filter((duty) => props.permissablePadaliniai.some((permissable) => permissable.id === duty?.padalinysId));
});

const rolesOptions = props.roles.map((role) => ({
  label: role.name,
  value: role.id,
}));

const emailOptions = computed(() => {
  return usePage().props.auth?.user.padaliniai.map((padalinys) => {
    const prefix = form.email?.split("@")[0];
    return {
      label: `${prefix}@${padalinys.alias}.stud.vu.lt`,
      value: `${prefix}@${padalinys.alias}.stud.vu.lt`,
    };
  });
});

form.current_duties = props.user.current_duties?.map((duty) => duty.id);

// tsx render Ntree
const renderSourceList: TransferRenderSourceList = ({ onCheck, pattern }) => {
  return h(NTree, {
    style: "margin: 0 4px;",
    keyField: "value",
    checkable: true,
    selectable: false,
    blockLine: true,
    virtualScroll: true,
    renderLabel: renderLabel,
    data: dutyOptions,
    pattern,
    checkedKeys: form.current_duties,
    onUpdateCheckedKeys: (checkedKeys: Array<string | number>) => {
      onCheck(checkedKeys);
    },
  });
};

const sendWelcomeEmail = () => {
  router.post(
    route("users.sendWelcomeEmail", props.user.id as number),
    {},
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
};
</script>
