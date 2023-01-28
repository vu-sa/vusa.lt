<template>
  <QuickContentCard>
    <div class="mb-2">
      <h2>{{ currentStateText.title }}</h2>
      <component :is="currentStateText.description"></component>
    </div>
    <template #action-button>
      <NPopover :disabled="doing.files.length > 0">
        Pirmiausia, įkelk bent vieną failą.
        <template #trigger>
          <NButton
            :strong="doing.files.length > 0"
            :disabled="doing.files.length === 0"
            icon-placement="right"
            @click="showStateChangeModal = true"
            ><template #icon
              ><NIcon :component="ArrowExportLtr24Regular"></NIcon></template
            >Naujinti būseną</NButton
          >
        </template>
      </NPopover>
    </template>
    <CardModal
      v-model:show="showStateChangeModal"
      title="Naujinti būseną"
      @close="showStateChangeModal = false"
    >
      <div class="not-prose relative w-full">
        <InfoText>Palik trumpą komentarą</InfoText>

        <CommentTipTap
          v-model:text="commentText"
          class="mt-4"
          rounded-top
          :loading="loading"
          :enable-approve="doing?.approvable"
          :disabled="false"
          @submit:comment="submitComment"
        >
          <template #submit-text>Pateikti</template>
        </CommentTipTap>
      </div>
    </CardModal>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import { NButton, NIcon, NPopover } from "naive-ui";
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import { ArrowExportLtr24Regular } from "@vicons/fluent";
import CardModal from "@/Components/Modals/CardModal.vue";
import CommentTipTap from "@/Features/Admin/CommentViewer/CommentTipTap.vue";
import Icons from "@/Types/Icons/filled";
import InfoText from "@/Components/SmallElements/InfoText.vue";
import ModelChip from "@/Components/Chips/ModelChip.vue";
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
  Record<"title" | "description", any>
> = {
  draft: {
    title: "Šablonas",
    description: (
      <span>
        Atlik
        <ModelChip>
          {{
            default: () => "Užduotys",
            icon: () => <NIcon component={Icons.TASK}></NIcon>,
          }}
        </ModelChip>
        skiltyje esančias užduotis ir pateik veiksmą tvirtinimui!
      </span>
    ),
  },
  pending_changes: {
    title: "Laukiama pakeitimų",
    description: (
      <span>
        Už veiksmą atsakingi asmenys turi atnaujinti informaciją ir pateikti
        tvirtinimui darkart."
      </span>
    ),
  },
  pending_padalinys_approval: {
    title: "Laukia padalinio tvirtinimo",
    description: (
      <span>Veiksmas yra pateiktas tvirtinimui padalinio koordinatoriams.</span>
    ),
  },
  pending_final_approval: {
    title: "Laukia galutinio tvirtinimo",
    description: (
      <span>Laukiama galutinio patvirtinimo iš centrinių koordinatorių.</span>
    ),
  },
  approved: {
    title: "Patvirtintas",
    description: (
      <span>
        Veiksmas patvirtintas. Po patvirtinimo, laukiama ataskaitos ir pateikimo
        užbaigimui.
      </span>
    ),
  },
  pending_completion: {
    title: "Laukia užbaigimo",
    description: (
      <span>
        Veiksmas įvykdytas, laukiama galutinio patikrinimo dėl įkeltų failų.
      </span>
    ),
  },
  completed: {
    title: "Užbaigtas",
    description: (
      <span>
        Veiksmas įvykdytas ir visa susijusi galutinė informacija yra įkelta.
      </span>
    ),
  },
  canceled: {
    title: "Atšauktas",
    description: <span>Veiksmas atšauktas.</span>,
  },
};

const currentStateText = computed(() => {
  return doingStateDescriptions[props.doing.state];
});
</script>
