<template>
  <QuickContentCard>
    <div class="mb-2">
      <h2>{{ currentStateText.title }}</h2>
      <p>{{ currentStateText.description }}</p>
    </div>
    <template #action-button
      ><NButton @click="showStateChangeModal = true"
        >Atnaujinti būseną</NButton
      ></template
    >
    <CardModal
      v-model:show="showStateChangeModal"
      title="Keisti statusą"
      @close="showStateChangeModal = false"
    >
      <div class="not-prose relative w-full">
        <label>Komentaras</label>

        <CommentTipTap
          v-model:text="commentText"
          class="mt-4"
          rounded-top
          :loading="loading"
          :enable-approve="doing?.approvable"
          :disabled="false"
          @submit:comment="submitComment"
        >
          <template #submit-text>PATEIKTI</template>
        </CommentTipTap>
      </div>
    </CardModal>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import { NButton } from "naive-ui";
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import CardModal from "@/Components/Modals/CardModal.vue";
import CommentTipTap from "@/Features/Admin/CommentViewer/CommentTipTap.vue";
import QuickContentCard from "./QuickContentCard.vue";

const props = defineProps<{
  doing: App.Entities.Doing;
}>();

const showStateChangeModal = ref(false);
const loading = ref(false);
const commentText = ref<string | null>(null);

const submitComment = (decision?: "approve" | "reject") => {
  // add decision attribute
  loading.value = true;
  router.post(
    route("users.comments.store", usePage().props.auth?.user.id),
    {
      commentable_type: "doing",
      commentable_id: props.doing.id,
      comment: commentText.value,
      decision: decision ?? "progress",
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        loading.value = false;
      },
      onError: () => {
        loading.value = false;
      },
    }
  );
};

const doingStateDescriptions: Record<
  App.Entities.Doing["state"],
  Record<"title" | "description", string>
> = {
  draft: {
    title: "Šablonas",
    description:
      "Į šį veiksmą, prieš jį pateikiant tvirtinimui, reikia įkelti atitinkamus dokumentus ir atnaujinti informaciją.",
  },
  pending_changes: {
    title: "Laukiama pakeitimų",
    description:
      "Už veiksmą atsakingi asmenys turi atnaujinti informaciją ir pateikti tvirtinimui darkart.",
  },
  pending_padalinys_approval: {
    title: "Laukia padalinio tvirtinimo",
    description:
      "Veiksmas yra pateiktas tvirtinimui padalinio koordinatoriams.",
  },
  pending_final_approval: {
    title: "Laukia galutinio tvirtinimo",
    description: "Laukiama galutinio patvirtinimo iš centrinių koordinatorių.",
  },
  approved: {
    title: "Patvirtintas",
    description:
      "Veiksmas patvirtintas. Po patvirtinimo, laukiama ataskaitos ir pateikimo užbaigimui.",
  },
  pending_completion: {
    title: "Laukia užbaigimo",
    description:
      "Veiksmas įvykdytas, laukiama galutinio patikrinimo dėl įkeltų failų.",
  },
  completed: {
    title: "Užbaigtas",
    description:
      "Veiksmas įvykdytas ir visa susijusi galutinė informacija yra įkelta.",
  },
  canceled: {
    title: "Atšauktas",
    description: "Veiksmas atšauktas.",
  },
};

const currentStateText = computed(() => {
  return doingStateDescriptions[props.doing.state];
});
</script>
