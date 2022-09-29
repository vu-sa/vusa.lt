<template>
  <PageContent :title="doing.title">
    <template #aside-header
      ><span class="prose dark:prose-invert">{{
        doing.question_group.title
      }}</span></template
    >
    <template #aside-card> <div class="main-card">Žymos</div> </template>

    <div class="main-card">
      <div class="mb-2 flex items-center gap-4">
        <h2 class="mb-0">Veiklos</h2>
        <NButton round size="tiny" secondary @click="showModal = true"
          ><template #icon><NIcon :component="AddCircle32Regular" /></template
          >Sukurti veiklą</NButton
        >
        <HelpTextModal class="ml-auto" title="Kas yra veikla?"
          ><p>
            Veikla – tai bet koks nutikęs veiksmas, susijęs su šiuo klausimu.
          </p></HelpTextModal
        >
      </div>
    </div>
    <div class="main-card">
      <h2 class="mb-0">Komentarai</h2>
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import {
  AddCircle32Regular,
  ArrowTurnRight20Filled,
  DocumentAdd24Regular,
  Edit20Filled,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NBreadcrumb,
  NBreadcrumbItem,
  NButton,
  NDataTable,
  NForm,
  NFormItemGi,
  NGrid,
  NIcon,
  NInput,
  NModal,
  NPopover,
  NSelect,
} from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import HelpTextModal from "@/Components/HelpTextModal.vue";

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

const props = defineProps<{
  doing: Record<string, any>;
}>();

const showModal = ref(false);
</script>
