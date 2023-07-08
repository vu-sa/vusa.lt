<template>
  <PageContent
    :title="title"
    :back-url="route('resources.index')"
    :heading-icon="Icons.RESOURCE"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="resource">
      <ResourceForm
        :resource="resource"
        :padaliniai="assignablePadaliniai"
        model-route="resources.update"
      />
    </UpsertModelLayout>
    <NCard
      title="Rezervacijų istorija"
      class="subtle-gray-gradient mt-4 min-w-[450px]"
    >
      <NDataTable :data="resource.reservations" :columns="columns"></NDataTable>
    </NCard>
  </PageContent>
</template>

<script setup lang="tsx">
import { NCard, NDataTable, NTag } from "naive-ui";
import { computed } from "vue";

import { Link } from "@inertiajs/vue3";
import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { formatRelativeTime, formatStaticTime } from "@/Utils/IntlTime";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ReservationResourceStateTag from "@/Components/Tag/ReservationResourceStateTag.vue";
import ResourceForm from "@/Components/AdminForms/ResourceForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

export type ResourceEditType = Omit<
  App.Entities.Resource,
  "created_at" | "updated_at" | "deleted_at" | "name" | "description"
> & {
  name: Record<"lt" | "en", string>;
  description: Record<"lt" | "en", string>;
  media: App.Models.Media[];
};

const props = defineProps<{
  resource: ResourceEditType;
  assignablePadaliniai: Array<App.Entities.Padalinys>;
}>();

const title = computed(() => {
  if (props.resource.name.lt) {
    return props.resource.name.lt;
  } else if (props.resource.name.en) {
    return props.resource.name.en;
  } else {
    return "Išteklio redagavimas";
  }
});

const columns = [
  {
    title: "Pavadinimas",
    key: "name",
    render(row) {
      return <Link href={route("reservations.show", row.id)}>{row.name}</Link>;
    },
  },
  {
    title: "Kiekis",
    key: "pivot.quantity",
  },
  {
    title: "Rezervacijos kūrėjai",
    key: "users",
    render(row) {
      return row.users && row.users?.length > 0 ? (
        <UsersAvatarGroup class="align-middle" size={30} users={row.users} />
      ) : (
        "Nėra"
      );
    },
  },
  {
    title: "Statusas",
    key: "state",
    render(row) {
      return (
        <ReservationResourceStateTag
          state={row.pivot.state}
          state_properties={row.pivot.state_properties}
          class="align-middle"
        />
      );
    },
  },
  {
    title: "Rezervacijos pradžia",
    key: "start_time",
    sorter: (a, b) =>
      new Date(a.start_time).getTime() - new Date(b.start_time).getTime(),
    render(row) {
      return formatStaticTime(
        new Date(row.start_time),
        RESERVATION_DATE_TIME_FORMAT
      );
    },
  },
  {
    title: "Rezervacijos pabaiga",
    key: "end_time",
    sorter: (a, b) =>
      new Date(a.end_time).getTime() - new Date(b.end_time).getTime(),
    defaultSortOrder: "descend",
    render(row) {
      return formatStaticTime(
        new Date(row.end_time),
        RESERVATION_DATE_TIME_FORMAT
      );
    },
  },
  {
    title: "Sukurta",
    key: "created_at",
    render(row) {
      return formatRelativeTime(new Date(row.created_at));
    },
  },
];
</script>
