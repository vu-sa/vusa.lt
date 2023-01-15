<template>
  <PageContent :title="contact.name">
    <template #aside-header>
      <div class="inline-flex gap-2">
        <ActivityLogButton :activities="contact.activities" />
        <MoreOptionsButton
          edit
          delete
          @edit-click="handleEdit"
          @delete-click="handleDelete"
        ></MoreOptionsButton>
      </div>
    </template>
    <NDivider />
    <div class="min-h-[45vh]"></div>
    <CommentPart
      v-model:text="commentText"
      class="mt-auto h-[35vh] border border-zinc-400 dark:border-zinc-700"
      :commentable_type="'contact'"
      :model="contact"
    />
  </PageContent>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { ref } from "vue";

import ActivityLogButton from "@/Features/Admin/ActivityLogViewer/ActivityLogButton.vue";
import CommentPart from "@/Features/Admin/Workspace/CommentPart.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

const props = defineProps<{
  contact: App.Entities.Contact;
}>();

const commentText = ref("");

const handleEdit = () => {
  Inertia.get(route("contacts.edit", props.contact.id));
};

const handleDelete = () => {
  Inertia.delete(route("contacts.destroy", props.contact.id));
};
</script>
