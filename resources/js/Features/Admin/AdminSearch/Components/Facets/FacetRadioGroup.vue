<template>
  <RadioGroup :model-value="selectedValue" @update:model-value="$emit('select', $event)">
    <div class="space-y-1">
      <div
        v-for="item in values"
        :key="item.value"
        :class="[
          'flex items-center gap-2.5 p-2 rounded-md transition-colors',
          'hover:bg-accent/50',
          item.value === selectedValue ? 'bg-accent' : '',
        ]"
      >
        <RadioGroupItem :value="item.value" :id="`radio-${item.value}`" />
        <Label
          :for="`radio-${item.value}`"
          :class="[
            'flex items-center justify-between flex-1 cursor-pointer',
            item.count === 0 ? 'opacity-50' : '',
          ]"
        >
          <span
            :class="[
              'text-sm',
              item.value === selectedValue ? 'font-medium text-foreground' : 'text-muted-foreground',
            ]"
          >
            {{ labelFormatter(item.value) }}
          </span>
          <Badge variant="secondary" class="text-xs">
            {{ item.count }}
          </Badge>
        </Label>
      </div>

      <!-- Clear Selection -->
      <Button
        v-if="selectedValue"
        variant="ghost"
        size="sm"
        class="w-full text-xs mt-2"
        @click="$emit('select', '')"
      >
        <X class="size-3 mr-1" />
        {{ $t('Išvalyti pasirinkimą') }}
      </Button>
    </div>
  </RadioGroup>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n'
import { X } from 'lucide-vue-next'

import { Label } from '@/Components/ui/label'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group'

import type { AdminFacetValue } from '../../Types/AdminSearchTypes'

interface Props {
  values: AdminFacetValue[]
  selectedValue: string | undefined
  labelFormatter?: (value: string) => string
}

interface Emits {
  (e: 'select', value: string): void
}

withDefaults(defineProps<Props>(), {
  labelFormatter: (value: string) => value,
})

defineEmits<Emits>()
</script>
