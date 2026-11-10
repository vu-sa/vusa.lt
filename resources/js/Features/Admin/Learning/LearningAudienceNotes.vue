<script setup lang="ts">
import type { LearningStepData } from '@/Types/learning';

defineProps<{ notes: LearningStepData['audienceNotes'] }>();
</script>

<template>
  <div v-if="notes.length" class="max-w-prose space-y-1 text-sm" data-slot="audience-notes">
    <h4 class="font-semibold">{{ $t('learning.path.audience_notes') }}</h4>
    <p v-for="(note, index) in notes" :key="index" class="whitespace-pre-line leading-relaxed text-muted-foreground">
      <span class="font-semibold text-foreground">{{ note.label }}</span> · {{ note.description }}
    </p>
  </div>
</template>
