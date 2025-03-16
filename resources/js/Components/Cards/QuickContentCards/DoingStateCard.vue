<template>
  <QuickContentCard class="border" :class="[currentStateText.borderColorClass]">
    <template #header>
      <h2 class="flex items-center gap-2">
        <NIcon :component="currentStateText.icon" /><span>{{ currentStateText.title }}</span>
      </h2>
    </template>
    <component :is="currentStateText.description" />

    <template #action-button>
      <NPopover :disabled="doing.files.length > 0">
        Pirmiausia, įkelk bent vieną failą.
        <template #trigger>
          <NButton :strong="doing.files.length > 0" :disabled="doing.files.length === 0" icon-placement="right"
            @click="showStateChangeModal = true"><template #icon>
              <NIcon :component="ArrowExportLtr24Regular" />
            </template>Naujinti būseną</NButton>
        </template>
      </NPopover>
    </template>
    <CardModal v-model:show="showStateChangeModal" title="Naujinti būseną" @close="showStateChangeModal = false">
      <div class="relative w-full">
        <InfoText>Palik trumpą komentarą</InfoText>

        <CommentTipTap v-model:text="commentText" class="mt-4" rounded-top :loading="loading"
          :enable-approve="doing?.approvable" :disabled="false" @submit:comment="submitComment">
          <template #submit-text>
            Pateikti
          </template>
        </CommentTipTap>
      </div>
    </CardModal>
  </QuickContentCard>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

import IconArrow from "~icons/fluent/arrow-export-ltr24-regular";
import IconCheckmarkCircle from "~icons/fluent/checkmark-circle24-regular";
import IconDocumentBulletListClock from "~icons/fluent/document-bullet-list-clock20-regular";
import IconDocumentEdit from "~icons/fluent/document-edit24-regular";

import CardModal from "@/Components/Modals/CardModal.vue";
import CommentTipTap from "@/Features/Admin/CommentViewer/CommentTipTap.vue";
import Icons from "@/Types/Icons/filled";
import InfoText from "@/Components/SmallElements/InfoText.vue";
import ModelChip from "@/Components/Tag/ModelChip.vue";
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
  Record<"title" | "description" | "borderColorClass" | "icon", any>
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
    borderColorClass: "border-zinc-500",
    icon: IconDocumentEdit,
  },
  pending_changes: {
    title: "Laukiama pakeitimų",
    description: (
      <span>
        Pasižiūrėk{" "}
        <ModelChip>
          {{
            default: () => "komentarų",
            icon: () => <NIcon component={Icons.COMMENT}></NIcon>,
          }}
        </ModelChip>{" "}
        skiltį, pataisyk informaciją, failus ir teik dar kartą!
      </span>
    ),
    borderColorClass: "border-vusa-yellow",
    icon: IconDocumentBulletListClock
  },
  pending_padalinys_approval: {
    title: "Laukia padalinio pritarimo arba komentarų",
    description: (
      <span>
        Veiksmas yra pateiktas padalinio koordinatoriams. Jei dokumentai bus
        sugrąžinti su komentarais atgal, pataisyk ir teik dar kartą.
      </span>
    ),
    borderColorClass: "border-blue-500",
    icon: IconDocumentBulletListClock,
  },
  pending_final_approval: {
    title: "Laukia galutinio pritarimo",
    description: (
      <span>
        Laukiama galutinio patvirtinimo iš centrinių koordinatorių! 👀
      </span>
    ),
    borderColorClass: "border-blue-500",
    icon: IconDocumentBulletListClock,
  },
  approved: {
    title: "Patvirtintas",
    description: (
      <span>
        Tavo veiksmas patvirtintas ir gali pradėti tolimesnius darbus! Kai
        įgyvendinsi veiklą, nepamiršk įkelti ataskaitos{" "}
        <ModelChip>
          {{
            default: () => "failų",
            icon: () => <NIcon component={Icons.SHAREPOINT_FILE}></NIcon>,
          }}
        </ModelChip>{" "}
        skiltyje.
      </span>
    ),
    borderColorClass: "border-green-500",
    icon: IconCheckmarkCircle,
  },
  pending_completion: {
    title: "Laukia užbaigimo",
    description: (
      <span>
        Veiksmas įvykdytas, laukiama galutinio patikrinimo iš koordinatorių!
      </span>
    ),
    borderColorClass: "border-blue-500",
    icon: IconDocumentBulletListClock,
  },
  completed: {
    title: "Užbaigtas",
    description: (
      <span>
        Veiksmas įvykdytas ir visa susijusi galutinė informacija yra įkelta!
        Woohoo, way to go! 🎉
      </span>
    ),
    borderColorClass: "border-green-500",
    icon: IconCheckmarkCircle,
  },
  cancelled: {
    title: "Atšauktas",
    description: <span>Veiksmas atšauktas.</span>,
    borderColorClass: "border-red-500",
    icon: IconArrow,
  },
};

const currentStateText = computed(() => {
  return doingStateDescriptions[props.doing.state];
});
</script>
