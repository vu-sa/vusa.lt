<template>
  <PageContent :title="resource.name[$page.props.app.locale]" :back-url="route('resources.index')"
    :heading-icon="Icons.RESOURCE">
    <UpsertModelLayout :errors="$page.props.errors" :model="resource">
      <ResourceForm :resource :categories :assignable-tenants model-route="resources.update" />
    </UpsertModelLayout>
    <NCard :title="$t('Rezervacijų istorija')" class="mt-4 min-w-[450px]">
      <NDataTable size="small" :data="resource.reservations" :columns />
    </NCard>
  </PageContent>
</template>

<script setup lang="tsx">
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";
import { Link, usePage } from "@inertiajs/vue3";
import { NCard, NDataTable } from "naive-ui";

import { RESERVATION_DATE_TIME_FORMAT } from "@/Constants/DateTimeFormats";
import { capitalize } from "@/Utils/String";
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
  media: Record<string, never>[];
  // media: models.Media[];
};

defineProps<{
  resource: ResourceEditType;
  categories: any
  assignableTenants: Array<App.Entities.Tenant>;
}>();

const columns = [
  {
    title() {
      return $t("forms.fields.title");
    },
    key: "name",
    render(row) {
      return <Link href={route("reservations.show", row.id)}>{row.name}</Link>;
    },
  },
  {
    title() {
      return $t("forms.fields.quantity");
    },
    key: "pivot.quantity",
  },
  {
    title() {
      return capitalize($tChoice("entities.reservation.managers", 2));
    },
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
    title() {
      return $t("forms.fields.state");
    },
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
    title() {
      return capitalize($t("entities.reservation.start_time"));
    },
    key: "start_time",
    sorter: (a, b) =>
      new Date(a.start_time).getTime() - new Date(b.start_time).getTime(),
    render(row) {
      return formatStaticTime(
        new Date(row.start_time),
        RESERVATION_DATE_TIME_FORMAT,
        usePage().props.app.locale
      );
    },
  },
  {
    title() {
      return capitalize($t("entities.reservation.end_time"));
    },
    key: "end_time",
    sorter: (a, b) =>
      new Date(a.end_time).getTime() - new Date(b.end_time).getTime(),
    defaultSortOrder: "descend",
    render(row) {
      return formatStaticTime(
        new Date(row.end_time),
        RESERVATION_DATE_TIME_FORMAT,
        usePage().props.app.locale
      );
    },
  },
  {
    title() {
      return $t("forms.fields.created_at");
    },
    key: "created_at",
    render(row) {
      return formatRelativeTime(
        new Date(row.created_at),
        {
          numeric: "auto",
        },
        usePage().props.app.locale
      );
    },
  },
];
</script>
