<template>
  <aside 
    v-if="highlights && highlights.length > 0" 
    class="not-prose my-6 w-fit max-w-full rounded-2xl border border-[#bd2835]/20 bg-[#bd2835]/5 p-4 md:p-5"
  >
    <div class="mb-3 flex items-center gap-2">
      <div class="flex h-6 w-6 items-center justify-center rounded-lg bg-[#bd2835]">
        <LightbulbIcon class="h-3.5 w-3.5 text-white" />
      </div>
      <h4 class="text-xs font-bold uppercase tracking-wide text-[#bd2835]">{{ $t('Trumpai') }}</h4>
    </div>
    <ul class="space-y-2">
      <li
        v-for="(highlight, index) in highlights"
        :key="index"
        class="flex items-start gap-2.5"
      >
        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-[#bd2835]/15 text-xs font-bold text-[#bd2835]">
          {{ index + 1 }}
        </span>
        <span class="text-sm leading-relaxed text-foreground">{{ highlight }}</span>
      </li>
    </ul>
  </aside>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { LightbulbIcon } from 'lucide-vue-next';

defineProps<{
  highlights?: string[] | null;
}>();
</script>
