<template>
  <ShowPageLayout :title="contact.name" :model="contact">
    <template #more-options>
      <MoreOptionsButton
        edit
        delete
        @edit-click="handleEdit"
        @delete-click="handleDelete"
      ></MoreOptionsButton>
    </template>
    <NDivider />
    <div class="min-h-[45vh]"></div>
    <CommentPart
      v-model:text="commentText"
      class="mt-auto h-[35vh] border border-zinc-400 dark:border-zinc-700"
      :commentable_type="'contact'"
      :model="contact"
    />
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import CommentPart from "@/Features/Admin/Workspace/CommentPart.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";

const props = defineProps<{
  contact: App.Entities.Contact;
}>();

const commentText = ref("");

const handleEdit = () => {
  router.get(route("contacts.edit", props.contact.id));
};

const handleDelete = () => {
  router.delete(route("contacts.destroy", props.contact.id));
};
</script>
