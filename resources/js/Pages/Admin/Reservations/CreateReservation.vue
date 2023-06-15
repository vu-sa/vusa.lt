<template>
  <PageContent title="Nauja rezervacija" :heading-icon="Icons.RESERVATION">
    <UpsertModelLayout :errors="$page.props.errors" :model="reservation">
      <ReservationForm
        model-route="reservations.store"
        :reservation="reservation"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ReservationForm from "@/Components/AdminForms/ReservationForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

export type ReservationCreationTemplate = Omit<
  App.Entities.Reservation,
  | "created_at"
  | "updated_at"
  | "deleted_at"
  | "id"
  | "name"
  | "description"
  | "completed_at"
  | "start_time"
  | "end_time"
> & {
  id: undefined;
  name: {
    lt: string;
    en: string;
  };
  description: {
    lt: string;
    en: string;
  };
  start_time: Date;
  end_time: Date;
};

const reservation: ReservationCreationTemplate = {
  id: undefined,
  name: {
    lt: "",
    en: "",
  },
  description: {
    lt: "",
    en: "",
  },
  start_time: new Date(),
  end_time: new Date(),
};
</script>
