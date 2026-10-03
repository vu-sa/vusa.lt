<template>
  <div class="flex max-h-72 flex-col">
    <div class="grid grid-cols-[52px_1fr] overflow-y-auto border border-border bg-card">
      <div class="flex justify-center items-center">
        <UserAvatar :size="23" class="sticky top-4" :user="$page.props.auth?.user" />
      </div>
      <TiptapEditor
        v-model="internalText"
        preset="minimal"
        html
        :placeholder="$t('forms.commentPlaceholder')"
        class="comment-editor"
      />
    </div>
    <div class="flex items-center justify-end gap-2 border-x border-b border-border bg-card p-3">
      <Button variant="brand" :disabled="disabled || loading" @click="$emit('submit:comment')">
        <Spinner v-if="loading" />
        <Send v-else class="size-4" aria-hidden="true" />
        {{ submitText ?? $t("Pateikti") }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Send } from 'lucide-vue-next';

import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { Spinner } from '@/Components/ui/spinner';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';

const props = defineProps<{
  text: string | null;
  disabled: boolean;
  loading: boolean;
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
