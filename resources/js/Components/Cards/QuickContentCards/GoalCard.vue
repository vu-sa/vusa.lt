<template>
  <QuickContentCard class="mb-4">
    <FadeTransition mode="out-in">
      <!-- TODO: Make card for many goals -->
      <div v-if="matter.goals && matter.goals?.length > 0">
        <div class="flex items-center gap-2">
          <Link
            class="inline-flex items-center gap-2"
            :href="route('goals.show', matter.goals[0].id)"
          >
            <NIcon :size="30" :component="Icons.GOAL" />
            <span class="text-2xl font-bold line-clamp-1">{{
              matter.goals[0].title
            }}</span>
          </Link>
        </div>
        <p class="mt-4">
          Šis svarstomas klausimas priklauso
          <strong>{{ matter.goals[0].title }}</strong> tikslui.
        </p>
      </div>
      <p v-else class="mt-2">
        Svarstomas klausimas nepriklauso jokiam tikslui.
      </p>
    </FadeTransition>
    <template #action-button>
      <div class="flex items-center gap-2">
        <Link
          v-if="matter.goals && matter.goals.length > 0"
          :href="route('goals.show', matter.goals[0].id)"
        >
          <NButton icon-placement="right" secondary size="small"
            ><template #icon
              ><NIcon :component="ArrowUpRight24Filled" /></template
            >Eiti</NButton
          >
        </Link>
        <NButton secondary size="small" @click="handleModalOpen">
          <template #icon><NIcon :component="Icons.GOAL"></NIcon></template>
          <slot>Pridėti?</slot></NButton
        >
        <CardModal
          v-model:show="showModal"
          display-directive="show"
          title="Pakeisti tikslą"
          @close="showModal = false"
          ><GoalSelectorForm
            :goals="goals"
            :loading="loading"
            :current="matter?.goals?.[0] ?? null"
            @submit="handleGoalChange"
        /></CardModal>
      </div>
    </template>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import { ArrowUpRight24Filled } from "@vicons/fluent";
import { Link, router } from "@inertiajs/vue3";
import { NButton, NIcon } from "naive-ui";
import { ref } from "vue";

import CardModal from "@/Components/Modals/CardModal.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import GoalSelectorForm from "@/Components/AdminForms/Special/GoalSelectorForm.vue";
import Icons from "@/Types/Icons/filled";
import QuickContentCard from "@/Components/Cards/QuickContentCards/QuickContentCard.vue";

const props = defineProps<{
  matter: App.Entities.Matter;
  goals: App.Entities.Goal[] | [] | undefined;
}>();

const showModal = ref(false);
const loading = ref(false);

const handleModalOpen = () => {
  loading.value = true;
  router.reload({ only: ["goals"], onSuccess: () => (loading.value = false) });
  showModal.value = true;
};

const handleGoalChange = (goal: App.Entities.Goal) => {
  router.post(
    route("matters.attachGoal", props.matter.id),
    { goal_id: goal.id },
    {
      onSuccess: () => {
        showModal.value = false;
      },
    }
  );
};
</script>
