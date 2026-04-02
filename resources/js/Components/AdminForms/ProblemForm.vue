<template>
  <AdminForm
    :model="form"
    :enable-delete="!!form.id"
    @submit:form="$emit('submit:form', form)"
    @delete="$emit('delete')"
  >
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <p class="mb-4">
          {{
            $t(
              "Užregistruokite problemą, su kuria susidūrėte savo veikloje. Nurodykite problemos kategoriją, atsakingą asmenį ir būseną."
            )
          }}
        </p>
      </template>

      <FormFieldWrapper id="title" :label="capitalize($tChoice('entities.problem.title', 1))" required>
        <MultiLocaleInput v-model:input="form.title" />
      </FormFieldWrapper>

      <MultiLocaleTiptapFormItem
        v-model:input="form.description"
        :label="capitalize($tChoice('entities.problem.description', 1))"
      />

      <div class="grid gap-x-4 lg:grid-cols-2">
        <FormFieldWrapper id="tenant_id" :label="capitalize($tChoice('entities.tenant.model', 1))" required>
          <Select v-model="tenantIdString">
            <SelectTrigger>
              <SelectValue :placeholder="capitalize($tChoice('entities.tenant.model', 1))" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="tenant in tenants" :key="tenant.id" :value="String(tenant.id)">
                {{ tenant.shortname || tenant.fullname }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper id="status" :label="capitalize($tChoice('entities.problem.status', 1))" required>
          <Select v-model="form.status">
            <SelectTrigger>
              <SelectValue :placeholder="capitalize($tChoice('entities.problem.status', 1))" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="open">{{ $t("Atvira") }}</SelectItem>
              <SelectItem value="in_progress">{{ $t("Vykdoma") }}</SelectItem>
              <SelectItem value="resolved">{{ $t("Išspręsta") }}</SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
      </div>

      <div class="grid gap-x-4 lg:grid-cols-2">
        <FormFieldWrapper id="occurred_at" :label="capitalize($tChoice('entities.problem.occurred_at', 1))" required>
          <Input v-model="form.occurred_at" type="date" />
        </FormFieldWrapper>

        <FormFieldWrapper id="resolved_at" :label="capitalize($tChoice('entities.problem.resolved_at', 1))">
          <Input v-model="form.resolved_at" type="date" />
        </FormFieldWrapper>
      </div>

      <FormFieldWrapper id="responsible_user_id" :label="capitalize($tChoice('entities.problem.responsible_user', 1))">
        <Combobox
          v-model="selectedUser"
          :filter-function="() => userOptions"
          @update:model-value="handleUserSelect"
        >
          <ComboboxAnchor :class="[
            'flex h-9 w-full items-center justify-between gap-2',
            'rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs',
            'border-zinc-200 dark:border-zinc-800 dark:bg-zinc-800/30',
            'focus-within:border-zinc-950 focus-within:ring-zinc-950/50 focus-within:ring-[3px]',
            'dark:focus-within:border-zinc-300 dark:focus-within:ring-zinc-300/50',
            'transition-[color,box-shadow] outline-none',
          ]">
            <ComboboxInput
              :display-value="(val: any) => (val as UserOption)?.name ?? ''"
              :placeholder="capitalize($tChoice('entities.problem.responsible_user', 1))"
              class="w-full bg-transparent text-sm outline-none placeholder:text-zinc-500 dark:placeholder:text-zinc-400"
              @input="onUserSearchInput"
            />
            <button
              v-if="selectedUser"
              type="button"
              class="shrink-0 rounded-sm opacity-50 hover:opacity-100"
              @click.prevent.stop="clearSelectedUser"
            >
              <X class="size-4" />
            </button>
            <ChevronsUpDown v-else class="size-4 shrink-0 opacity-50" />
          </ComboboxAnchor>
          <ComboboxList>
            <ComboboxViewport class="max-h-60">
              <div v-if="userSearchTerm.length < 2 && !selectedUser" class="px-2 py-4 text-center text-sm text-muted-foreground">
                {{ $t("Įveskite bent 2 simbolius") }}
              </div>
              <div v-else-if="isSearchingUsers" class="px-2 py-4 text-center text-sm text-muted-foreground">
                {{ $t("Ieškoma...") }}
              </div>
              <template v-else>
                <ComboboxEmpty>{{ $t("Nerasta") }}</ComboboxEmpty>
                <ComboboxItem
                  v-for="user in userOptions"
                  :key="user.id"
                  :value="user"
                >
                  {{ user.name }}
                </ComboboxItem>
              </template>
            </ComboboxViewport>
          </ComboboxList>
        </Combobox>
      </FormFieldWrapper>

      <FormFieldWrapper id="categories" :label="capitalize($tChoice('entities.problem.categories', 2))">
        <MultiSelect
          v-model="selectedCategories"
          :options="categoryOptions"
          :placeholder="capitalize($tChoice('entities.problem.categories', 2))"
        />
      </FormFieldWrapper>

      <FormFieldWrapper id="institutions" :label="capitalize($tChoice('entities.institution.model', 2))">
        <MultiSelect
          v-model="selectedInstitutions"
          :options="institutionOptions"
          :placeholder="capitalize($tChoice('entities.institution.model', 2))"
        />
      </FormFieldWrapper>
    </FormElement>

    <FormElement>
      <template #title>
        {{ capitalize($tChoice("entities.problem.steps_taken", 1)) }}
      </template>
      <template #description>
        <p class="mb-4">
          {{
            $t(
              "Aprašykite veiksmus, kurie jau buvo atlikti bandant išspręsti šią problemą."
            )
          }}
        </p>
      </template>

      <MultiLocaleTiptapFormItem
        v-model:input="form.steps_taken"
        :label="capitalize($tChoice('entities.problem.steps_taken', 1))"
      />
    </FormElement>

    <FormElement>
      <template #title>
        {{ capitalize($tChoice("entities.problem.solution", 1)) }}
      </template>
      <template #description>
        <p class="mb-4">
          {{
            $t(
              "Aprašykite problemos sprendimą, jei toks jau rastas. Šis laukas gali būti užpildytas vėliau."
            )
          }}
        </p>
      </template>

      <MultiLocaleTiptapFormItem
        v-model:input="form.solution"
        :label="capitalize($tChoice('entities.problem.solution', 1))"
      />
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, capitalize, ref, watch } from "vue";
import type { InertiaForm } from "@inertiajs/vue3";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { useDebounceFn } from "@vueuse/core";

