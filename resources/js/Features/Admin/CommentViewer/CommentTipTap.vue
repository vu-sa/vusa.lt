<!--
  CommentTipTap - Comment form wrapper around TiptapEditor
  
  This is a specialized component for comment forms that includes:
  - User avatar
  - Minimal text editor
  - Submit button
  
  For general-purpose editing, use TiptapEditor directly with appropriate preset.
-->
<template>
  <div class="flex flex-col" style="max-height: 280px">
    <div :class="{ 'rounded-t-md': roundedTop }"
      class="grid grid-cols-[60px_1fr] overflow-y-scroll rounded-b-md border dark:border-zinc-600">
      <div class="flex justify-center items-center">
        <UserAvatar :size="23" class="sticky top-4" :user="$page.props.auth?.user" />
      </div>
      <TiptapEditor
        v-model="internalText"
        preset="minimal"
        :html="true"
        :placeholder="$t('forms.commentPlaceholder')"
        class="comment-editor"
      />
    </div>
    <div class="border-top-0 flex items-center justify-end gap-2 border-zinc-400 p-4">
      <Button size="sm" :disabled="disabled || loading" @click="$emit('submit:comment')">
        <Spinner v-if="loading" />
        <IFluentSend24Filled v-else />
        {{ submitText ?? $t("Pateikti") }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";

import TiptapEditor from "@/Components/TipTap/TiptapEditor.vue";
import { Button } from "@/Components/ui/button";
import { Spinner } from "@/Components/ui/spinner";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

import IFluentSend24Filled from "~icons/fluent/send24-filled";

const props = defineProps<{
  text: string | null;
  disabled: boolean;
  loading: boolean;
  roundedTop?: boolean;
  submitText?: string;
}>();

const emit = defineEmits<{
  'update:text': [value: string | null];
  'submit:comment': [];
}>();

// Two-way binding adapter for TiptapEditor (uses modelValue) to CommentTipTap (uses text)
const internalText = computed({
  get: () => props.text,
  set: (value) => {
    // TiptapEditor with html=true emits string
    emit('update:text', value as string | null);
  },
});
</script>

<style scoped>
.comment-editor :deep(.tiptap-toolbar) {
  display: none;
}

.comment-editor :deep(.tiptap-content) {
  border: none;
  min-height: 60px;
}
</style>
