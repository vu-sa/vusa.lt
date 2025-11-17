<template>
  <AdminForm
    :model="form"
    :label-placement="'top'"
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

      <NFormItem :label="$tChoice('entities.problem.title', 1)" required>
        <MultiLocaleInput v-model:input="form.title" />
      </NFormItem>

      <MultiLocaleTiptapFormItem
        v-model:input="form.description"
        :label="$tChoice('entities.problem.description', 1)"
      />

      <div class="grid gap-x-4 lg:grid-cols-2">
        <NFormItem :label="$tChoice('entities.tenant.model', 1)" required>
          <NSelect
            v-model:value="form.tenant_id"
            :options="tenantOptions"
            :placeholder="$tChoice('entities.tenant.model', 1)"
          />
        </NFormItem>

        <NFormItem :label="$tChoice('entities.problem.status', 1)" required>
          <NSelect
            v-model:value="form.status"
            :options="statusOptions"
            :placeholder="$tChoice('entities.problem.status', 1)"
          />
        </NFormItem>
      </div>

      <div class="grid gap-x-4 lg:grid-cols-2">
        <NFormItem
          :label="$tChoice('entities.problem.occurred_at', 1)"
          required
        >
          <NDatePicker
            v-model:formatted-value="form.occurred_at"
            type="date"
            :placeholder="$tChoice('entities.problem.occurred_at', 1)"
            value-format="yyyy-MM-dd"
            format="yyyy-MM-dd"
            class="w-full"
          />
        </NFormItem>

        <NFormItem :label="$tChoice('entities.problem.resolved_at', 1)">
          <NDatePicker
            v-model:formatted-value="form.resolved_at"
            type="date"
            :placeholder="$tChoice('entities.problem.resolved_at', 1)"
            value-format="yyyy-MM-dd"
            format="yyyy-MM-dd"
            class="w-full"
            clearable
          />
        </NFormItem>
      </div>

      <NFormItem :label="$tChoice('entities.problem.responsible_user', 1)">
        <NSelect
          v-model:value="form.responsible_user_id"
          :options="userOptions"
          :placeholder="$tChoice('entities.problem.responsible_user', 1)"
          clearable
          filterable
        />
      </NFormItem>

      <NFormItem :label="$tChoice('entities.problem.categories', 2)">
        <NSelect
          v-model:value="form.categories"
          :options="categoryOptions"
          :placeholder="$tChoice('entities.problem.categories', 2)"
          multiple
          clearable
        />
      </NFormItem>
    </FormElement>

    <FormElement>
      <template #title>
        {{ $tChoice("entities.problem.steps_taken", 1) }}
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
        :label="$tChoice('entities.problem.steps_taken', 1)"
      />
    </FormElement>

    <FormElement>
      <template #title>
        {{ $tChoice("entities.problem.solution", 1) }}
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
        :label="$tChoice('entities.problem.solution', 1)"
      />
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { NDatePicker, NFormItem, NSelect } from "naive-ui";
import type { InertiaForm } from "@inertiajs/vue3";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";

import AdminForm from "./AdminForm.vue";
import FormElement from "./FormElement.vue";
import MultiLocaleInput from "@/Components/FormItems/MultiLocaleInput.vue";
import MultiLocaleTiptapFormItem from "@/Components/FormItems/MultiLocaleTiptapFormItem.vue";

type ProblemForm = {
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
};

const props = defineProps<{
  form: InertiaForm<ProblemForm>;
  tenants: Array<App.Entities.Tenant>;
  categories: Array<App.Entities.ProblemCategory>;
  users: Array<App.Entities.User>;
}>();

defineEmits<{
  "submit:form": [form: InertiaForm<ProblemForm>];
  delete: [];
}>();

const tenantOptions = computed(() =>
  props.tenants.map((tenant) => ({
    label: tenant.shortname || tenant.fullname,
    value: tenant.id,
  }))
);

const statusOptions = computed(() => [
  { label: "Atvira", value: "open" },
  { label: "Vykdoma", value: "in_progress" },
  { label: "Išspręsta", value: "resolved" },
]);

const userOptions = computed(() =>
  props.users.map((user) => ({
    label: user.name,
    value: user.id,
  }))
);

const categoryOptions = computed(() =>
  props.categories.map((category) => ({
    label: category.name,
    value: category.id,
  }))
);
</script>
