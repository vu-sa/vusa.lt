<template>
  <ShowPageLayout
    v-model:current-tab="currentTab"
    :title="contact.name"
    :model="contact"
    :related-models="relatedModels"
  >
    <template #more-options>
      <MoreOptionsButton
        edit
        delete
        @edit-click="handleEdit"
        @delete-click="handleDelete"
      ></MoreOptionsButton>
    </template>
    <NDivider />
    <template #below>
      <CommentPart
        v-model:text="commentText"
        class="mt-auto h-min"
        :commentable_type="'contact'"
        :model="contact"
      />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="tsx">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import CommentPart from "@/Features/Admin/CommentViewer/CommentViewer.vue";
import Icons from "@/Types/Icons/filled";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import ShowPageLayout from "@/Components/Layouts/ShowModel/ShowPageLayout.vue";

const props = defineProps<{
  contact: App.Entities.Contact;
}>();

const commentText = ref("");
const currentTab = ref("Komentarai");

const relatedModels = [
  {
    name: "Komentarai",
    icon: Icons.COMMENT,
    count: props.contact.comments.length,
  },
];

const handleEdit = () => {
  router.get(route("contacts.edit", props.contact.id));
};

const handleDelete = () => {
  router.delete(route("contacts.destroy", props.contact.id));
};
</script>
