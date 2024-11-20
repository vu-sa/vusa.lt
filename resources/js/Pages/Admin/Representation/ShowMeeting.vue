<template>
  <ShowPageLayout :model="meeting" :breadcrumb-options="breadcrumbOptions" :related-models="relatedModels"
    :title="`${mainInstitution?.name} (${meetingTitle})`" :current-tab="currentTab" @change:tab="currentTab = $event">
    <template #more-options>
      <MoreOptionsButton edit @edit-click="showMeetingModal = true" />
      <CardModal v-model:show="showMeetingModal" title="Redaguoti posėdžio datą" @close="showMeetingModal = false">
        <MeetingForm class="mt-2" :meeting="meeting" @submit="handleMeetingFormSubmit" />
      </CardModal>
    </template>
    <div class="my-4 flex items-center gap-4">
      <NButton size="small" @click="showAgendaItemStoreModal = true">
        {{ $t("Pridėti klausimą") }}
        <template #icon>
          <NIcon size="16" :component="Icons.AGENDA_ITEM" />
        </template>
      </NButton>
      <NDivider vertical style="background-color: lightgray;" />
      <div class="flex items-center gap-2">
        <NSwitch v-model:value="showVoteOptions" size="small" />
        <label class="text-zinc-500 dark:text-zinc-400">{{ $t("Rodyti balsavimo parinktis") }}</label>
      </div>
    </div>
    <NDataTable scroll-x="800" size="small" class="mt-4" :data="meeting.agenda_items" :columns />
    <CardModal v-model:show="showAgendaItemStoreModal" title="Pridėti darbotvarkės punktą" class="max-w-md"
      :segmented="{ content: 'soft' }" @close="showAgendaItemStoreModal = false">
      <AgendaItemForm :agenda-item="{ title: null }" @submit="handleAgendaItemStore" />
    </CardModal>
    <CardModal v-model:show="showAgendaItemUpdateModal" title="Redaguoti darbotvarkės punktą"
      :segmented="{ content: 'soft' }" @close="showAgendaItemUpdateModal = false">
      <AgendaItemForm v-if="selectedAgendaItem" :agenda-item="selectedAgendaItem" @submit="handleAgendaItemUpdate" />
    </CardModal>
    <template #below>
      <FileManager v-if="currentTab === 'Failai'" :starting-path="meeting.sharepointPath"
        :fileable="{ ...meeting, type: 'Meeting' }" />
      <TaskManager v-else-if="currentTab === 'Užduotys'" :taskable="{ id: meeting.id, type: 'App\\Models\\Meeting' }"
        :tasks="meeting.tasks" />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { computed, provide, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import { formatStaticTime } from "@/Utils/IntlTime";
import { genitivizeEveryWord } from "@/Utils/String";
import { modelTypes } from "@/Types/formOptions";
import AgendaItemForm from "@/Components/AdminForms/AgendaItemForm.vue";
import CardModal from "@/Components/Modals/CardModal.vue";
import FileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";
import Icons from "@/Types/Icons/filled";
import MeetingForm from "@/Components/AdminForms/MeetingForm.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import type { BreadcrumbOption } from "@/Components/Layouts/ShowModel/Breadcrumbs/AdminBreadcrumbDisplayer.vue";
import { DataTableProps, NButton, NTooltip } from "naive-ui";
import TriStateButton from "@/Components/Buttons/TriStateButton.vue";
import { trans as $t } from "laravel-vue-i18n";

import IMdiThumbsUpOutline from "~icons/mdi/thumbs-up-outline";
import IMdiThumbsDownOutline from "~icons/mdi/thumbs-down-outline";
import IMdiThumbsUpDownOutline from "~icons/mdi/thumbs-up-down-outline";

const props = defineProps<{
  meeting: App.Entities.Meeting;
}>();

const showMeetingModal = ref(false);
const showAgendaItemStoreModal = ref(false);
const showAgendaItemUpdateModal = ref(false);
const currentTab = useStorage("show-meeting-tab", "Failai");
const showVoteOptions = useStorage("show-vote-options", false);

// Used in FileUploader.vue
provide<boolean>("keepFileable", true);

console.log(props.meeting.agenda_items);

const selectedAgendaItem = ref<App.Entities.AgendaItem | null>(null);

const handleMeetingFormSubmit = (meeting: App.Entities.Meeting) => {
  router.patch(route("meetings.update", meeting.id), meeting, {
    onSuccess: () => {
      showMeetingModal.value = false;
    },
  });
};

const mainInstitution: App.Entities.Institution | string =
  props.meeting.institutions?.[0] ?? "Be institucijos";

const meetingTitle =
  props.meeting.title === ""
    ? `${formatStaticTime(new Date(props.meeting.start_time), {
      year: "numeric",
      month: "long",
      day: "numeric",
    })} ${genitivizeEveryWord(mainInstitution.name)} posėdis`
    : props.meeting.title;

const sharepointFileTypeOptions = computed(() => {
  return modelTypes.sharepointFile.map((type) => ({
    label: type,
    value: type,
  }));
});

const handleAgendaClick = (agendaItem: App.Entities.AgendaItem) => {
  selectedAgendaItem.value = agendaItem;
  showAgendaItemUpdateModal.value = true;
};

const handleAgendaItemDelete = (agendaItem: App.Entities.AgendaItem) => {
  router.delete(route("agendaItems.destroy", agendaItem.id));
};

const breadcrumbOptions: BreadcrumbOption[] = [
  {
    label: mainInstitution.name,
    icon: Icons.INSTITUTION,
    routeOptions: {
      name: "institutions.show",
      params: {
        institution: mainInstitution.id,
      },
    },
  },
  {
    label: meetingTitle,
    icon: Icons.MEETING,
  },
];

const columns = [
  {
    key: 'index',
    title() {
      return $t('No.');
    },
    width: 40, render(row, index) { return index + 1; }
  },
  {
    key: 'title',
    title() {
      return $t('forms.fields.title');
    },
    fixed: 'left', minWidth: 150
  },
  {
    key: 'decision',
    title() {
      return $t('Sprendimas');
    },
    width: 110,
    render(row: App.Entities.AgendaItem) {
      return <TriStateButton state={row.decision} size="tiny" positiveText="Sprendimas priimtas / klausimas patvirtintas" negativeText="Klausimui / sprendimui nepritarta" neutralText="Joks sprendimas (teigiamas ar neigiamas) nepriimtas / susilaikyta" row={row} showOptions={showVoteOptions.value} onEnableOptions={() => showVoteOptions.value = true} onChangeState={(state) => {
        row.decision = state;
        handleAgendaItemUpdate(row);
      }} />
    }
  },
  {
    key: 'student_vote',
    title() {
      return $t('Kaip balsavo studentai');
    },
    width: 110,
    render(row: App.Entities.AgendaItem) {
      return <TriStateButton state={row.student_vote} size="tiny" row={row} showOptions={showVoteOptions.value} positiveText="Visi pritarė" negativeText="Visi nepritarė" neutralText="Visi susilaikė" onEnableOptions={() => showVoteOptions.value = true}
        onChangeState={(state) => {
          row.student_vote = state;
          handleAgendaItemUpdate(row);
        }}
      />
    }
  },
  {
    key: 'student_benefit',
    title() {
      return $t('Ar palanku studentams') + '?';
    },
    width: 120,
    render(row: App.Entities.AgendaItem) {
      return <TriStateButton state={row.student_benefit} size="tiny" row={row} showOptions={showVoteOptions.value} positiveIcon={IMdiThumbsUpOutline} negativeIcon={IMdiThumbsDownOutline} neutralIcon={IMdiThumbsUpDownOutline} onEnableOptions={() => showVoteOptions.value = true} positiveText="Palanku" negativeText="Nepalanku" neutralText="Sprendimas neturi tiesioginės ar netiesioginės įtakos studentams"
        onChangeState={(state) => {
          row.student_benefit = state;
          handleAgendaItemUpdate(row);
        }}
      >
      </TriStateButton>
    }
  },
  {
    key: 'description',
    title() {
      return $t('forms.fields.description');
    },
    width: 100,
    render(row: App.Entities.AgendaItem) {
      return <NTooltip trigger="hover" placement="top">
        {{
          trigger: () => <NButton size="tiny" color={!row.description ? 'gray' : undefined} quaternary onClick={() => handleAgendaClick(row)}>
            {{
              icon: () => <>{!row.description ? <IMdiFileDocumentPlus /> : <IMdiFileDocument />}</>,
            }}
          </NButton>,
          default: () => !row.description ? $t('Pridėti aprašymą') : row.description,
        }}
      </NTooltip>
    }
  },
  {
    key: 'actions', render(row) {
      return <MoreOptionsButton
        edit
        delete
        small
        onEditClick={() => {
          handleAgendaClick(row);
        }}
        onDeleteClick={() => {
          handleAgendaItemDelete(row);
        }}
      ></MoreOptionsButton>
    }
  }
]

const handleAgendaItemStore = (agendaItem: App.Entities.AgendaItem) => {
  router.post(
    route("agendaItems.store"),
    { agendaItemTitles: [agendaItem.title], meeting_id: props.meeting.id },
    {
      onSuccess: () => {
        showAgendaItemStoreModal.value = false;
      },
    }
  );
};

const handleAgendaItemUpdate = (agendaItem: App.Entities.AgendaItem) => {
  router.patch(route("agendaItems.update", agendaItem.id), agendaItem, {
    onSuccess: () => {
      showAgendaItemUpdateModal.value = false;
    },
  });
};

const relatedModels = [
  {
    name: "Failai",
    icon: Icons.SHAREPOINT_FILE,
  },
  {
    name: "Užduotys",
    icon: Icons.TASK,
    count: props.meeting.tasks?.length,
  },
];
</script>
