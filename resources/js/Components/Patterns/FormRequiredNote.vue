<template>
  <p
    v-if="note"
    class="max-w-prose border-l border-border pl-3 text-sm leading-relaxed text-muted-foreground"
    data-slot="form-required-note"
  >
    {{ typeof note === 'string' ? note : $t('forms.required_note') }}
  </p>
</template>

<script setup lang="ts">
defineProps<{
  note?: boolean | string;
}>();
</script>
