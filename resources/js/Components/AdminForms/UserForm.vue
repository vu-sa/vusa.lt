<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
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
            <InfoPopover v-if="isUserEmailMaybeDutyEmail">
              Jeigu <strong>{{ user.email }}</strong> yra pareigybinis el.
              paštas (ir panašu, kad šiuo atveju taip ir yra 😊), jį reikėtų
              pakeisti į studentinį.
            </InfoPopover>
          </div>
        </template>
        <NInput v-model:value="form.email"
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
        <ImageUpload v-model:url="form.profile_photo_path" mode="immediate" folder="contacts" cropper :existing-url="user?.profile_photo_path" />
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
        <p>
          Taip pat, pasirinkus įvardžių rodymą viešai, jis bus rodomas prie asmens vardo, pavardės
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
              <Button size="xs" variant="secondary">
                <IFluentAdd24Filled />
                Sukurti naują pareigybę?
              </Button>
            </a>
            <Button class="ml-auto" size="xs" variant="outline" @click="handleChangeDutyShowMode">
              Pakeisti rodymo būdą
            </Button>
          </div>
        </template>
        <NTransfer ref="transfer" v-model:value="form.current_duties" :options="flattenDutyOptions" :render-source-list="dutyShowMode === 'tree' ? renderSourceList : undefined
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
      <!-- Password Management Section - Only for Super Admins -->
      <template v-if="$page.props.auth?.user?.isSuperAdmin">
        <div class="border-t border-gray-200 pt-4 mt-4">
          <h4 class="font-semibold text-lg mb-2">{{ $t("Slaptažodžio valdymas") }}</h4>
          <div class="flex items-center gap-4">
            <div>
              <span class="inline-flex items-center gap-2">
                <span>{{ $t("Slaptažodžio būsena") }}:</span>
                <NTag :type="user.has_password ? 'success' : 'warning'" size="small">
                  {{ user.has_password ? $t("Nustatytas") : $t("Nenustatytas") }}
                </NTag>
              </span>
            </div>
            <div class="flex gap-2">
              <NPopconfirm @positive-click="generatePassword">
                <template #trigger>
                  <Button size="sm">
                    {{ $t("Generuoti naują slaptažodį") }}
                  </Button>
                </template>
                <span>{{ $t("Ar tikrai norite sugeneruoti naują slaptažodį šiam naudotojui?") }}</span>
                <template v-if="user.has_password">
                  <p class="text-orange-500 mt-1">{{ $t("Dėmesio: Tai pakeis esamą naudotojo slaptažodį!") }}</p>
                </template>
              </NPopconfirm>
              
              <NPopconfirm v-if="user.has_password" @positive-click="deletePassword">
                <template #trigger>
                  <Button size="sm" variant="destructive">
                    {{ $t("Ištrinti slaptažodį") }}
                  </Button>
                </template>
                <span>{{ $t("Ar tikrai norite ištrinti šio naudotojo slaptažodį?") }}</span>
                <p class="text-orange-500 mt-1">{{ $t("Dėmesio: Naudotojas nebegalės prisijungti su slaptažodžiu!") }}</p>
              </NPopconfirm>
            </div>
          </div>
          
          <!-- Display generated password if available -->
          <div v-if="$page.props.flash.data" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <h5 class="font-semibold mb-2">{{ $t("Sugeneruotas slaptažodis:") }}</h5>
            <div class="relative mb-2">
              <NInput 
                readonly 
                :value="$page.props.flash.data" 
                class="font-mono"
              />
              <Button 
                size="sm" 
                variant="outline"
                class="absolute right-2 top-1/2 transform -translate-y-1/2"
                @click="copyPasswordToClipboard"
              >
                <IFluentCopy16Regular />
                {{ hasCopied ? $t("Nukopijuota!") : $t("Kopijuoti") }}
              </Button>
            </div>
            <p class="text-sm text-orange-600">
              {{ $t("Šis slaptažodis bus rodomas tik vieną kartą! Įsitikinkite, kad jį išsaugojote saugiai.") }}
            </p>
          </div>
        </div>
      </template>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="tsx">
