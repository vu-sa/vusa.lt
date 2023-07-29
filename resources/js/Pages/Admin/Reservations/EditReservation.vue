<template>
  <PageContent
    :title="title"
    :back-url="route('resources.index')"
    :heading-icon="Icons.RESOURCE"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="reservation">
      <ReservationForm
        model-route="reservations.update"
        :reservation="reservation"
        :all-resources="allResources"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from "vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ReservationForm from "@/Components/AdminForms/ReservationForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

export type ReservationEditType = Omit<
  App.Entities.Reservation,
  "created_at" | "updated_at" | "deleted_at" | "name" | "description"
> & {
  name: Record<"lt" | "en", string>;
  description: Record<"lt" | "en", string>;
};

const props = defineProps<{
  reservation: ReservationEditType;
  allResources: Array<App.Entities.Resource>;
}>();

const title = computed(() => {
  if (props.reservation.name.lt) {
    return props.reservation.name.lt;
  } else if (props.reservation.name.en) {
    return props.reservation.name.en;
  } else {
    return "Rezervacijos redagavimas";
  }
});

const reservation = {
  ...props.reservation,
  start_time: props.reservation.start_time * 1000,
  end_time: props.reservation.end_time * 1000,
};
</script>
