<template>
  <IndexPageLayout :title="capitalize($tChoice('entities.document.model', 2))" model-name="documents" :scroll-x="1600"
    :icon="Icons.DOCUMENT" :can-use-routes :columns :paginated-models="documents">
    <template #create-button>
      <FilePicker v-if="$page.props.app.url.startsWith('https')" :loading round size="tiny" :theme-overrides="{ border: '1.2px solid' }" @pick="handleDocumentPick">
        <template #default>
          {{ $t("forms.add") }}
        </template>
      </FilePicker>
    </template>
  </IndexPageLayout>

</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import {
  type DataTableColumns,
  type DataTableSortState,
  NButton,
  NIcon,
} from "naive-ui";
import { Link, router } from "@inertiajs/vue3";
import { computed, provide, ref } from "vue";

import { Item } from "@/Features/Admin/SharepointFilePicker/picker";
import { capitalize } from "@/Utils/String";
import ArrowCounterclockwise28Regular from "~icons/fluent/arrow-counterclockwise28-regular";
import FilePicker from "@/Features/Admin/SharepointFilePicker/FilePicker.vue";
import IFluentOpen24Filled from "~icons/fluent/open16-filled";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";

defineProps<{
  documents: PaginatedModels<App.Entities.Document>;
}>();

const canUseRoutes = {
  create: false,
  show: false,
  edit: false,
  duplicate: false,
  destroy: true,
};

const loading = ref(false);

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  title: false,
});

provide("sorters", sorters);
//
//const filters = ref<Record<string, any>>({
//  'padalinys.id': [],
//  'category.id': [],
//});
//
//provide("filters", filters);

// add columns
const columns = computed<DataTableColumns<App.Entities.Document>>(() => [
  //{
  //  key: "thumbnail_url",
  //  render(row) {
  //    return (
  //      <img src={row.thumbnail_url} class="size-12 rounded-md border object-cover" />
  //    );
  //  },
  //  align: "center",
  //  width: 66,
  //},
  {
    title() {
      return $t("forms.fields.title");
    },
    key: "title",
    sorter: true,
    fixed: "left",
    sortOrder: sorters.value.title,
    width: 400,
    ellipsis: {
      tooltip: true,
    },
    render(row) {
      return (
        <SmartLink href={row.anonymous_url}>
          <div class="inline-flex items-center gap-1">{row.title} <IFluentOpen24Filled /></div>
        </SmartLink>
      );
    },
  },
  {
    title: "Veiksmai",
    key: "sharepoint-actions",
    width: 100,
    align: "center",
    render(row) {
      return (
        <div class="flex justify-center">
          <NButton size="small" secondary onClick={() => handleDocumentRefresh(row)}>
            {{
              icon: () => (
                <NIcon component={ArrowCounterclockwise28Regular} />
              )
            }}
          </NButton>
        </div>
      )
    }
  },
  {
    title: "Data",
    key: "document_date",
  },
  {
    title: "Content Type",
    key: "content_type",
  },
  {
    title: "Language",
    key: "language",
  },
  {
    title: "Institution",
    key: "institution.short_name",
  },
  {
    title: "Atnaujinimo data",
    key: "checked_at",
    render(row) {
      return (
        <div class="flex justify-center">
          <NButton size="tiny" secondary onClick={() => handleDocumentRefresh(row)}>
            {{
              icon: () => (
                <NIcon component={ArrowCounterclockwise28Regular} />
              ),
              default: () => (
                row.checked_at
              )
            }}
          </NButton>
        </div>
      )
    }
  }
]);

const handleDocumentPick = (items: Item[]) => {
  loading.value = true

  const documents = items.map((item) => ({
    name: item.name,
    site_id: item.sharepointIds?.siteId,
    list_id: item.sharepointIds?.listId,
    list_item_unique_id: item.sharepointIds?.listItemUniqueId,
  }));

  router.post(route("documents.store"), { documents }, {
    onSuccess: () => {
      loading.value = false
    },
    onError() {
      loading.value = false
    },
  });
};

const handleDocumentRefresh = (document: App.Entities.Document) => {

  loading.value = true

  router.post(route("documents.refresh", document.id), {}, {
    onSuccess: () => {
      loading.value = false
    },
    onError() {
      loading.value = false
    },
  });
}
</script>
