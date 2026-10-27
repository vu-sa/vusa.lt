<template>
  <div class="space-y-3">
    <div class="space-y-2">
      <Label :for="`${id}-url`">{{ $t('rich-content.link_url') }}</Label>
      <Input
        :id="`${id}-url`"
        v-model="url"
        data-testid="link-url"
        placeholder="https://..."
        :aria-invalid="url.trim() !== '' && !valid"
        @keydown.enter.prevent="submit"
        @keydown.esc.stop.prevent="$emit('cancel')"
      />
      <p v-if="url.trim() && !valid" class="text-xs text-destructive" role="alert">
        {{ $t('rich-content.link_invalid_url') }}
      </p>
    </div>
    <div class="space-y-2">
      <Label :for="`${id}-text`">{{ $t('rich-content.link_text') }}</Label>
      <Input
        :id="`${id}-text`"
        v-model="text"
        data-testid="link-text"
        :placeholder="url || $t('rich-content.link_text')"
        @keydown.enter.prevent="submit"
        @keydown.esc.stop.prevent="$emit('cancel')"
      />
      <p class="text-xs text-muted-foreground">
        {{ hasSelectedText ? $t('rich-content.link_text_editing') : $t('rich-content.link_text_fallback_url') }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, useId } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { isValidTiptapLink } from './linkUrl';

import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

defineProps<{ hasSelectedText?: boolean }>();
const emit = defineEmits<{ submit: []; cancel: [] }>();
const url = defineModel<string>('url', { required: true });
const text = defineModel<string>('text', { required: true });
const id = useId();
const valid = computed(() => isValidTiptapLink(url.value));

function submit() {
  if (valid.value) emit('submit');
}
</script>
