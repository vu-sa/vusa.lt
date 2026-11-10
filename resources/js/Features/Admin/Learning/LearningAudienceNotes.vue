<script setup lang="ts">
import type { LearningStepData } from '@/Types/learning';

defineProps<{ notes: LearningStepData['audienceNotes'] }>();
</script>

<template>
  <div v-if="notes.length" class="max-w-prose space-y-1.5 border-l-2 border-brand-fill pl-4 text-sm" data-slot="audience-notes">
    <h4 class="text-[11px] font-bold uppercase tracking-widest text-brand">{{ $t('learning.path.audience_notes') }}</h4>
    <p v-for="(note, index) in notes" :key="index" class="whitespace-pre-line leading-relaxed text-muted-foreground">
      <span class="font-semibold text-foreground">{{ note.label }}</span> · {{ note.description }}
    </p>
  </div>
</template>
