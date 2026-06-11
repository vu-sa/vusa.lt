<template>
  <SectionCard
    :title="$t('Diskusija')"
    :icon="MessagesSquare"
    :count="commentsCount || undefined"
    :action-label="$t('Peržiūrėti')"
    :empty="comments.length === 0"
    @action="$emit('view-all')"
  >
    <div class="space-y-3">
      <div
        v-for="comment in comments"
        :key="comment.id"
        class="flex items-start gap-2.5"
      >
        <UserAvatar :user="comment.user" :size="28" class="mt-0.5 shrink-0" />
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2">
            <span class="truncate text-sm font-medium text-foreground">
              {{ comment.user?.name ?? $t('Nežinomas') }}
            </span>
            <span class="shrink-0 text-xs text-muted-foreground">
              {{ formatRelativeTime(comment.created_at) }}
            </span>
            <Badge v-if="comment.kind === 'poll'" variant="outline" class="shrink-0 gap-1 text-xs">
              <BarChart3 class="h-3 w-3" />
              {{ $t('Apklausa') }}
            </Badge>
          </div>
          <p class="line-clamp-2 text-sm text-muted-foreground">
            {{ snippet(comment.body) }}
          </p>
          <p v-if="comment.replies_count" class="mt-0.5 text-xs text-muted-foreground">
            {{ $t(':count atsakymai', { count: comment.replies_count }) }}
          </p>
        </div>
      </div>
    </div>

    <template #empty>
      <div class="py-8 text-center">
        <MessagesSquare class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
        <p class="text-sm text-muted-foreground">
          {{ $t('Diskusijų dar nėra') }}
        </p>
      </div>
    </template>
  </SectionCard>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { BarChart3, MessagesSquare } from 'lucide-vue-next';

import { SectionCard } from '@/Components/ui/section-card';
import { Badge } from '@/Components/ui/badge';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { formatRelativeTime } from '@/Utils/IntlTime';

interface PreviewComment {
  id: string;
  body: string;
  kind?: string;
  created_at: string;
  replies_count?: number;
  user?: { id: string | number; name: string; profile_photo_path?: string | null } | null;
}

defineProps<{
  comments: PreviewComment[];
  commentsCount?: number;
}>();

defineEmits<{
  'view-all': [];
}>();

/** Strip HTML tags from a Tiptap comment body for a plain-text preview. */
const snippet = (html: string): string => {
  return html
    .replace(/<[^>]*>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
};
</script>
