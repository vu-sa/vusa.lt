<template>
  <AdminContentPage>
    <template #above-header>
      <AdminBreadcrumbDisplayer :options="breadcrumbOptions" class="mb-4 w-full" />
    </template>
    <Card class="border shadow-xs bg-white dark:bg-zinc-800 dark:border-zinc-700">
      <div class="h-48">
        <img :src="training.image" class="size-full rounded-t-lg object-cover">
      </div>
      <CardHeader>
        <div class="flex justify-between">
          <div>
            <h2 class="mb-px text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
              {{ training.name }}
            </h2>
            <div class="inline-flex text-zinc-500 items-center">
              <IFluentCalendar24Regular class="mr-1" />
              <span>
                {{ formatStaticTime(training.start_time, { month: "long", day: "numeric", }) }}
              </span>
              <span>
                {{ formatStaticTime(training.end_time, { day: "numeric" }) }}
              </span>
            </div>
          </div>
          <NTag v-if="training.form === null" type="warning" size="small" round class="float-right">
            <template #icon>
              <IFluentSubtractCircle12Regular class="mr-1" />
            </template>
            Registracija nevykdoma
          </NTag>
          <NTag v-else-if="!userCanRegister" size="small" round class="float-right">
            <template #icon>
              <IFluentCircleOff16Regular class="mr-1" />
            </template>
            Negalite registruotis
          </NTag>
          <NTag v-else-if="!userIsRegistered" type="warning" size="small" round class="float-right">
            <template #icon>
              <IFluentCircle24Regular class="mr-1" />
            </template>
            Registracija vyksta
          </NTag>
          <NTag v-else-if="userIsRegistered" type="success" size="small" round class="float-right">
            <template #icon>
              <IFluentCheckmarkCircle24Filled class="mr-1" />
            </template>
            Užsiregistruota
          </NTag>
        </div>
        <div class="typography">
          <div v-html="training.description" />
        </div>
      </CardHeader>
      <CardFooter>
        <!-- Registration component -->
        <div class="flex gap-2">
          <Link v-if="userCanRegister" :href="route('trainings.showRegistration', training.id)">
          <NButton :disabled="!userCanRegister">
            Registruotis
          </NButton>
          </Link>
          <NButton v-else-if="userIsRegistered" type="warning" disabled>
            Atšaukti registraciją
          </NButton>
          <NButton v-else disabled>
            Registruotis
          </NButton>
          <!-- Share button -->
          <NButton>
            <template #icon>
              <IFluentShareAndroid24Regular />
            </template>
          </NButton>
        </div>
      </CardFooter>
    </Card>
    <NTabs class="my-4" type="segment" :default-value="defaultTab">
      <NTabPane name="summary" tab="Pagrindinis">
        <Card class="border shadow-xs bg-white dark:bg-zinc-800 dark:border-zinc-700">
          <CardHeader>
            <h2>Pagrindinė informacija</h2>
          </CardHeader>
          <CardContent class="flex flex-col gap-2">
            <div v-if="training.address" class="flex items-center gap-2">
              <IFluentLocation24Regular />
              <span class="font-bold">{{ training.address }}</span>
            </div>
            <div v-if="training.meeting_url" class="flex items-center gap-2">
              <IFluentLink24Regular />
              <a :href="training.meeting_url" target="_blank" class="underline">Prisijungti nuotoliu</a>
            </div>
            <div class="flex items-center gap-2">
              <Icons.INSTITUTION />
              <span>{{ training.institution?.name }}</span>
            </div>
            <div class="inline-flex items-center gap-2">
              <Icons.USER />
              <UserPopover show-name :size="20" :user="training.organizer" />
            </div>
          </CardContent>
        </Card>
      </NTabPane>
      <NTabPane name="programme" tab="Programa">
        <ProgrammePlanner show-times :programme="training.programmes?.at(0)" />
      </NTabPane>
    </NTabs>
  </AdminContentPage>
</template>

<script setup lang="ts">
import UserPopover from "@/Components/Avatars/UserPopover.vue";
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import AdminBreadcrumbDisplayer from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import Card from "@/Components/ShadcnVue/ui/card/Card.vue";
import CardContent from "@/Components/ShadcnVue/ui/card/CardContent.vue";
import CardHeader from "@/Components/ShadcnVue/ui/card/CardHeader.vue";
import ProgrammePlanner from "@/Features/Admin/ProgrammePlanner/ProgrammePlanner.vue";
import { formatStaticTime } from "@/Utils/IntlTime";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
import Icons from "@/Types/Icons/filled";

import Sparkle20Filled from "~icons/fluent/sparkle20-filled";
import CardFooter from "@/Components/ShadcnVue/ui/card/CardFooter.vue";

const props = defineProps<{
  training: App.Entities.Training;
  registeredUserCount: number;
  userIsRegistered: boolean;
  userCanRegister: boolean;
}>();

const defaultTab = computed(() => {
  if (props.userIsRegistered) {
    return "summary";
  }

  return "summary";
});

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: "Mokymai",
    routeOptions: {
      name: "trainings.index",
    },
  },
  {
    label: props.training.name,
    icon: Sparkle20Filled,
  },
];
</script>
