<template>
  <div class="min-w-0">
    <p
      v-if="eyebrow.length > 0"
      class="mb-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-[0.625rem] leading-snug font-bold uppercase tracking-[0.18em] text-brand"
      data-slot="institution-card-eyebrow"
    >
      <template v-for="(part, index) in eyebrow" :key="part">
        <span v-if="index > 0" class="text-muted-foreground/60" aria-hidden="true">/</span>
        <span>{{ part }}</span>
      </template>
    </p>
    <h3 class="text-pretty break-words text-2xl font-bold leading-tight tracking-tight text-foreground transition-colors group-hover:text-brand">
      <SmartLink
        :href
        class="plain outline-none after:absolute after:inset-0 after:content-[''] focus-visible:after:outline-2 focus-visible:after:outline-brand"
      >
        {{ title }}
      </SmartLink>
    </h3>
  </div>
</template>

<script setup lang="ts">
import SmartLink from '@/Components/Public/SmartLink.vue';

defineProps<{
  eyebrow: string[];
  href: string;
  title: string;
}>();
</script>
