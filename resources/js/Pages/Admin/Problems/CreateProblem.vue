<template>
  <PageContent
    :title="
      $tChoice('forms.new_model', 0, {
        model: $tChoice('entities.problem.model', 1),
      })
    "
    :back-url="route('problems.index')"
    :heading-icon="Icons.PROBLEM"
  >
    <UpsertModelLayout>
      <ProblemForm
        :form="form"
        :tenants="tenants"
        :categories="categories"
        :users="users"
        :institutions="institutions"
        @submit:form="
          (form) => form.post(route('problems.store'), { preserveScroll: true })
        "
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { transChoice as $tChoice } from "laravel-vue-i18n";

import Icons from "@/Types/Icons/regular";
import ProblemForm from "@/Components/AdminForms/ProblemForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  tenants: Array<App.Entities.Tenant>;
  categories: Array<App.Entities.ProblemCategory>;
  users: Array<App.Entities.User>;
  institutions: Array<App.Entities.Institution>;
}>();

const form = useForm({
  title: { lt: "", en: "" },
  description: { lt: "", en: "" },
  solution: { lt: "", en: "" },
  steps_taken: { lt: "", en: "" },
  tenant_id: null as number | null,
  responsible_user_id: null as string | null,
  occurred_at: new Date().toISOString().split("T")[0] as string,
  resolved_at: null as string | null,
  status: "open" as string,
  categories: [] as number[],
  institutions: [] as string[],
});
</script>
