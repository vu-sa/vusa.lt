<template>
  <AdminContentPage :title="training.name">
    <img src="https://via.placeholder.com/1920x1080" alt="Placeholder image" class="h-72 w-full object-cover">
    <div class="typography mt-4">
      <div v-html="training.description" />
    </div>
    <NDivider />
    <NTabs class="my-4" type="line" :default-value="defaultTab">
      <NTabPane name="registration">
        <template #tab>
          <IFluentCircle24Regular v-if="!userIsRegistered" class="mr-1" />
          <IFluentCheckmarkCircle16Regular v-else class="mr-1" />
          Registracija
        </template>
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
      </NTabPane>
      <NTabPane name="programme" tab="Programa">
        <ProgrammePlanner show-times :programme="training.programmes?.at(0)" />
      </NTabPane>
      <NTabPane disabled name="tasks" tab="Užduotys" />
    </NTabs>
  </AdminContentPage>
</template>

<script setup lang="ts">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import ProgrammePlanner from "@/Features/Admin/ProgrammePlanner/ProgrammePlanner.vue";
import { Link } from "@inertiajs/vue3";
import { NCard } from "naive-ui";
import { computed } from "vue";

const props = defineProps<{
  training: App.Entities.Training;
  userIsRegistered: boolean;
}>();

const defaultTab = computed(() => {
  if (props.userIsRegistered) {
    return "programme";
  }

  return "registration";
});
</script>
