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
        <Select v-model="form.responsible_user_id">
          <SelectTrigger>
            <SelectValue :placeholder="capitalize($tChoice('entities.problem.responsible_user', 1))" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="user in users" :key="user.id" :value="user.id">
              {{ user.name }}
            </SelectItem>
          </SelectContent>
        </Select>
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
import { computed, capitalize } from "vue";
import type { InertiaForm } from "@inertiajs/vue3";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";

import AdminForm from "./AdminForm.vue";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import MultiLocaleInput from "@/Components/FormItems/MultiLocaleInput.vue";
import MultiLocaleTiptapFormItem from "@/Components/FormItems/MultiLocaleTiptapFormItem.vue";
import { Input } from "@/Components/ui/input";
import { MultiSelect } from "@/Components/ui/multi-select";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/Components/ui/select";

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
  users: Array<App.Entities.User>;
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
