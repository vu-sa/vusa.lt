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

      <FormFieldWrapper id="email" label="El. paštas" required>
        <div v-if="isUserEmailMaybeDutyEmail" class="mb-1 text-xs text-amber-600 dark:text-amber-400">
          Jeigu <strong>{{ user.email }}</strong> nėra pareigybinis el.
          paštas (<code>@vusa.lt</code> dažniausiai naudojami pareigybėms), pagal gerąsias praktikas jį reikėtų
          pakeisti į studentinį arba kitą VU paštą.
        </div>
        <Input v-model="form.email"
          placeholder="vardas.pavarde@stud.vu.lt" />
        <div v-if="currentDutiesWithVusaEmail.length > 0" class="mt-2 rounded-md bg-blue-50 p-2.5 text-xs dark:bg-blue-950">
          <p class="mb-1.5 font-medium text-blue-800 dark:text-blue-200">
            Šie pareigybiniai el. paštai taip pat leidžia prisijungti prie sistemos:
          </p>
          <ul class="space-y-1">
            <li v-for="duty in currentDutiesWithVusaEmail" :key="duty.id" class="flex items-center gap-1.5 text-blue-700 dark:text-blue-300">
              <span class="truncate font-medium">{{ duty.name }}</span>
              <span class="text-blue-400">→</span>
              <code class="rounded bg-blue-100 px-1 py-0.5 text-[10px] dark:bg-blue-900">{{ duty.email }}</code>
            </li>
          </ul>
        </div>
        <div v-else-if="!user.current_duties?.some(d => d.email)" class="mt-2 text-xs text-muted-foreground">
          Šis el. paštas yra vienintelis naudojamas prisijungimui.
        </div>
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
        <ImageUpload
          v-model:url="form.profile_photo_path"
          v-model:focal-point-value="form.profile_photo_focal_point"
          mode="immediate"
          folder="contacts"
          cropper
          focal-point
          preview-aspect="4/3"
          :existing-url="user?.profile_photo_path"
        />
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
        <div class="flex items-center gap-2 mb-2">
          <Label><strong>{{ $t("Pareigybės") }}</strong></Label>
          <Button size="xs" variant="secondary" as="a" :href="route('duties.create')" target="_blank">
            <IFluentAdd24Filled />
            Sukurti naują pareigybę?
          </Button>
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
              :filter
              multiple
              class="p-1"
            >
              <template #item="{ item }">
                <span class="inline-flex items-center gap-2">
                  {{ item.label }}
                  <Button
                    v-if="typeof item.value !== 'number'"
                    variant="ghost"
                    size="icon-xs"
                    as="a"
                    :href="item.checkboxDisabled
                      ? route('institutions.edit', item.value)
                      : route('duties.edit', item.value)"
                    target="_blank"
                    @click.stop
                  >
                    <Eye16Regular />
                  </Button>
                </span>
              </template>
            </Tree>
          </template>
          <template #target-label="{ option }">
            <span class="inline-flex items-center gap-2">
              {{ option.label }}
              <Button variant="ghost" size="icon-xs" as="a" :href="route('duties.edit', option.value)" target="_blank" @click.stop>
                <Eye16Regular />
              </Button>
            </span>
          </template>
        </TransferList>
        <TransferList v-else v-model="form.current_duties" :options="flattenDutyOptions">
          <template #target-label="{ option }">
            <span class="inline-flex items-center gap-2">
              {{ option.label }}
              <Button variant="ghost" size="icon-xs" as="a" :href="route('duties.edit', option.value)" target="_blank" @click.stop>
                <Eye16Regular />
              </Button>
            </span>
          </template>
        </TransferList>
      </div>
      <Card class="mb-4 h-auto">
        <CardHeader class="pb-2">
          <CardTitle class="text-base">
            Užimamos pareigos
          </CardTitle>
        </CardHeader>
        <CardContent>
          <SimpleDataTable :data="user.current_duties ?? []" :columns="existingDutyColumns" :enable-pagination="false" :enable-filtering="false" />
        </CardContent>
      </Card>
      <Card class="mb-4 h-auto">
        <CardHeader class="pb-2">
          <CardTitle class="text-base">
            Buvusios pareigos
          </CardTitle>
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
          <h4 class="font-semibold text-lg mb-2">
            {{ $t("Slaptažodžio valdymas") }}
          </h4>
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
                    <AlertDialogAction @click="generatePassword">
                      {{ $t("Generuoti") }}
                    </AlertDialogAction>
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
                    <AlertDialogAction @click="deletePassword">
                      {{ $t("Ištrinti") }}
                    </AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>
            </div>
          </div>

          <!-- Display generated password if available -->
          <div v-if="$page.props.flash.data" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <h5 class="font-semibold mb-2">
              {{ $t("Sugeneruotas slaptažodis:") }}
            </h5>
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

    <AccessChangeWarningDialog :open="accessChangeOpen" :report="accessChangeReport"
      @update:open="accessChangeOpen = $event" @confirm="confirmAccessChange" @cancel="cancelAccessChange" />
  </AdminForm>
