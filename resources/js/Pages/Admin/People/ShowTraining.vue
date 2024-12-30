<template>
  <AdminContentPage>
    <div class="relative h-72 w-full rounded-md">
      <img :src="training.image" class="size-full rounded-lg object-cover">
      <div class="absolute bottom-0 h-1/2 w-full rounded-b-md bg-gradient-to-b from-transparent to-zinc-900" />
      <div class="absolute bottom-0 grid grid-cols-2 p-6">
        <div>
          <h2 class="mb-0 text-3xl font-extrabold tracking-tight text-white">
            {{ training.name }}
          </h2>
        </div>
        <div>
          <NButton v-if="userIsRegistered" type="success" size="small" class="float-right">
            <template #default>
              <IFluentCheckmarkCircle24Filled class="mr-1" />
              Užsiregistruota
            </template>
          </NButton>
        </div>
      </div>
    </div>
    <div class="mt-4 grid grid-cols-2 gap-6 max-sm:grid-cols-1">
      <NCard>
        <div class="grid grid-cols-2 gap-4">
          <div class="items-center gap-2">
            <IFluentCalendar20Regular />
            Mokymų pradžia:
            <span class="font-bold">
              {{ formatStaticTime(training.start_time, {
                month: "long", day: "numeric", hour: "2-digit", minute: "2-digit"
              })
              }}
            </span>
          </div>
          <div class=" items-center gap-2">
            <IFluentCalendar20Regular />
            Mokymų pabaiga:
            <span class="font-bold">
              {{ formatStaticTime(training.end_time, {
                month: "long", day: "numeric", hour: "2-digit", minute: "2-digit"
              }) }}
            </span>
          </div>
          <div v-if="training.address" class=" items-center gap-2">
            <IFluentLocation24Regular />
            Vieta:
            <span class="font-bold">{{ training.address }}</span>
          </div>
          <div v-if="training.meeting_url" class=" items-center gap-2">
            <IFluentLink24Regular />
            <a :href="training.meeting_url" target="_blank" class="font-bold">Nuoroda</a>
          </div>
        </div>
      </NCard>
      <NCard>
        <div class="grid grid-cols-1">
          <div class="inline-flex items-center gap-2">
            Planuojamas dalyvių skaičius:
            <span>{{ registeredUserCount }} </span>
            /
            <span>{{ training.max_participants }}</span>
          </div>
          <div class="inline-flex items-center gap-2">
            Organizuoja:
            <span>{{ training.institution?.name }}</span>
          </div>
          <div class="inline-flex items-center gap-2">
            Pagrindinis organizatorius (-ė):
            <UserPopover show-name :size="20" :user="training.organizer" />
          </div>
        </div>
      </NCard>
    </div>
    <div class="typography mt-4">
      <div v-html="training.description" />
    </div>
    <NDivider />
    <NTabs class="my-4" type="line" :default-value="defaultTab">
      <NTabPane name="registration">
        <template #tab>
          <IFluentCircleOff16Regular v-if="!userCanRegister" class="mr-1" />
          <IFluentCircle24Regular v-else-if="!userIsRegistered" class="mr-1" />
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
        <NCard v-else-if="!userCanRegister" class="mt-4 max-w-xl">
          <template #header>
            <strong>Negalite registruotis</strong>
          </template>
          Susisiekite su mokymų organizatoriais dėl registracijos atvėrimo.
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
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";
import UserPopover from "@/Components/Avatars/UserPopover.vue";
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import ProgrammePlanner from "@/Features/Admin/ProgrammePlanner/ProgrammePlanner.vue";
import { formatStaticTime } from "@/Utils/IntlTime";
import { Link } from "@inertiajs/vue3";
import { NCard } from "naive-ui";
import { computed } from "vue";

const props = defineProps<{
  training: App.Entities.Training;
  registeredUserCount: number;
  userIsRegistered: boolean;
  userCanRegister: boolean;
}>();

const defaultTab = computed(() => {
  if (props.userIsRegistered) {
    return "programme";
  }

  return "registration";
});
</script>
