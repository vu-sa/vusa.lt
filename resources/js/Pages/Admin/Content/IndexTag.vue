<template>
  <IndexPageLayout title="Žymos" model-name="tags" :can-use-routes="canUseRoutes" :columns="columns"
    :paginated-models="tags" :icon="Icons.TAG">
    <template #create-button>
      <Button as-child variant="outline">
        <Link :href="route('tags.merge')">
          <NIcon :component="Icons.RELATIONSHIP" class="mr-2 h-4 w-4" />
          Sulieti žymas
        </Link>
      </Button>
    </template>
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  type DataTableColumns,
  NIcon,
} from "naive-ui";
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import { Button } from "@/Components/ui/button";

defineProps<{
  tags: PaginatedModels<App.Entities.Tag[]>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  duplicate: false,
  destroy: true,
};

// ! Don't forget that columns must be computed for the filters to update
const columns = computed<DataTableColumns<App.Entities.Tag>>(() => {
  return [
    {
      title() {
        return $t("forms.fields.name");
      },
      key: "name",
      render(row) {
        return row.name?.lt || row.name?.en || "-";
      },
    },
    {
      title() {
        return $t("forms.fields.alias");
      },
      key: "alias",
      render(row) {
        return row.alias || "-";
      },
    },
    {
      title() {
        return $t("forms.fields.description");
      },
      key: "description",
      render(row) {
        return row.description?.lt || row.description?.en || "-";
      },
      ellipsis: {
        tooltip: true,
      },
    },
    {
      title() {
        return $t("forms.fields.created_at");
      },
      key: "created_at",
      render(row) {
        return new Date(row.created_at).toLocaleDateString("lt-LT");
      },
    },
  ];
});
</script>
