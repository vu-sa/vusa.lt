<script setup lang="ts">
import type { FieldProps } from './interface'
import { Button } from '@/Components/ShadcnVue/ui/button'
import { Calendar } from '@/Components/ShadcnVue/ui/calendar'
import { FormControl, FormDescription, FormField, FormItem, FormMessage } from '@/Components/ShadcnVue/ui/form'
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ShadcnVue/ui/popover'
import { cn } from '@/Utils/shadcn'

import { DateFormatter, getLocalTimeZone } from '@internationalized/date'
import { CalendarIcon } from 'lucide-vue-next'
import AutoFormLabel from './AutoFormLabel.vue'
import { beautifyObjectName } from './utils'

defineProps<FieldProps>()

const df = new DateFormatter('en-US', {
  dateStyle: 'long',
})
</script>

<template>
  <FormField v-slot="slotProps" :name="fieldName">
    <FormItem>
      <AutoFormLabel v-if="!config?.hideLabel" :required="required">
        {{ config?.label || beautifyObjectName(label ?? fieldName) }}
      </AutoFormLabel>
      <FormControl>
        <slot v-bind="slotProps">
          <div>
            <Popover>
              <PopoverTrigger as-child :disabled="disabled">
                <Button
                  variant="outline"
                  :class="cn(
                    'w-full justify-start text-left font-normal',
                    !slotProps.componentField.modelValue && 'text-zinc-500 dark:text-zinc-400',
                  )"
                >
                  <CalendarIcon class="mr-2 h-4 w-4" :size="16" />
                  {{ slotProps.componentField.modelValue ? df.format(slotProps.componentField.modelValue.toDate(getLocalTimeZone())) : "Pick a date" }}
                </Button>
              </PopoverTrigger>
              <PopoverContent class="w-auto p-0">
                <Calendar initial-focus v-bind="slotProps.componentField" />
              </PopoverContent>
            </Popover>
          </div>
        </slot>
      </FormControl>

      <FormDescription v-if="config?.description">
        {{ config.description }}
      </FormDescription>
      <FormMessage />
    </FormItem>
  </FormField>
</template>
