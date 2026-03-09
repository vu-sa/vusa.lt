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
      <FormFieldWrapper id="name" :label="$t('forms.fields.name_and_surname')" required>
        <Input v-model="form.name" :disabled="user.name !== ''" type="text"
          placeholder="Įrašyti vardą ir pavardę" />
      </FormFieldWrapper>

      <FormFieldWrapper id="email" label="Studentinis el. paštas" required>
        <div v-if="isUserEmailMaybeDutyEmail" class="mb-1 text-xs text-amber-600 dark:text-amber-400">
          Jeigu <strong>{{ user.email }}</strong> yra pareigybinis el.
          paštas (ir panašu, kad šiuo atveju taip ir yra), jį reikėtų
          pakeisti į studentinį.
        </div>
        <Input v-model="form.email"
          placeholder="vardas.pavarde@padalinys.stud.vu.lt" />
      </FormFieldWrapper>

      <div class="grid gap-4 lg:grid-cols-2">
        <FormFieldWrapper id="phone" :label="$t('forms.fields.phone')">
          <Input v-model="form.phone" placeholder="+370 612 34 567" />
        </FormFieldWrapper>
        <FormFieldWrapper id="facebook_url" :label="$t('forms.fields.facebook_url')">
          <Input v-model="form.facebook_url" placeholder="https://www.facebook.com/..." />
        </FormFieldWrapper>
      </div>

      <FormFieldWrapper id="profile_photo_path" :label="$t('forms.fields.picture')">
        <ImageUpload v-model:url="form.profile_photo_path" mode="immediate" folder="contacts" cropper :existing-url="user?.profile_photo_path" />
      </FormFieldWrapper>

      <FormFieldWrapper v-if="$page.props.auth?.user?.isSuperAdmin" id="roles" :label="$t('forms.fields.admin_role')">
        <MultiSelect
          v-model="selectedRoles"
          :options="rolesOptions"
          label-field="label"
          value-field="value"
          placeholder="Be rolės..."
        />
      </FormFieldWrapper>
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
        <FormFieldWrapper id="pronouns" :label="$t('forms.fields.pronouns')">
          <MultiLocaleInput v-model:input="form.pronouns" :placeholder="{ lt: 'Jie/jų', en: 'They/them' }" />
        </FormFieldWrapper>
        <FormFieldWrapper id="show_pronouns" :label="$t('forms.fields.show_pronouns')">
          <div class="flex items-center gap-2">
            <Switch :model-value="form.show_pronouns" :disabled="!form.pronouns?.lt && !form.pronouns?.en" @update:model-value="form.show_pronouns = $event" />
            <span class="text-sm text-muted-foreground">
              {{ form.show_pronouns ? 'Įvardžiai rodomi viešai' : 'Įvardžiai nerodomi viešai' }}
            </span>
          </div>
        </FormFieldWrapper>
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
      <div class="space-y-2">
        <div class="flex items-center gap-2">
          <Label><strong>{{ $t("Pareigybės") }}</strong></Label>
          <a target="_blank" :href="route('duties.create')">
            <Button size="xs" variant="secondary">
              <IFluentAdd24Filled />
              Sukurti naują pareigybę?
            </Button>
          </a>
          <Button class="ml-auto" size="xs" variant="outline" @click="handleChangeDutyShowMode">
            Pakeisti rodymo būdą
          </Button>
        </div>
        <TransferList v-if="dutyShowMode === 'tree'" v-model="form.current_duties" :options="flattenDutyOptions">
          <template #source="{ filter }">
            <Tree
              v-model="form.current_duties"
              :items="dutyOptions"
              :get-key="(item) => String(item.value)"
              :get-label="(item) => item.label"
              :is-item-disabled="(item) => item.checkboxDisabled ?? false"
              :filter="filter"
              multiple
              class="p-1"
            >
              <template #item="{ item }">
                <span class="inline-flex items-center gap-2">
                  {{ item.label }}
                  <a
                    v-if="typeof item.value !== 'number'"
                    target="_blank"
                    :href="item.checkboxDisabled
                      ? route('institutions.edit', item.value)
                      : route('duties.edit', item.value)"
                  >
                    <Button variant="ghost" size="icon-xs" @click.stop>
                      <Eye16Regular />
                    </Button>
                  </a>
                </span>
              </template>
            </Tree>
          </template>
          <template #target-label="{ option }">
            <span class="inline-flex items-center gap-2">
              {{ option.label }}
              <a target="_blank" :href="route('duties.edit', option.value)">
                <Button variant="ghost" size="icon-xs" @click.stop>
                  <Eye16Regular />
                </Button>
              </a>
            </span>
          </template>
        </TransferList>
        <TransferList v-else v-model="form.current_duties" :options="flattenDutyOptions">
          <template #target-label="{ option }">
            <span class="inline-flex items-center gap-2">
              {{ option.label }}
              <a target="_blank" :href="route('duties.edit', option.value)">
                <Button variant="ghost" size="icon-xs" @click.stop>
                  <Eye16Regular />
                </Button>
              </a>
            </span>
          </template>
        </TransferList>
      </div>
      <Card class="mb-4 h-auto">
        <CardHeader class="pb-2">
          <CardTitle class="text-base">Užimamos pareigos</CardTitle>
        </CardHeader>
        <CardContent>
          <SimpleDataTable :data="user.current_duties ?? []" :columns="existingDutyColumns" :enable-pagination="false" :enable-filtering="false" />
        </CardContent>
      </Card>
      <Card class="mb-4 h-auto">
        <CardHeader class="pb-2">
          <CardTitle class="text-base">Buvusios pareigos</CardTitle>
        </CardHeader>
        <CardContent>
          <SimpleDataTable :data="user.previous_duties ?? []" :columns="previousDutyColumns" :enable-pagination="false" :enable-filtering="false" />
        </CardContent>
      </Card>
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
                <Badge :variant="user.has_password ? 'success' : 'warning'" size="tiny">
                  {{ user.has_password ? $t("Nustatytas") : $t("Nenustatytas") }}
                </Badge>
              </span>
            </div>
            <div class="flex gap-2">
              <AlertDialog>
                <AlertDialogTrigger as-child>
                  <Button size="sm">
                    {{ $t("Generuoti naują slaptažodį") }}
                  </Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                  <AlertDialogHeader>
                    <AlertDialogTitle>{{ $t("Ar tikrai norite sugeneruoti naują slaptažodį šiam naudotojui?") }}</AlertDialogTitle>
                    <AlertDialogDescription v-if="user.has_password" class="text-orange-500">
                      {{ $t("Dėmesio: Tai pakeis esamą naudotojo slaptažodį!") }}
                    </AlertDialogDescription>
                  </AlertDialogHeader>
                  <AlertDialogFooter>
                    <AlertDialogCancel>{{ $t("Atšaukti") }}</AlertDialogCancel>
                    <AlertDialogAction @click="generatePassword">{{ $t("Generuoti") }}</AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>

              <AlertDialog v-if="user.has_password">
                <AlertDialogTrigger as-child>
                  <Button size="sm" variant="destructive">
                    {{ $t("Ištrinti slaptažodį") }}
                  </Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                  <AlertDialogHeader>
                    <AlertDialogTitle>{{ $t("Ar tikrai norite ištrinti šio naudotojo slaptažodį?") }}</AlertDialogTitle>
                    <AlertDialogDescription class="text-orange-500">
                      {{ $t("Dėmesio: Naudotojas nebegalės prisijungti su slaptažodžiu!") }}
                    </AlertDialogDescription>
                  </AlertDialogHeader>
                  <AlertDialogFooter>
                    <AlertDialogCancel>{{ $t("Atšaukti") }}</AlertDialogCancel>
                    <AlertDialogAction @click="deletePassword">{{ $t("Ištrinti") }}</AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>
            </div>
          </div>

          <!-- Display generated password if available -->
          <div v-if="$page.props.flash.data" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <h5 class="font-semibold mb-2">{{ $t("Sugeneruotas slaptažodis:") }}</h5>
            <div class="relative mb-2">
              <Input
                readonly
                :model-value="$page.props.flash.data"
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
import type { ColumnDef } from "@tanstack/vue-table";
import { computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";

import Delete24Regular from "~icons/fluent/delete24-regular";
import Eye16Regular from "~icons/fluent/eye16-regular";
import PersonEdit24Regular from "~icons/fluent/person-edit24-regular";
import IFluentCopy16Regular from "~icons/fluent/copy16-regular";

import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from "@/Components/ui/alert-dialog";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { MultiSelect } from "@/Components/ui/multi-select";
import { Switch } from "@/Components/ui/switch";
import { TransferList } from "@/Components/ui/transfer-list";
import { Tree } from "@/Components/ui/tree";
import { ImageUpload } from "@/Components/ui/upload";

import { formatStaticTime } from "@/Utils/IntlTime";
import AdminForm from "./AdminForm.vue";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import SimpleDataTable from "@/Components/Tables/SimpleDataTable.vue";

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

const rolesOptions = props.roles.map((role) => ({
  label: role.name,
  value: role.id,
}));

// Bridge object array <-> id array for MultiSelect
const selectedRoles = computed({
  get: () => rolesOptions.filter(opt => form.roles?.includes(opt.value)),
  set: (items) => { form.roles = items.map(item => item.value); },
});

interface DutyTreeOption {
  label: string
  value: string | number
  checkboxDisabled?: boolean
  children?: DutyTreeOption[]
}

const dutyOptions: DutyTreeOption[] = props.tenantsWithDuties.map(
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

const existingDutyColumns: ColumnDef<any, any>[] = [
  {
    accessorKey: "name",
    header: () => "Pavadinimas",
    cell: ({ row }) => (
      <a
        target="_blank"
        href={route("duties.edit", { id: row.original.id })}
        class="flex-inline gap-2"
      >
        {row.original.name}
      </a>
    ),
  },
  {
    id: "start_date",
    header: () => "Pradžia",
    cell: ({ row }) => formatStaticTime(row.original.pivot.start_date),
  },
  {
    id: "end_date",
    header: () => "Pabaiga",
    cell: ({ row }) => row.original.pivot?.end_date ? formatStaticTime(row.original.pivot.end_date) : "Nenurodyta",
  },
  {
    id: "actions",
    cell: ({ row }) => (
      <Button
        variant="secondary"
        size="icon-xs"
        as="a"
        href={route("dutiables.edit", row.original.pivot.id as string)}
        target="_blank"
      >
        <PersonEdit24Regular />
      </Button>
    ),
  },
];

const previousDutyColumns: ColumnDef<any, any>[] = [
  ...existingDutyColumns,
  {
    id: "delete",
    cell: ({ row }) => (
      <Button
        size="icon-xs"
        variant="destructive"
        onClick={() =>
          router.delete(route("dutiables.destroy", row.original.pivot.id), {
            preserveState: true,
            preserveScroll: true,
          })
        }
      >
        <Delete24Regular />
      </Button>
    ),
  },
];

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

form.current_duties = props.user.current_duties?.map((duty) => duty.id);

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
  const $page = usePage();
  navigator.clipboard.writeText($page.props.flash.generated_password).then(() => {
    hasCopied.value = true;
    setTimeout(() => {
      hasCopied.value = false;
    }, 2000);
  });
};
</script>
