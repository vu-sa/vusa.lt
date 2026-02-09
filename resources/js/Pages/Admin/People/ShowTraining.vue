<template>
  <AdminContentPage>
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
            <div class="inline-flex text-zinc-500 items-center whitespace-pre">
              <IFluentCalendar24Regular class="mr-1" />
              <span>
                {{ formatStaticTime(training.start_time, { month: "long", day: "numeric", }) }}
              </span>
              <span>{{ " - " }}</span>
              <span>
                {{ formatStaticTime(training.end_time, { month: "long", day: "numeric" }) }}
              </span>
            </div>
          </div>
          <Badge v-if="training.form === null" variant="warning">
            <IFluentSubtractCircle12Regular class="mr-1" />
            Registracija nevykdoma
          </Badge>
          <Badge v-else-if="!userCanRegister" variant="secondary">
            <IFluentCircleOff16Regular class="mr-1" />
            Negalite registruotis
          </Badge>
          <Badge v-else-if="!userIsRegistered" variant="warning">
            <IFluentCircle24Regular class="mr-1" />
            Registracija vyksta
          </Badge>
          <Badge v-else-if="userIsRegistered" variant="success">
            <IFluentCheckmarkCircle24Filled class="mr-1" />
            Uzsiregistruota
          </Badge>
        </div>
        <div class="text-zinc-900 mt-2">
          <div v-html="training.description" />
        </div>
      </CardHeader>
      <CardFooter>
        <!-- Registration component -->
        <div class="flex gap-2">
          <Link v-if="userCanRegister" :href="route('trainings.showRegistration', training.id)">
            <Button :disabled="!userCanRegister">
              Registruotis
            </Button>
          </Link>
          <Button v-else-if="userIsRegistered" variant="warning" disabled>
            Atsaukti registracija
          </Button>
          <Button v-else disabled>
            Registruotis
          </Button>
          <!-- Share button -->
          <Button variant="secondary" size="icon">
            <IFluentShareAndroid24Regular />
          </Button>
        </div>
      </CardFooter>
    </Card>
    <Tabs class="my-4" :default-value="defaultTab">
      <TabsList>
        <TabsTrigger value="summary">
          Pagrindinis
        </TabsTrigger>
        <TabsTrigger value="programme">
          Programa
        </TabsTrigger>
      </TabsList>
      <TabsContent value="summary">
        <Card class="border shadow-xs bg-white dark:bg-zinc-800 dark:border-zinc-700">
          <CardHeader>
            <h2 class="mb-0">
              Pagrindine informacija
            </h2>
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
              Organizuoja:
              <UserPopover show-name :size="20" :user="training.organizer" />
            </div>
          </CardContent>
        </Card>
      </TabsContent>
      <TabsContent value="programme">
        <ProgrammePlanner show-times :programme="training.programmes?.at(0)" />
      </TabsContent>
    </Tabs>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import UserPopover from '@/Components/Avatars/UserPopover.vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Card, CardContent, CardFooter, CardHeader } from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import ProgrammePlanner from '@/Features/Admin/ProgrammePlanner/ProgrammePlanner.vue';
import { formatStaticTime } from '@/Utils/IntlTime';
import Icons from '@/Types/Icons/filled';
import Sparkle20Filled from '~icons/fluent/sparkle20-filled';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{
  training: App.Entities.Training;
  registeredUserCount: number;
  userIsRegistered: boolean;
  userCanRegister: boolean;
}>();

const defaultTab = computed(() => {
  if (props.userIsRegistered) {
    return 'summary';
  }

  return 'summary';
});

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    'Mokymai',
    'trainings.index',
    {},
    props.training.name,
    Icons.TRAINING,
    Sparkle20Filled,
  ),
);
</script>
