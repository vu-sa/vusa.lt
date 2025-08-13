<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'

const props = defineProps<{
  institutionId: string
  open: boolean
}>()
const emit = defineEmits<{ (e: 'close'): void, (e: 'created'): void }>()

const mode = ref<'blackout' | 'heads_up'>('blackout')
const confidence = ref<'low' | 'medium' | 'high'>('medium')
const note = ref('')
const duration = ref<number>(14)

const maxHeadsUp = 7
const validDurations = [7, 14, 28, 60]

const durations = computed(() => mode.value === 'blackout' ? validDurations : [1,2,3,4,5,6,7])

watch(mode, () => {
  if (mode.value === 'heads_up' && duration.value > maxHeadsUp) duration.value = 7
  if (mode.value === 'blackout' && !validDurations.includes(duration.value)) duration.value = 14
})

function submit() {
  router.post(route('institutions.check-ins.store', props.institutionId), {
    mode: mode.value,
    duration_days: duration.value,
    confidence: confidence.value,
    note: note.value || undefined,
  }, { onSuccess: () => { emit('created'); emit('close') }, preserveScroll: true })
}
</script>

<template>
  <Dialog :open="open" @update:open="() => emit('close')">
    <DialogContent class="sm:max-w-[520px]">
      <DialogHeader>
        <DialogTitle>{{ $t('Pridėti pažymą') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Patvirtinkite, kad iki pasirinktos datos susitikimų neplanuojama (arba heads-up).') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <div class="flex gap-2">
          <Button :variant="mode==='blackout' ? 'default' : 'outline'" @click="mode='blackout'">{{ $t('Blackout') }}</Button>
          <Button :variant="mode==='heads_up' ? 'default' : 'outline'" @click="mode='heads_up'">{{ $t('Heads-up') }}</Button>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('Trukmė (dienomis)') }}</label>
          <div class="flex flex-wrap gap-2">
            <Button v-for="d in durations" :key="d" :variant="d===duration ? 'default' : 'outline'" @click="duration=d">+{{ d }}</Button>
          </div>
          <p v-if="mode==='blackout' && (duration===28 || duration===60)" class="text-xs text-amber-600">
            {{ $t('Ilgas laikotarpis – naudokite atsakingai.') }}
          </p>
        </div>

        <div v-if="mode==='blackout'" class="space-y-2">
          <label class="text-sm font-medium">{{ $t('Užtikrintumas') }}</label>
          <select v-model="confidence" class="border rounded px-3 py-2">
            <option value="low">{{ $t('Žemas') }}</option>
            <option value="medium">{{ $t('Vidutinis') }}</option>
            <option value="high">{{ $t('Aukštas') }}</option>
          </select>
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium">{{ $t('Pastaba (nebūtina)') }}</label>
          <textarea v-model="note" rows="3" class="w-full border rounded px-3 py-2" />
        </div>

        <p class="text-xs text-muted-foreground">
          {{ $t('Pateikdami pažymą patvirtinate, kad iki pasirinktos datos susitikimų neplanuojama. Ši informacija matoma nariams ir administratoriams.') }}
        </p>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="emit('close')">{{ $t('Atšaukti') }}</Button>
        <Button @click="submit">{{ $t('Išsaugoti') }}</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
/* minimal */
</style>
