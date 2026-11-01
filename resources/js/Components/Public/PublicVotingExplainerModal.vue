<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-xl">
      <DialogHeader>
        <DialogTitle>{{ $t('voting.explainer.modal_title') }}</DialogTitle>
        <DialogDescription>
          {{ $t('voting.explainer.why_publish_text') }}
        </DialogDescription>
      </DialogHeader>

      <dl class="divide-y divide-border border-y border-border">
        <div v-for="item in voteFieldExplanations" :key="item.field" class="py-3">
          <dt class="text-sm font-medium text-foreground">
            {{ $t(item.label) }}
          </dt>
          <dd class="mt-0.5 text-sm text-muted-foreground">
            {{ $t(item.public) }}
          </dd>
        </div>
        <div class="py-3">
          <dt class="text-sm font-medium text-foreground">
            {{ $t('voting.explainer.student_included') }}
          </dt>
          <dd class="mt-0.5 text-sm text-muted-foreground">
            {{ $t('voting.explainer.student_included_description') }}
          </dd>
        </div>
      </dl>

      <DialogFooter>
        <Button variant="brand" @click="$emit('update:open', false)">
          {{ $t('Supratau') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { voteFieldExplanations } from '@/Constants/votingExplainer';

defineProps<{
  open: boolean;
}>();

defineEmits<{
  'update:open': [value: boolean];
}>();
</script>
