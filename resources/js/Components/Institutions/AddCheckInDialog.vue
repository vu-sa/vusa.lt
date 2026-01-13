<template>
  <Dialog :open @update:open="() => emit('close')">
    <DialogContent class="sm:max-w-[520px]">
      <DialogHeader>
        <DialogTitle>{{ $t('Pranešti apie posėdžio nebuvimą') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Nurodykite laikotarpį, kada posėdžiai nėra planuojami. Tai padeda sekti atstovavimo organizavimą.') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-2">
            <Label>{{ $t('Pradžios data') }}</Label>
            <DatePicker v-model="startDate" />
          </div>

          <div class="space-y-2">
            <Label>{{ $t('Pabaigos data') }}</Label>
            <DatePicker v-model="endDate" />
          </div>
        </div>

        <div class="space-y-2">
          <Label>{{ $t('Pastaba (neprivaloma)') }}</Label>
          <Textarea v-model="note" :placeholder="$t('Pvz., atostogų laikotarpis, neveiklus semestro laikas...')"
            rows="3" />
        </div>

        <p class="text-xs text-muted-foreground">
          Galima pranešti apie posėdžių nebuvimą iki 3 mėnesių į priekį.
        </p>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="emit('close')">
          {{ $t('Atšaukti') }}
        </Button>
        <Button @click="submit">
          {{ $t('Išsaugoti pranešimą') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'
import { DatePicker } from '@/Components/ui/date-picker'
import { Label } from '@/Components/ui/label'
import { Textarea } from '@/Components/ui/textarea'

const props = defineProps<{
  institutionId: string
  open: boolean
}>()
const emit = defineEmits<{ (e: 'close'): void, (e: 'created'): void }>()

const startDate = ref<Date>(new Date(Date.now())) // 1 day from now
const endDate = ref<Date>(new Date(Date.now() + 14 * 24 * 60 * 60 * 1000)) // 14 days from now
const note = ref('')

function formatDate(date: Date): string {
  return date.toISOString().split('T')[0] as string
}

function submit() {
  router.post(route('institutions.check-ins.store', props.institutionId), {
    start_date: formatDate(startDate.value),
    end_date: formatDate(endDate.value),
    note: note.value || undefined,
  }, { onSuccess: () => { emit('created'); emit('close') }, preserveScroll: true })
}
</script>