</template>

<script setup lang="tsx">
import type { ColumnDef } from '@tanstack/vue-table';
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';

import AdminForm from './AdminForm.vue';
import AccessChangeWarningDialog from './AccessChangeWarningDialog.vue';
import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';

import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import { useApiMutation } from '@/Composables/useApi';
import Delete24Regular from '~icons/fluent/delete24-regular';
import Eye16Regular from '~icons/fluent/eye16-regular';
import PersonEdit24Regular from '~icons/fluent/person-edit24-regular';
import IFluentCopy16Regular from '~icons/fluent/copy16-regular';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/Components/ui/alert-dialog';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { MultiSelect } from '@/Components/ui/multi-select';
import { Switch } from '@/Components/ui/switch';
import { TransferList } from '@/Components/ui/transfer-list';
import { Tree } from '@/Components/ui/tree';
import { ImageUpload } from '@/Components/ui/upload';
import { formatStaticTime } from '@/Utils/IntlTime';
import SimpleDataTable from '@/Components/Tables/SimpleDataTable.vue';

const props = defineProps<{
  user: App.Entities.User;
  roles: App.Entities.Role[];
  tenantsWithDuties: App.Entities.Tenant[];
  permissableTenants: App.Entities.Tenant[];
  rememberKey?: 'CreateUser';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const dutyShowMode = ref<'tree' | 'transfer'>('tree');
const handleChangeDutyShowMode = () => {
  dutyShowMode.value = dutyShowMode.value === 'tree' ? 'transfer' : 'tree';
};

// Deleting a duty assignment may strip the acting user's own access; guard the
// removal so the warning dialog surfaces instead of a silent no-op rollback.
const {
  report: accessChangeReport,
  open: accessChangeOpen,
  guardedSubmit: guardedDutiableDestroy,
  confirm: confirmAccessChange,
  cancel: cancelAccessChange,
} = useAccessChangeGuard();

const deleteDutiable = (pivotId: string) => {
  guardedDutiableDestroy(acknowledge =>
    router.delete(route('dutiables.destroy', pivotId), {
      data: { acknowledge_access_change: acknowledge },
      preserveState: true,
      preserveScroll: true,
    }),
  );
};

const form = props.rememberKey
  ? useForm(props.rememberKey, props.user)
  : useForm(props.user);

form.roles = props.user.roles?.map(role => role.id);

if (Array.isArray(form.pronouns)) {
  form.pronouns = { lt: '', en: '' };
}

const rolesOptions = props.roles.map(role => ({
  label: role.name,
  value: role.id,
}));

// Bridge object array <-> id array for MultiSelect
const selectedRoles = computed({
  get: () => rolesOptions.filter(opt => form.roles?.includes(opt.value)),
  set: (items) => { form.roles = items.map(item => item.value); },
});

interface DutyTreeOption {
  label: string;
  value: string | number;
  checkboxDisabled?: boolean;
  children?: DutyTreeOption[];
}

const dutyOptions: DutyTreeOption[] = props.tenantsWithDuties.map(
  (tenant) => {
    return ({
      label: tenant.shortname,
      value: tenant.id,
      checkboxDisabled: true,
      children: tenant.institutions?.map(institution => ({
        label: institution.name,
        value: institution.id,
        checkboxDisabled: true,
        children: institution.duties?.map(duty => ({
          label: duty.name,
          value: duty.id,
        })),
      })),
    });
  },
).filter(tenant => props.permissableTenants.some(permissable => permissable.id === tenant.value));

// check if user email looks like a duty email (@vusa.lt)
const isUserEmailMaybeDutyEmail = computed(() => {
  return props.user.email.toLowerCase().endsWith('@vusa.lt');
});

const currentDutiesWithVusaEmail = computed(() => {
  return props.user.current_duties?.filter(duty => duty.email?.toLowerCase().endsWith('@vusa.lt')) ?? [];
});

// Inline editing state for dutiable additional_email
const editingDutiableId = ref<string | null>(null);
const editingEmail = ref('');

const startEditingEmail = (dutiableId: string | undefined, currentEmail: string | null) => {
  if (!dutiableId) return;
  editingDutiableId.value = dutiableId;
  editingEmail.value = currentEmail ?? '';
};

const updateDutiableUrl = ref('');
const updateDutiableBody = ref<{ additional_email: string | null }>({ additional_email: null });

const { execute: executeDutiableUpdate, isFetching: isUpdatingEmail, isSuccess: emailUpdateSuccess, error: emailUpdateError } = useApiMutation(
  updateDutiableUrl,
  'PATCH',
  updateDutiableBody,
  { showSuccessToast: true, successMessage: 'Kontaktinis el. paštas atnaujintas' },
);

const finishEditingEmail = async (dutiableId: string) => {
  if (!editingDutiableId.value || editingDutiableId.value !== dutiableId) return;

  const duty = props.user.current_duties?.find(d => d.pivot?.id === dutiableId);
  const currentValue = duty?.pivot?.additional_email ?? duty?.email ?? props.user.email;

  if (editingEmail.value === currentValue) {
    editingDutiableId.value = null;
    return;
  }

  updateDutiableUrl.value = route('dutiables.update', dutiableId);
  updateDutiableBody.value = { additional_email: editingEmail.value || null };
  await executeDutiableUpdate();

  if (emailUpdateSuccess.value) {
    if (duty && duty.pivot) {
      duty.pivot.additional_email = editingEmail.value || null;
    }
  }

  editingDutiableId.value = null;
};

const existingDutyColumns: ColumnDef<any, any>[] = [
  {
    accessorKey: 'name',
    header: () => 'Pavadinimas',
    cell: ({ row }) => (
      <a
        target="_blank"
        href={route('duties.edit', { id: row.original.id })}
        class="flex-inline gap-2 text-sm"
      >
        {row.original.name}
      </a>
    ),
  },
  {
    id: 'period',
    header: () => 'Laikotarpis',
    cell: ({ row }) => {
      const start = formatStaticTime(row.original.pivot.start_date);
      const end = row.original.pivot?.end_date ? formatStaticTime(row.original.pivot.end_date) : '—';
      return (
        <span class="text-xs text-muted-foreground">
          {start}
 –
          {end}
        </span>
      );
    },
  },
  {
    id: 'email',
    header: () => 'El. paštai',
    cell: ({ row }) => {
      const pivot = row.original.pivot as App.Entities.Dutiable | undefined;
      const dutyEmail = row.original.email as string | null;
      const isCurrentDuty = props.user.current_duties?.some(d => d.pivot?.id === pivot?.id);

      if (isCurrentDuty && editingDutiableId.value === pivot?.id) {
        return (
          <div class="flex items-center gap-1">
            <Input
              modelValue={editingEmail.value}
              onUpdate:modelValue={(val: string) => { editingEmail.value = val; }}
              onBlur={() => finishEditingEmail(pivot!.id)}
              onKeydown={(e: KeyboardEvent) => {
                if (e.key === 'Enter') finishEditingEmail(pivot!.id);
                if (e.key === 'Escape') { editingDutiableId.value = null; }
              }}
              class="h-7 min-w-[160px] text-xs"
              placeholder="Kontaktinis el. paštas"
            />
            {isUpdatingEmail.value && <span class="text-xs text-muted-foreground shrink-0">saugoma...</span>}
            {emailUpdateError.value && !isUpdatingEmail.value && (
              <span class="text-xs text-red-500 shrink-0" title={emailUpdateError.value}>!</span>
            )}
          </div>
        );
      }

      return (
        <div class="flex flex-col gap-0.5 text-xs">
          {dutyEmail && (
            <span class="text-muted-foreground">
              Pareigybės:
              {' '}
              <span class="text-foreground">{dutyEmail}</span>
              {dutyEmail.toLowerCase().endsWith('@vusa.lt') && (
                <Badge variant="outline" class="ml-1 text-[10px] px-1 py-0 h-4 shrink-0">prisijungimas</Badge>
              )}
            </span>
          )}
          {pivot?.additional_email
            ? (
                <span
                  class={['text-muted-foreground', isCurrentDuty ? 'cursor-pointer hover:text-primary' : '']}
                  onClick={() => isCurrentDuty && startEditingEmail(pivot?.id, pivot.additional_email)}
                  title={isCurrentDuty ? 'Spustelėkite redaguoti kontaktinį el. paštą' : ''}
                >
                  Kontaktinis:
                  {' '}
                  <span class="text-foreground">{pivot.additional_email}</span>
                  <Badge variant="outline" class="ml-1 text-[10px] px-1 py-0 h-4 shrink-0">kontaktinis</Badge>
                </span>
              )
            : isCurrentDuty
              ? (
                  <span
                    class="cursor-pointer text-muted-foreground hover:text-primary"
                    onClick={() => startEditingEmail(pivot?.id, '')}
                    title="Spustelėkite, kad pridėtumėte papildomą kontaktinį el. paštą. Įprastai bus naudojamas jau esamas vartotojo prisijungimo el. paštas."
                  >
                    + Pridėti papildomą kontaktinį
                  </span>
                )
              : null}

        </div>
      );
    },
  },
  {
    id: 'actions',
    cell: ({ row }) => (
      <Button
        variant="ghost"
        size="icon-xs"
        as="a"
        href={route('dutiables.edit', row.original.pivot.id as string)}
        target="_blank"
        title="Redaguoti pareigybės laikotarpį"
      >
        <PersonEdit24Regular />
      </Button>
    ),
  },
];

const previousDutyColumns: ColumnDef<any, any>[] = [
  ...existingDutyColumns,
  {
    id: 'delete',
    cell: ({ row }) => (
      <Button
        size="icon-xs"
        variant="destructive"
        onClick={() => deleteDutiable(row.original.pivot.id)}
      >
        <Delete24Regular />
      </Button>
    ),
  },
];

const flattenDutyOptions = computed(() => {
  return dutyOptions.flatMap(
    tenant =>
      tenant.children?.flatMap(
        institution =>
          institution.children?.map((duty) => {
            return {
              label:
                dutyShowMode.value === 'tree'
                  ? duty.label
                  : `${duty.label} (${institution.label})`,
              value: duty.value,
              tenantId: tenant.value,
            };
          }),
      ),
  ).filter(duty => props.permissableTenants.some(permissable => permissable.id === duty?.tenantId));
});

form.current_duties = props.user.current_duties?.map(duty => duty.id);

const hasCopied = ref(false);

const generatePassword = () => {
  router.post(
    route('users.generatePassword', props.user.id as number),
    {},
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
};

const deletePassword = () => {
  router.delete(
    route('users.deletePassword', props.user.id as number),
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