import {
  type DataTableColumns,
  NIcon,
  NTree,
  type TransferRenderSourceList,
  type TreeOption,
} from "naive-ui";
import { computed, h, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";

import Delete24Regular from "~icons/fluent/delete24-regular";
import Eye16Regular from "~icons/fluent/eye16-regular";
import PersonEdit24Regular from "~icons/fluent/person-edit24-regular";
import IFluentCopy16Regular from "~icons/fluent/copy16-regular";

import { formatStaticTime } from "@/Utils/IntlTime";
import FormElement from "./FormElement.vue";
import InfoPopover from "../Buttons/InfoPopover.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import { ImageUpload } from "@/Components/ui/upload";
import AdminForm from "./AdminForm.vue";

const props = defineProps<{
  user: App.Entities.User;
  roles: App.Entities.Role[];
  tenantsWithDuties: App.Entities.Tenant[];
  permissableTenants: App.Entities.Tenant[];
  rememberKey?: "CreateUser";
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const dutyShowMode = ref<"tree" | "transfer">("tree");
const handleChangeDutyShowMode = () => {
  dutyShowMode.value = dutyShowMode.value === "tree" ? "transfer" : "tree";
};

const form = props.rememberKey
  ? useForm(props.rememberKey, props.user)
  : useForm(props.user);

form.roles = props.user.roles?.map((role) => role.id);

if (Array.isArray(form.pronouns)) {
  form.pronouns = { lt: "", en: "" };
}

const dutyOptions: TreeOption[] = props.tenantsWithDuties.map(
  (tenant) => {
    return ({
      label: tenant.shortname,
      value: tenant.id,
      checkboxDisabled: true,
      children: tenant.institutions?.map((institution) => ({
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
).filter((tenant) => props.permissableTenants.some((permissable) => permissable.id === tenant.value));

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
    render(row) {
      return formatStaticTime(row.pivot.start_date);
    },
  },
  {
    title: "Pabaiga",
    key: "pivot.end_date",
    render(row) {
      return row.pivot?.end_date ? formatStaticTime(row.pivot.end_date) : "Nenurodyta";
    },
  },
  {
    key: "actions",
    render(row) {
      return (
        <Button
          variant="secondary"
          size="icon-xs"
          as="a"
          href={route("dutiables.edit", row.pivot.id as string)}
          target="_blank"
        >
          <NIcon component={PersonEdit24Regular} />
        </Button>
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
        <Button
          size="icon-xs"
          variant="destructive"
          onClick={() =>
            router.delete(route("dutiables.destroy", row.pivot.id), {
              preserveState: true,
              preserveScroll: true,
            })
          }
        >
          <NIcon component={Delete24Regular} />
        </Button>
      );
    },
  },
];

const renderLabel = ({ option }: { option: TreeOption }) => {
  // jsx element
  // if value is integer then it's a tenant and doesn't have additional button
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
        <Button size="icon-xs" variant="link">
          <NIcon component={Eye16Regular} />
        </Button>
      </a>
    </span>
  );
};

const renderTargetLabel = ({ option }: { option: TreeOption }) => {
  // jsx element
  // if value is integer then it's a tenant and doesn't have additional button
  if (typeof option.value === "number") {
    return <span>{option.label}</span>;
  }

  // jsx element with button
  // ! assumption that if checkbox is enabled then it's a duty
  return (
    <span class="inline-flex items-center gap-2">
      {option.label}
      <a target="_blank" href={route("duties.edit", option.value as string)}>
        <Button size="icon-xs" variant="link">
          <NIcon component={Eye16Regular} />
        </Button>
      </a>
    </span>
  );
};

const flattenDutyOptions = computed(() => {
  return dutyOptions.flatMap(
    (tenant) =>
      tenant.children?.flatMap(
        (institution) =>
          institution.children?.map((duty) => {
            return {
              label:
                dutyShowMode.value === "tree"
                  ? duty.label
                  : `${duty.label} (${institution.label})`,
              value: duty.value,
              tenantId: tenant.value,
            };
          }),
      ),
  ).filter((duty) => props.permissableTenants.some((permissable) => permissable.id === duty?.tenantId));
});

const rolesOptions = props.roles.map((role) => ({
  label: role.name,
  value: role.id,
}));

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

const hasPassword = computed(() => !!props.user.password);
const hasCopied = ref(false);

const generatePassword = () => {
  router.post(
    route("users.generatePassword", props.user.id as number),
    {},
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
};

const deletePassword = () => {
  router.delete(
    route("users.deletePassword", props.user.id as number),
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
};

const copyPasswordToClipboard = () => {
  navigator.clipboard.writeText($page.props.flash.generated_password).then(() => {
    hasCopied.value = true;
    setTimeout(() => {
      hasCopied.value = false;
    }, 2000);
  });
};
</script>
