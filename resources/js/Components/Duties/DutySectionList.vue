<template>
  <section data-slot="duty-section-list">
    <SectionHeading :title :icon :count="duties.length" class="mb-3" />

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
      <DutySummaryCard
        v-for="duty in duties"
        :key="duty.id"
        :duty
        :muted
        :show-status
        :show-holders
        :exclude-user-id="excludeUserId"
        :holder
      />
    </div>
  </section>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

import DutySummaryCard, { type DutySummaryHolder, type SummaryDuty } from './DutySummaryCard.vue';

import { SectionHeading } from '@/Components/Patterns';

/**
 * A titled grid of duty cards. Used for both the current and the previous
 * duties on a member profile, so the two lists can't drift apart visually.
 */
withDefaults(defineProps<{
  title: string;
  duties: SummaryDuty[];
  /** Lucide icon rendered before the title. */
  icon?: Component;
  /** Dim the cards — previous duties shouldn't outweigh active ones. */
  muted?: boolean;
  showStatus?: boolean;
  showHolders?: boolean;
  excludeUserId?: string | number;
  holder?: DutySummaryHolder | null;
}>(), {
  icon: undefined,
  muted: false,
  showStatus: false,
  showHolders: false,
  excludeUserId: undefined,
  holder: null,
});
</script>
