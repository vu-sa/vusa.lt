<template>
  <PageContent
    :title="localizedTitle"
    :back-url="route('problems.index')"
    :heading-icon="Icons.PROBLEM"
  >
    <UpsertModelLayout>
      <ProblemForm
        :form="form"
        :tenants="tenants"
        :categories="categories"
        :users="users"
        enable-delete
        @submit:form="handleUpdateProblem"
        @delete="() => router.delete(route('problems.destroy', problem.id))"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { router, useForm, type InertiaForm } from "@inertiajs/vue3";
import { getActiveLanguage } from "laravel-vue-i18n";

import Icons from "@/Types/Icons/regular";
import ProblemForm from "@/Components/AdminForms/ProblemForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

type ProblemForm = {
  title: { lt: string; en: string };
  description: { lt: string; en: string };
  solution: { lt: string; en: string };
  tenant_id: number | null;
  responsible_user_id: string | null;
  occurred_at: string;
  resolved_at: string | null;
  status: string;
  categories: number[];
};

const props = defineProps<{
  problem: App.Entities.Problem;
  tenants: Array<App.Entities.Tenant>;
  categories: Array<App.Entities.ProblemCategory>;
  users: Array<App.Entities.User>;
}>();

const localizedTitle = computed(() => {
  const locale = getActiveLanguage() as "lt" | "en";
  if (typeof props.problem.title === "object" && props.problem.title !== null) {
    return props.problem.title[locale] || props.problem.title.en || "";
  }
  return props.problem.title || "";
});

/**
 * Format a date string to yyyy-MM-dd format for naive-ui date picker
 */
function formatDateForPicker(
  dateValue: string | null | undefined
): string | null {
  if (!dateValue) {
    return null;
  }

  try {
    // Parse the date (handles both ISO strings and date-only strings)
    const date = new Date(dateValue);

    // Check if date is valid
    if (isNaN(date.getTime())) {
      return null;
    }

    // Format as yyyy-MM-dd
    const formatted = date.toISOString().split("T")[0];
    return formatted || null;
  } catch {
    return null;
  }
}

const form = useForm<ProblemForm>({
  title: (props.problem.title || { lt: "", en: "" }) as { lt: string; en: string },
  description: (props.problem.description || { lt: "", en: "" }) as {
    lt: string;
    en: string;
  },
  solution: (props.problem.solution || { lt: "", en: "" }) as {
    lt: string;
    en: string;
  },
  tenant_id: props.problem.tenant_id as number | null,
  responsible_user_id: props.problem.responsible_user_id as string | null,
  occurred_at: formatDateForPicker(props.problem.occurred_at as string) || "",
  resolved_at: formatDateForPicker(props.problem.resolved_at as string | null),
  status: props.problem.status as string,
  categories: (props.problem.categories || []) as number[],
});

function handleUpdateProblem(form: InertiaForm<ProblemForm>) {
  form
    .transform((data) => ({
      ...data,
      _method: "patch",
    }))
    .post(route("problems.update", props.problem.id), {
      preserveScroll: true,
    });
}
</script>
