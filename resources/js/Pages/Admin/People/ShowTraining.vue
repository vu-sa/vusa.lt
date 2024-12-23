<template>
  <AdminContentPage :title="training.name">
    <img src="https://via.placeholder.com/1920x1080" alt="Placeholder image" class="h-72 w-full object-cover">
    <!-- Registration component -->
    <NCard v-if="training.form === null" class="mt-4 max-w-xl">
      <template #header>
        <div class="flex items-center gap-2">
          <IFluentSubtractCircle12Regular color="gray" />
          <strong>Registracija nevykdoma</strong>
        </div>
      </template>
    </NCard>
    <NCard v-else-if="!userIsRegistered" class="mt-4 max-w-xl">
      <template #header>
        <strong>Registracija dar vyksta</strong>
      </template>
      <Link :href="route('trainings.showRegistration', training.id)">
      <NButton>Registruotis</NButton>
      </Link>
    </NCard>
    <NCard v-else class="mt-4 max-w-xl">
      <template #header>
        <div class="flex items-center gap-2">
          <IFluentCheckmarkCircle24Filled color="green" />
          <strong>Užsiregistruota į mokymus!</strong>
        </div>
      </template>
    </NCard>
    <div class="typography mt-4">
      <div v-html="training.description" />
    </div>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import { Link } from "@inertiajs/vue3";
import { NCard } from "naive-ui";

defineProps<{
  training: App.Entities.Training;
  userIsRegistered: boolean;
}>();
</script>
