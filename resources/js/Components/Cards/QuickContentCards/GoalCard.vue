<template>
  <QuickContentCard class="mb-4">
    <FadeTransition mode="out-in">
      <!-- TODO: Make card for many goals -->
      <div v-if="goals.length > 0">
        <div class="flex items-center gap-2">
          <Link
            class="inline-flex items-center gap-2"
            :href="route('goals.show', goals[0].id)"
          >
            <NIcon :size="30" :component="StarLineHorizontal324Filled" />
            <span class="text-2xl font-bold line-clamp-1">{{
              goals[0].title
            }}</span>
          </Link>
        </div>
        <p class="mt-4">
          Šis svarstomas klausimas priklauso
          <strong>{{ goals[0].title }}</strong> tikslui.
        </p>
      </div>
      <p v-else class="mt-2">
        Svarstomas klausimas nepriklauso jokiam tikslui.
      </p>
    </FadeTransition>
    <template #action-button>
      <div class="flex items-center gap-2">
        <Link v-if="goals.length > 0" :href="route('goals.show', goals[0]?.id)">
          <NButton icon-placement="right" secondary size="small"
            ><template #icon
              ><NIcon :component="ArrowUpRight24Filled" /></template
            >Eiti</NButton
          >
        </Link>
        <GoalChanger :matter="matter"
          ><template v-if="goals">Pakeisti?</template></GoalChanger
        >
      </div>
    </template>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import {
  ArrowUpRight24Filled,
  StarLineHorizontal324Filled,
} from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";


import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import GoalChanger from "@/Components/Buttons/GoalChangerButton.vue";
import QuickContentCard from "@/Components/Cards/QuickContentCards/QuickContentCard.vue";

const props = defineProps<{
  goals: App.Entities.Goal[] | [];
  matter: App.Entities.Matter;
}>();

console.log(props.goals);
</script>