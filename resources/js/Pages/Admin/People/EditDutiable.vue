<template>
  <PageContent
    :title="`${dutiable.duty.name} (${dutiable.dutiable.name})`"
    :back-url="route('users.edit', dutiable.dutiable.id)"
    :heading-icon="Icons.DUTY"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="dutiable">
      <DutiableForm
        :dutiable="dutiable"
        model-route="dutiables.update"
        delete-model-route="dutiables.destroy"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import DutiableForm from "@/Components/AdminForms/DutiableForm.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  dutiable: App.Entities.Dutiable;
}>();

const dutiable = {
  ...props.dutiable,
  start_date: new Date(props.dutiable.start_date).getTime(),
  end_date: new Date(props.dutiable.end_date).getTime(),
};

console.log(dutiable)
</script>
