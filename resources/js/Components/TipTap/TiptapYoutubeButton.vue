<template>
  <!-- The slotted trigger is the real button; this only catches its click. -->
  <!-- eslint-disable-next-line vuejs-accessibility/click-events-have-key-events, vuejs-accessibility/no-static-element-interactions -->
  <div v-if="$slots.default" @click="showModal = true">
    <slot />
  </div>

  <Dialog :open="showModal" @update:open="showModal = $event">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ $t('rich-content.youtube_upload_title') }}</DialogTitle>
        <DialogDescription>
          {{ $t('rich-content.youtube_upload_description') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-2">
        <Label for="youtube-url">{{ $t('rich-content.youtube_url_label') }}</Label>
        <Input
          id="youtube-url"
          v-model="youtubeUrl"
          placeholder="https://www.youtube.com/watch?v=..."
          type="url"
          @keydown.enter.prevent="addYoutubeVideo"
        />
        <p class="text-xs text-muted-foreground">
          {{ $t('rich-content.youtube_url_hint') }}
        </p>
      </div>

      <DialogFooter>
        <Button type="button" variant="outline" @click="showModal = false">
          {{ $t('Atšaukti') }}
        </Button>
        <Button type="button" :disabled="!youtubeUrl.trim()" @click="addYoutubeVideo">
          {{ $t('rich-content.insert') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const emit = defineEmits<(e: 'submit', youtubeUrl: string) => void>();

/** Controlled by a menu item when there is no slotted trigger. */
const showModal = defineModel<boolean>('open', { default: false });
const youtubeUrl = ref('');

watch(showModal, (isOpen) => {
  if (isOpen) {
    youtubeUrl.value = '';
  }
});

function addYoutubeVideo() {
  if (!youtubeUrl.value.trim()) return;
  emit('submit', youtubeUrl.value.trim());
  showModal.value = false;
}
</script>