import { useApi } from "@/Composables/useApi";
import AdminForm from "./AdminForm.vue";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import MultiLocaleInput from "@/Components/FormItems/MultiLocaleInput.vue";
import MultiLocaleTiptapFormItem from "@/Components/FormItems/MultiLocaleTiptapFormItem.vue";
import { Input } from "@/Components/ui/input";
import { MultiSelect } from "@/Components/ui/multi-select";
import { ChevronsUpDown, X } from "lucide-vue-next";
import {
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxRoot as Combobox,
} from "reka-ui";
import {
  ComboboxItem,
  ComboboxList,
  ComboboxViewport,
} from "@/Components/ui/combobox";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";

interface UserOption {
  id: string;
  name: string;
}

type ProblemForm = {
  id?: string;
  title: { lt: string; en: string };
  description: { lt: string; en: string };
  solution: { lt: string; en: string };
  steps_taken: { lt: string; en: string };
  tenant_id: number | null;
  responsible_user_id: string | null;
  occurred_at: string;
  resolved_at: string | null;
  status: string;
  categories: number[];
  institutions: string[];
};

const props = defineProps<{
  form: InertiaForm<ProblemForm>;
  tenants: Array<App.Entities.Tenant>;
  categories: Array<App.Entities.ProblemCategory>;
  initialResponsibleUser?: { id: string; name: string } | null;
  institutions: Array<App.Entities.Institution>;
}>();

defineEmits<{
  "submit:form": [form: InertiaForm<ProblemForm>];
  delete: [];
}>();

// Shadcn Select requires string values for v-model
const tenantIdString = computed({
  get: () => (props.form.tenant_id != null ? String(props.form.tenant_id) : ""),
  set: (val: string) => {
    props.form.tenant_id = val ? Number(val) : null;
  },
});

// --- User search ---
const userSearchTerm = ref("");
const selectedUser = ref<UserOption | null>(props.initialResponsibleUser ?? null);
const userSearchUrl = ref("");

const { data: searchedUsers, isFetching: isSearchingUsers, execute: executeUserSearch } = useApi<UserOption[]>(
  userSearchUrl,
  { immediate: false, showErrorToast: false }
);

const userOptions = computed<UserOption[]>(() => searchedUsers.value ?? []);

const debouncedSearch = useDebounceFn(() => {
  if (userSearchTerm.value.length >= 2) {
    const params = new URLSearchParams({
      search: userSearchTerm.value,
    });
    userSearchUrl.value = route("api.v1.admin.users.search") + "?" + params.toString();
    executeUserSearch();
  }
}, 300);

function onUserSearchInput(event: Event) {
  const target = event.target as HTMLInputElement;
  userSearchTerm.value = target.value;
  if (userSearchTerm.value.length >= 2) {
    debouncedSearch();
  }
}

function handleUserSelect(val: unknown) {
  const user = val as UserOption | null;
  selectedUser.value = user;
  props.form.responsible_user_id = user?.id ?? null;
}

function clearSelectedUser() {
  selectedUser.value = null;
  userSearchTerm.value = "";
  props.form.responsible_user_id = null;
}

const categoryOptions = computed(() =>
  props.categories.map((category) => ({
    label: category.name as string,
    value: category.id,
  }))
);

// Bridge: MultiSelect operates on full objects, form.categories stores number[] IDs
const selectedCategories = computed({
  get: () =>
    (props.form.categories as number[])
      .map((id) => categoryOptions.value.find((opt) => opt.value === id))
      .filter((opt): opt is { label: string; value: number } => Boolean(opt)),
  set: (items: { label: string; value: number }[]) => {
    props.form.categories = items.map((item) => item.value);
  },
});

const institutionOptions = computed(() => {
  const filtered = props.form.tenant_id
    ? props.institutions.filter((i) => i.tenant_id === props.form.tenant_id)
    : props.institutions;

  return filtered.map((institution) => ({
    label: institution.name as string,
    value: institution.id,
  }));
});

// Bridge: MultiSelect operates on full objects, form.institutions stores string[] IDs
const selectedInstitutions = computed({
  get: () =>
    (props.form.institutions as string[])
      .map((id) => institutionOptions.value.find((opt) => opt.value === id))
      .filter((opt): opt is { label: string; value: string } => Boolean(opt)),
  set: (items: { label: string; value: string }[]) => {
    props.form.institutions = items.map((item) => item.value);
  },
});
</script>
