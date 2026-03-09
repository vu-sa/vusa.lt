<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        Pareigos rodymas pagal žmogaus įvardį, jeigu įvardyje pirmas žodis yra:
        <li>
          jis (he) - <strong>{{ changeDutyNameEndings(null, duty.name.lt, $page.props.app.locale, "jis/jo", false)
            }}</strong>
        </li>
        <li>
          ji (she) - <strong>{{ changeDutyNameEndings(null, duty.name.lt, $page.props.app.locale, "ji/jos", false)
            }}</strong>
        </li>
        <li>
          jie (they) - <strong>{{ changeDutyNameEndings(null, duty.name.lt, $page.props.app.locale, "jie/jų", false)
            }}</strong>
        </li>
      </template>
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" :error="form.errors.name">
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>

      <FormFieldWrapper id="email" :label="$t('forms.fields.email')" :error="form.errors.email">
        <Input id="email" v-model="form.email" placeholder="vusa@vusa.lt" />
      </FormFieldWrapper>

      <div class="grid gap-4 lg:grid-cols-2">
        <FormFieldWrapper id="institution_id" label="Institucija" :error="form.errors.institution_id">
          <Select v-model="institutionIdString">
            <SelectTrigger>
              <SelectValue placeholder="Pasirink instituciją pagal pavadinimą..." />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="inst in institutionsFromDatabase" :key="inst.value" :value="String(inst.value)">
                {{ inst.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper id="places_to_occupy" :label="$t('forms.fields.duty_people_count')" :error="form.errors.places_to_occupy">
          <NumberField id="places_to_occupy" v-model="form.places_to_occupy" :min="0" />
        </FormFieldWrapper>
      </div>

      <FormFieldWrapper id="contacts_grouping" label="Kontaktų grupavimas" :error="form.errors.contacts_grouping">
        <Select v-model="form.contacts_grouping">
          <SelectTrigger>
            <SelectValue placeholder="Pasirinkite grupavimo būdą" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="none">Be grupavimo</SelectItem>
            <SelectItem value="study_program">Pagal studijų programą</SelectItem>
            <SelectItem value="tenant">Pagal padalinį</SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormElement>
    <FormElement>
      <template #title>
        {{ $t("forms.fields.description") }}
      </template>
      <template #description>
        Aprašymas yra rodomas vusa.lt puslapyje prie pareigybės
      </template>
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2">
          <Label for="description">Aprašymas</Label>
          <SimpleLocaleButton v-model:locale="locale" />
        </div>
        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
        <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
        <p v-if="form.errors.description" class="text-xs text-red-600 dark:text-red-400">
          {{ form.errors.description }}
        </p>
      </div>
    </FormElement>
    <FormElement>
      <template #title>
        Asmenys
      </template>
      <template #description>
        <p class="mb-4">
          Pareigybę gali užimti daug naudotojų.
        </p>
        <p>
          Jeigu sąraše nėra asmens, kuris užima pareigybę, šį asmenį reikia
          sukurti.
        </p>
      </template>
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2">
          <Label><strong>{{ $t("Nariai") }}</strong></Label>
          <a target="_blank" :href="route('users.create')">
            <Button variant="link" size="xs">
              <IFluentAdd24Filled />
              Sukurti naują asmenį
            </Button>
          </a>
        </div>
        <TransferList v-model="form.current_users" :options="userOptions">
          <template #source-label="{ option }">
            <span class="inline-flex items-center gap-2">
              {{ option.label }}
              <a target="_blank" :href="route('users.edit', option.value)">
                <Button variant="ghost" size="icon-xs" @click.stop>
                  <IconEdit />
                </Button>
              </a>
            </span>
          </template>
          <template #target-label="{ option }">
            <div class="flex items-center gap-2">
              <UserAvatar :size="24" :user="option.user" />
              <span class="inline-flex gap-2">
                {{ option.label }}
                <a target="_blank" :href="route('users.edit', option.value)">
                  <Button variant="ghost" size="icon-xs" @click.stop>
                    <IconEye />
                  </Button>
                </a>
              </span>
            </div>
          </template>
        </TransferList>
      </div>

      <!-- Current users with study programs display -->
      <div v-if="duty.current_users && duty.current_users.length > 0" class="mt-4">
        <div class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
          Dabartiniai nariai:
        </div>
        <div class="space-y-2">
          <div v-for="user in duty.current_users" :key="user.id"
            class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div class="flex items-center gap-3">
              <UserAvatar :user="user" :size="32" />
              <div>
                <div class="font-medium">
                  {{ user.name }}
                </div>
                <div v-if="getUserStudyProgram(user)" class="text-sm text-gray-600 dark:text-gray-400">
                  <Badge variant="secondary">
                    {{ getUserStudyProgram(user)?.name }} ({{ getUserStudyProgram(user)?.degree }})
                  </Badge>
                </div>
              </div>
            </div>
            <a v-if="getUserDutiableId(user)" :href="route('dutiables.edit', { dutiable: getUserDutiableId(user) })" target="_blank">
              <Button variant="link" size="xs">
                <IconEdit />
                Redaguoti pareigybės laikotarpį
              </Button>
            </a>
          </div>
        </div>
      </div>
    </FormElement>
    <FormElement>
      <template #title>
        Papildoma informacija
      </template>
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
      <FormFieldWrapper id="types" label="Pareigybės tipas" :error="form.errors.types">
        <MultiSelect v-model="selectedTypes" :options="dutyTypes" label-field="title" value-field="id"
          placeholder="Pasirinkti kategoriją..." />
      </FormFieldWrapper>

      <FormFieldWrapper id="roles" label="Administracinė vusa.lt rolė" :error="form.errors.roles">
        <MultiSelect v-model="selectedRoles" :options="rolesOptions" label-field="label" value-field="value"
          :disabled="!$page.props.auth?.user.isSuperAdmin" placeholder="Be rolės..." />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import IconEdit from "~icons/fluent/edit16-filled";
import IconEye from "~icons/fluent/eye16-regular";

import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { MultiSelect } from "@/Components/ui/multi-select";
import { NumberField } from "@/Components/ui/number-field";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { TransferList } from "@/Components/ui/transfer-list";
import { changeDutyNameEndings } from "@/Utils/String";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TiptapEditor from "@/Components/TipTap/TiptapEditor.vue";
import UserAvatar from "../Avatars/UserAvatar.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import AdminForm from "./AdminForm.vue";

const props = defineProps<{
  duty: App.Entities.Duty;
  dutyTypes: App.Entities.Type[];
  assignableUsers: App.Entities.User[];
  roles: App.Entities.Role[];
  assignableInstitutions: App.Entities.Institution[];
  rememberKey?: string;
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const locale = ref("lt");

const form = props.rememberKey
  ? useForm(props.rememberKey, props.duty as any)
  : useForm(props.duty as any);

form.roles = props.duty.roles?.map((role) => role.id);
form.types = props.duty.types?.map((type) => type.id);
form.current_users = props.duty.current_users?.map((user) => user.id);

const userOptions = props.assignableUsers.map((user) => ({
  label: user.name,
  value: user.id,
  user: user,
}));

const rolesOptions = props.roles.map((role) => ({
  label: role.name,
  value: role.id,
}));

// MultiSelect operates on full objects, but form stores ID arrays for server submission
const selectedTypes = computed({
  get: () => props.dutyTypes.filter(dt => form.types?.includes(dt.id)),
  set: (items: App.Entities.Type[]) => { form.types = items.map(item => item.id); },
});

const selectedRoles = computed({
  get: () => rolesOptions.filter(opt => form.roles?.includes(opt.value)),
  set: (items: { label: string; value: number }[]) => { form.roles = items.map(item => item.value); },
});

const institutionsFromDatabase = props.assignableInstitutions.map((institution) => ({
  label: `${institution.name} (${institution.tenant?.shortname})`,
  value: institution.id,
}));

// Shadcn Select requires string values
const institutionIdString = computed({
  get: () => form.institution_id != null ? String(form.institution_id) : '',
  set: (val: string) => { form.institution_id = val ? Number(val) : null; },
});

// Helper functions for displaying study program information
const getUserStudyProgram = (user: any) => {
  const dutiable = user.pivot;
  return dutiable?.study_program;
};

const getUserDutiableId = (user: any) => {
  const dutiable = user.pivot;
  return dutiable?.id || null;
};

</script>
