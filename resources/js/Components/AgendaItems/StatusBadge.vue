<template>
  <div class="flex items-center gap-1">
    <Badge 
      :variant="getBadgeVariant(state)" 
      class="text-xs cursor-pointer transition-all hover:scale-105"
      @click="$emit('click')"
    >
      <component :is="getStatusIcon(state)" class="h-3 w-3 mr-1" />
      {{ getStatusText(state) }}
    </Badge>
    
    <TooltipProvider v-if="showTooltip">
      <Tooltip>
        <TooltipTrigger asChild>
          <Button variant="ghost" size="icon" class="h-4 w-4 p-0 ml-1">
            <Info class="h-3 w-3" />
          </Button>
        </TooltipTrigger>
        <TooltipContent>
          <p class="text-xs max-w-48">{{ tooltipText }}</p>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/Components/ui/badge'
import { Button } from '@/Components/ui/button'
import { Tooltip, TooltipContent, TooltipTrigger, TooltipProvider } from '@/Components/ui/tooltip'

// Icons
import { Check, X, Minus, Clock, Info } from 'lucide-vue-next'

interface Props {
  state: string | null
  type?: 'decision' | 'student_vote' | 'student_benefit'
  size?: 'sm' | 'md'
  showTooltip?: boolean
  interactive?: boolean
}

interface Emits {
  (e: 'click'): void
}

const props = withDefaults(defineProps<Props>(), {
  type: 'decision',
  size: 'sm',
  showTooltip: false,
  interactive: true
})

defineEmits<Emits>()

const getBadgeVariant = (state: string | null) => {
  switch (state) {
    case 'positive':
      return 'default' // Green-ish
    case 'negative':  
      return 'destructive' // Red
    case 'neutral':
      return 'secondary' // Gray
    default:
      return 'outline' // Pending/unclear
  }
}

const getStatusIcon = (state: string | null) => {
  switch (state) {
    case 'positive':
      return Check
    case 'negative':
      return X
    case 'neutral':
      return Minus
    default:
      return Clock
  }
}

const getStatusText = (state: string | null) => {
  if (props.type === 'decision') {
    switch (state) {
      case 'positive':
        return 'Priimtas'
      case 'negative':
        return 'Nepriimtas'
      case 'neutral':
        return 'Susilaikyta'
      default:
        return 'Laukia'
    }
  }
  
  if (props.type === 'student_vote') {
    switch (state) {
      case 'positive':
        return 'Pritarė'
      case 'negative':
        return 'Nepritarė'
      case 'neutral':
        return 'Susilaikė'
      default:
        return 'Nebalsavo'
    }
  }
  
  if (props.type === 'student_benefit') {
    switch (state) {
      case 'positive':
        return 'Palanku'
      case 'negative':
        return 'Nepalanku'
      case 'neutral':
        return 'Neutralu'
      default:
        return 'Neaišku'
    }
  }
  
  return state || 'Nenustatyta'
}

const tooltipText = computed(() => {
  if (props.type === 'decision') {
    switch (props.state) {
      case 'positive':
        return 'Sprendimas priimtas / klausimas patvirtintas'
      case 'negative':
        return 'Klausimui / sprendimui nepritarta'
      case 'neutral':
        return 'Joks sprendimas (teigiamas ar neigiamas) nepriimtas / susilaikyta'
      default:
        return 'Sprendimas dar nepriimtas'
    }
  }
  
  if (props.type === 'student_vote') {
    switch (props.state) {
      case 'positive':
        return 'Visi studentų atstovai pritarė'
      case 'negative':
        return 'Visi studentų atstovai nepritarė'
      case 'neutral':
        return 'Visi studentų atstovai susilaikė'
      default:
        return 'Studentų atstovai dar nebalsavo'
    }
  }
  
  if (props.type === 'student_benefit') {
    switch (props.state) {
      case 'positive':
        return 'Sprendimas palankus studentams'
      case 'negative':
        return 'Sprendimas nepalankus studentams'
      case 'neutral':
        return 'Sprendimas neturi tiesioginės ar netiesioginės įtakos studentams / dar nėra aišku'
      default:
        return 'Poveikis studentams dar neįvertintas'
    }
  }
  
  return 'Nėra papildomos informacijos'
})
</script>