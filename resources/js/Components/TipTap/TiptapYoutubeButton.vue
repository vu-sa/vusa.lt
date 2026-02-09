<template>
  <div @click="showModal = true">
    <slot />
  </div>

  <Dialog :open="showModal" @update:open="showModal = $event">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>Įkelti YouTube filmuką</DialogTitle>
        <DialogDescription>
          Įveskite YouTube vaizdo įrašo nuorodą, kurią norite įterpti.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <div class="space-y-2">
          <Label for="youtube-url">YouTube nuoroda</Label>
          <Input
            id="youtube-url"
            v-model="youtubeUrl"
            placeholder="https://www.youtube.com/watch?v=..."
            type="url"
          />
          <p class="text-xs text-muted-foreground">
            Įklijuokite YouTube vaizdo įrašo nuorodą
          </p>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="showModal = false">
          Atšaukti
        </Button>
        <Button :disabled="!youtubeUrl.trim()" @click="addYoutubeVideo">
          Įkelti
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

const emit = defineEmits<(e: 'submit', youtubeUrl: string) => void>();

const showModal = ref(false);
const youtubeUrl = ref('');

function addYoutubeVideo() {
  emit('submit', youtubeUrl.value);
  showModal.value = false;
}
</script>
