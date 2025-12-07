<template>
  <div class="space-y-6">
    <!-- Hero Card -->
    <Card
      class="relative overflow-hidden border-0 shadow-sm bg-gradient-to-br from-white to-zinc-50 dark:from-zinc-900 dark:to-zinc-800">
      <CardContent class="p-6">
        <!-- Header Row -->
        <div class="flex items-start justify-between mb-4">
          <div class="space-y-2">
            <!-- Meeting type badge and institution -->
            <div class="flex items-center gap-3">
              <Badge variant="secondary" class="gap-1">
                <Video class="h-3 w-3" />
                {{ $t('Posėdis') }}
              </Badge>
              <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <component :is="getInstitutionIcon()" class="h-4 w-4" />
                <template v-if="typeof mainInstitution === 'string'">
                  <span>{{ mainInstitution }}</span>
                </template>
                <template v-else>
                  <Link :href="route('institutions.show', mainInstitution.id)" class="hover:text-zinc-900 dark:hover:text-zinc-100 hover:underline transition-colors">
                    {{ mainInstitution.name }}
                  </Link>
                </template>
              </div>
            </div>

            <h1 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-zinc-100 leading-tight">
              {{ meetingTitle }}
            </h1>

            <!-- Representatives -->
            <div v-if="representatives && representatives.length > 0" class="flex items-center gap-2 pt-1">
              <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('Studentų atstovai') }}:</span>
              <UsersAvatarGroup :users="representatives" :max="5" :size="28" />
            </div>
            <div v-else-if="representatives" class="flex items-center gap-2 pt-1">
              <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">{{ $t('Atstovai nežinomi') }}</span>
            </div>
          </div>

          <!-- Actions -->
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button variant="outline" size="icon" class="h-10 w-10">
                <MoreHorizontal class="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem @click="$emit('edit')">
                <Edit class="h-4 w-4 mr-2" />
                {{ $t('Redaguoti posėdį') }}
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem class="text-destructive focus:text-destructive" @click="$emit('showDeleteDialog')">
                <Trash2 class="h-4 w-4 mr-2" />
                {{ $t('Šalinti posėdį') }}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>

        <!-- Meeting Info Row -->
        <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4">
          <!-- Date and Time -->
          <div class="flex items-center gap-4 text-sm">
            <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
              <Calendar class="h-4 w-4 text-blue-500" />
              <span class="font-medium">
                {{ formatStaticTime(new Date(meeting.start_time), {
                  year: "numeric",
                  month: "long",
                  day: "numeric"
                }) }}
              </span>
            </div>

            <div class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
              <Clock class="h-4 w-4 text-green-500" />
              <time class="font-medium">
                {{ formatStaticTime(new Date(meeting.start_time), {
                  hour: "2-digit",
                  minute: "2-digit"
                }) }}
              </time>
            </div>
          </div>

          <!-- Meeting Types -->
          <div v-if="meeting.types && meeting.types.length > 0" class="flex flex-wrap gap-2">
            <Badge v-for="type in meeting.types" :key="type.id" variant="secondary" class="text-xs">
              {{ type.title }}
            </Badge>
          </div>

          <!-- Public Status Badge -->
          <Badge v-if="meeting.is_public" variant="outline" class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700">
            <Globe class="h-3 w-3" />
            {{ $t('Rodomas viešai') }}
          </Badge>
        </div>

      </CardContent>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import {
  Building,
  Calendar,
  Clock,
  MoreHorizontal,
  Edit,
  Trash2,
  GraduationCap,
  Users,
  Globe,
  Video
} from 'lucide-vue-next'

import { formatStaticTime } from '@/Utils/IntlTime'
import { genitivizeEveryWord } from '@/Utils/String'

// UI Components
import { Card, CardContent } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import { Badge } from '@/Components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/Components/ui/dropdown-menu'
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue'

interface Props {
  meeting: App.Entities.Meeting
  mainInstitution: App.Entities.Institution | string
  representatives?: App.Entities.User[]
  agendaItems: App.Entities.AgendaItem[]
}

interface Emits {
  (e: 'edit'): void
  (e: 'showDeleteDialog'): void
}

const props = defineProps<Props>()
defineEmits<Emits>()

const meetingTitle = computed(() => {
  if (props.meeting.title && props.meeting.title !== "") {
    return props.meeting.title
  }

  const institutionName = typeof props.mainInstitution === 'string'
    ? props.mainInstitution
    : props.mainInstitution.name

  return `${formatStaticTime(new Date(props.meeting.start_time), {
    year: "numeric",
    month: "long",
    day: "numeric",
  })} ${genitivizeEveryWord(institutionName)} posėdis`
})

const agendaItemsCount = computed(() => props.agendaItems.length)

const completedItemsCount = computed(() => {
  return props.agendaItems.filter(item =>
    item.decision === 'positive' ||
    item.decision === 'negative' ||
    item.decision === 'neutral'
  ).length
})

const progressPercentage = computed(() => {
  if (agendaItemsCount.value === 0) return 0
  return (completedItemsCount.value / agendaItemsCount.value) * 100
})

const getInstitutionIcon = () => {
  if (typeof props.mainInstitution === 'string') {
    return Building
  }

  // You can add logic here to return different icons based on institution type
  const institutionName = props.mainInstitution.name?.toLowerCase() || ''

  if (institutionName.includes('fakultet') || institutionName.includes('institu')) {
    return GraduationCap
  }

  if (institutionName.includes('studen') || institutionName.includes('atstov')) {
    return Users
  }

  return Building
}
</script>
