<template>
  <div class="flex flex-col gap-6">
    <!-- Review Header -->
    <div class="text-center space-y-3 mb-6">
      <div class="mx-auto w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center">
        <CheckCircle class="h-7 w-7 text-primary" />
      </div>
      <h2 class="text-xl font-semibold">{{ $t('Paruošta sukurti susitikimą') }}</h2>
      <p class="text-sm text-muted-foreground max-w-md mx-auto">
        {{ $t('Patikrinkite duomenis ir spauskite "Sukurti", jei viskas gerai') }}
      </p>
    </div>

    <!-- Meeting Summary Card -->
    <Card>
      <CardContent class="pt-6">
        <div class="space-y-5">
          <!-- Institution -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <component :is="Icons.INSTITUTION" class="h-5 w-5 text-muted-foreground" />
              <div>
                <p class="font-medium">{{ selectedInstitution?.name }}</p>
                <!-- <p class="text-sm text-muted-foreground">{{ selectedInstitution?.type || $t('Institucija') }}</p> -->
              </div>
            </div>
            <Button type="button" variant="ghost" size="sm" @click="$emit('editStep', 1)">
              <Edit class="h-4 w-4" />
            </Button>
          </div>

          <Separator />

          <!-- Date & Time -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <Calendar class="h-5 w-5 text-muted-foreground" />
              <div>
                <p class="font-medium">{{ formatDate(meetingData.start_time) }}</p>
                <div class="flex items-center gap-2">
                  <span class="text-sm text-muted-foreground">{{ formatTime(meetingData.start_time) }}</span>
                  <Badge v-if="isWeekend(meetingData.start_time)" variant="secondary" class="text-xs">
                    {{ $t('Savaitgalis') }}
                  </Badge>
                </div>
              </div>
            </div>
            <Button type="button" variant="ghost" size="sm" @click="$emit('editStep', 2)">
              <Edit class="h-4 w-4" />
            </Button>
          </div>

          <!-- Meeting Type -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <component :is="Icons.TYPE" class="h-5 w-5 text-muted-foreground" />
              <div>
                <p class="font-medium">{{ meetingType?.title || $t('Posėdžio tipas nenurodytas') }}</p>
                <p class="text-sm text-muted-foreground">{{ $t('Posėdžio tipas') }}</p>
              </div>
            </div>
            <Button type="button" variant="ghost" size="sm" @click="$emit('editStep', 2)">
              <Edit class="h-4 w-4" />
            </Button>
          </div>

          <!-- Optional Details -->
          <div v-if="meetingData.description || meetingData.location">
            <Separator />
            <div class="space-y-3 pt-3">
              <div v-if="meetingData.description" class="flex items-start gap-3">
                <FileText class="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div class="flex-1">
                  <p class="text-sm font-medium">{{ $t('Aprašymas') }}</p>
                  <p class="text-sm text-muted-foreground">{{ meetingData.description }}</p>
                </div>
              </div>
              
              <div v-if="meetingData.location" class="flex items-start gap-3">
                <MapPin class="h-5 w-5 mt-0.5 text-muted-foreground" />
                <div class="flex-1">
                  <p class="text-sm font-medium">{{ $t('Vieta') }}</p>
                  <p class="text-sm text-muted-foreground">{{ meetingData.location }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Agenda Items Card -->
    <Card v-if="agendaItems.length > 0">
      <CardContent class="pt-6">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-2">
            <component :is="Icons.AGENDA_ITEM" class="h-5 w-5 text-muted-foreground" />
            <span class="font-medium">{{ $t('Darbotvarkė') }}</span>
            <Badge variant="outline" class="text-xs ml-2">
              {{ agendaItems.length }}
            </Badge>
          </div>
          <Button type="button" variant="ghost" size="sm" @click="$emit('editStep', 3)">
            <Edit class="h-4 w-4" />
          </Button>
        </div>
        
        <div class="space-y-2">
          <div
            v-for="(item, index) in displayedAgendaItems"
            :key="index"
            class="flex items-center gap-3 text-sm"
          >
            <div class="flex-shrink-0 w-5 h-5 bg-muted rounded-full flex items-center justify-center">
              <span class="text-xs font-medium">{{ index + 1 }}</span>
            </div>
            <span>{{ item.title }}</span>
          </div>
          
          <Button
            v-if="agendaItems.length > maxDisplayedItems"
            type="button"
            variant="ghost"
            size="sm"
            class="w-full mt-3"
            @click="showAllAgendaItems = !showAllAgendaItems"
          >
            {{ showAllAgendaItems 
              ? $t('Rodyti mažiau') 
              : $t('Dar') + ' ' + (agendaItems.length - maxDisplayedItems) + ' ' + $t('klausimų') 
            }}
            <ChevronDown class="ml-2 h-3 w-3" :class="{ 'rotate-180': showAllAgendaItems }" />
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-4 border-t">
      <Button type="button" variant="outline" @click="$emit('back')">
        <ArrowLeft class="mr-2 h-4 w-4" />
        {{ $t('Atgal') }}
      </Button>
      
  <div class="flex items-center gap-3">
        <Button @click="handleSubmit" :disabled="loading">
          <span v-if="loading" class="flex items-center">
            <Loader2 class="mr-2 h-4 w-4 animate-spin" />
            {{ $t('Kuriamas susitikimas...') }}
          </span>
          <span v-else class="flex items-center">
            {{ $t('Sukurti susitikimą') }}
            <Rocket class="ml-2 h-4 w-4" />
          </span>
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { usePage } from '@inertiajs/vue3'

import Icons from '@/Types/Icons/filled'
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import { Separator } from '@/Components/ui/separator'

// Import Lucide icons
import {
  CheckCircle,
  Edit,
  Calendar,
  AlertTriangle,
  FileText,
  MapPin,
  ChevronDown,
  Lightbulb,
  ArrowLeft,
  Clock,
  Loader2,
  Rocket,
} from 'lucide-vue-next'

const emit = defineEmits<{
  editStep: [step: number]
  back: []
  submit: []
}>()

const props = defineProps<{
  loading?: boolean
  meetingState?: any
}>()

// Local state
const showAllAgendaItems = ref(false)
const maxDisplayedItems = 5

// Computed properties
const selectedInstitution = computed(() => {
  return props.meetingState?.institution || null
})

const meetingData = computed(() => props.meetingState?.meeting || {})

const meetingType = computed(() => {
  const typeId = meetingData.value.type_id;
  if (!typeId) return null;
  
  // Try to get type name from page props (if available)
  const meetingTypes = (usePage().props as any)?.meetingTypes || [];
  const type = meetingTypes.find((t: any) => t.id === typeId);

  console.log('Meeting type:', typeId, type, meetingTypes);
  
  if (type) {
    return { id: type.id, title: type.title };
  }
  
  // Fallback to default
  return { id: typeId, title: $t('Standartinis posėdis') };
})

const agendaItems = computed(() => {
  return props.meetingState?.agendaItems || []
})

const displayedAgendaItems = computed(() => {
  if (showAllAgendaItems.value || agendaItems.value.length <= maxDisplayedItems) {
    return agendaItems.value
  }
  return agendaItems.value.slice(0, maxDisplayedItems)
})

const estimatedDuration = computed(() => {
  // Basic calculation: 30 min base + 15 min per agenda item
  const baseTime = 30
  const itemTime = agendaItems.value.length * 15
  return baseTime + itemTime
})

const insights = computed(() => {
  const suggestions: string[] = []
  
  // Time-based insights
  if (meetingData.value.start_time) {
    const date = new Date(meetingData.value.start_time)
    const hour = date.getHours()
    
    if (hour < 9) {
      suggestions.push($t('Ankstus susitikimo laikas - patikrinkite, ar visi dalyviai galės dalyvauti'))
    }
    
    if (hour > 18) {
      suggestions.push($t('Vėlyvas susitikimo laikas - apsvarstykite ankstesnį laiką'))
    }
    
    if (isWeekend(meetingData.value.start_time)) {
      suggestions.push($t('Susitikimas savaitgalį - patvirtinkite dalyvių galimybes'))
    }
  }
  
  // Agenda-based insights
  if (agendaItems.value.length === 0) {
    suggestions.push($t('Susitikimas be darbotvarkės - apsvarstykite pagrindinių klausimų pridėjimą'))
  } else if (agendaItems.value.length > 10) {
    suggestions.push($t('Daug darbotvarkės klausimų - apsvarstykite susitikimo padalijimą'))
  }
  
  // Duration insights
  if (estimatedDuration.value > 120) {
    suggestions.push($t('Ilgas susitikimas - planuokite pertraukėles'))
  }
  
  return suggestions
})

// Utility methods
const formatDate = (dateString: string): string => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString(undefined, { 
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (dateString: string): string => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleTimeString(undefined, { 
    hour: 'numeric',
    minute: '2-digit'
  })
}

const isWeekend = (dateString: string): boolean => {
  if (!dateString) return false
  const date = new Date(dateString)
  const day = date.getDay()
  return day === 0 || day === 6
}

const handleSubmit = () => {
  emit('submit')
}
</script>
