<template>
  <Button v-if="canPublish" variant="outline" size="sm" data-slot="collection-publish" @click="emit('publish')">
    <Eye aria-hidden="true" />
    {{ $t('Paskelbti') }}
  </Button>
  <Button v-if="canDraft" variant="outline" size="sm" data-slot="collection-draft" @click="emit('draft')">
    <FilePenLine aria-hidden="true" />
    {{ $t('Į juodraščius') }}
  </Button>
  <Button variant="destructive" size="sm" data-slot="collection-delete" @click="emit('delete')">
    <Trash2 aria-hidden="true" />
    {{ $t('Ištrinti') }}
  </Button>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, FilePenLine, Trash2 } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

/** The selection bar's publishing actions; a status every selected row already has is not offered. */
defineProps<{
  canPublish: boolean;
  canDraft: boolean;
}>();

const emit = defineEmits<{
  publish: [];
  draft: [];
  delete: [];
}>();
</script>
