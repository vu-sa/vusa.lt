<template>
  <Card class="mb-6">
    <CardHeader class="pb-3">
      <CardTitle class="flex items-center gap-2">
        <component :is="Icons.CHECK_CIRCLE" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
        {{ $t('Aktyvios pažimos') }}
        <span v-if="totalCheckIns > maxDisplay" 
              class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">
          {{ displayedInstitutions.length }}/{{ totalCheckIns }}
        </span>
      </CardTitle>
      <p class="text-sm text-muted-foreground">
        {{ $t('Institucijos su aktyvėmis pažymomis ir artėjantys galiojimo pabaigos terminai') }}
      </p>
    </CardHeader>
    
    <CardContent v-if="displayedInstitutions.length > 0" class="space-y-3">
      <!-- Institution check-ins -->
      <div v-for="inst in displayedInstitutions" :key="inst.id" 
           class="flex items-center justify-between p-3 rounded-lg border"
           :class="getRowClasses(inst)">
        <div class="flex items-center gap-3">
          <div class="flex-shrink-0">
            <div class="w-2 h-2 rounded-full" :class="getStatusDot(inst)"></div>
          </div>
          <div class="min-w-0">
            <p class="font-medium text-sm truncate" :title="inst.name">{{ inst.name }}</p>
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
              <span v-if="inst.active_check_in">
                {{ getExpiryText(inst.active_check_in) }}
              </span>
              <div v-if="inst.active_check_in?.verifications?.length" class="flex items-center -space-x-2">
                <Avatar v-for="v in inst.active_check_in.verifications.slice(0,5)"
                        :key="v.id"
                        class="size-5 ring-2 ring-white dark:ring-zinc-900">
                  <img :src="v.user?.profile_photo_path || defaultAvatarUrl(v.user?.name)" :alt="v.user?.name" />
                </Avatar>
                <span v-if="inst.active_check_in.verifications.length > 5" class="text-xs ml-2">+{{ inst.active_check_in.verifications.length - 5 }}</span>
              </div>
            </div>
          </div>
        </div>
        
  <div class="flex items-center gap-1">
          <!-- Quick actions -->
          <Button v-if="inst.active_check_in && canConfirm(inst)" 
      size="xs" 
                  variant="ghost"
                  class="h-8 px-2"
                  @click="$emit('confirm', inst.active_check_in.id)">
            <component :is="Icons.CHECK" class="h-3 w-3" />
          </Button>
          
          <Button v-if="!inst.active_check_in" 
      size="xs" 
                  variant="ghost"
                  class="h-8 px-2"
                  @click="$emit('addCheckIn', inst.id)">
            <component :is="Icons.PLUS" class="h-3 w-3" />
          </Button>
          
    <Button size="xs" variant="default" class="h-8 px-2" @click="$emit('scheduleMeeting', inst.id)">
            <component :is="Icons.CALENDAR" class="h-3 w-3" />
          </Button>
        </div>
      </div>
      
      <!-- Show more button -->
      <Button v-if="totalCheckIns > maxDisplay" 
              variant="ghost" 
              size="sm" 
              class="w-full"
              @click="$emit('showMore')">
        {{ $t('Rodyti daugiau') }} ({{ totalCheckIns - maxDisplay }})
        <component :is="Icons.CHEVRON_DOWN" class="ml-2 h-4 w-4" />
      </Button>
    </CardContent>
    
    <!-- Empty state -->
    <CardContent v-else class="text-center py-6">
      <div class="text-4xl mb-2">📝</div>
      <p class="font-medium text-muted-foreground">{{ $t('Nėra aktyvių pažymų') }}</p>
      <p class="text-sm text-muted-foreground mt-1">
        {{ $t('Institucijos be pažymų ar artėjančių susitikimų') }}
      </p>
      <Button variant="outline" size="sm" class="mt-3" @click="$emit('addFirstCheckIn')">
        <component :is="Icons.PLUS" class="mr-2 h-4 w-4" />
        {{ $t('Pridėti pažymą') }}
      </Button>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card'
import { Button } from '@/Components/ui/button'
import Avatar from '@/Components/ui/avatar/Avatar.vue'
import Icons from '@/Types/Icons/filled'

const props = defineProps<{
  institutions?: any[]
  currentUserId?: string
  maxDisplay?: number
}>()

defineEmits<{
  confirm: [checkInId: string]
  addCheckIn: [institutionId: string]
  scheduleMeeting: [institutionId: string]
  showMore: []
  addFirstCheckIn: []
}>()

const maxDisplay = props.maxDisplay || 5

// Derive institutions from user duties if not provided
const derivedInstitutions = computed<any[]>(() => {
  if (props.institutions && Array.isArray(props.institutions)) return props.institutions
  const global = (window as any)?.VUSA?.currentUser
  const duties = global?.current_duties ?? []
  const insts = duties
    .map((d: any) => d?.institution)
    .filter((i: any) => !!i)
    .filter((institution: any, idx: number, self: any[]) => idx === self.findIndex((t: any) => t && institution && t.id === institution.id))
  return insts
})

// Get institutions sorted by priority (expiring soon, then no check-ins)
const sortedInstitutions = computed(() => {
  return [...derivedInstitutions.value].sort((a, b) => {
    // Priority 1: Has active check-in vs doesn't
    const aHasCheckIn = !!a.active_check_in
    const bHasCheckIn = !!b.active_check_in
    
    if (aHasCheckIn && !bHasCheckIn) return -1
    if (!aHasCheckIn && bHasCheckIn) return 1
    
    // Priority 2: If both have check-ins, sort by expiry date (soonest first)
    if (aHasCheckIn && bHasCheckIn) {
      const aDays = getDaysUntilExpiry(a.active_check_in)
      const bDays = getDaysUntilExpiry(b.active_check_in)
      return aDays - bDays
    }
    
    // Priority 3: Institutions without check-ins but with upcoming meetings
    const aHasMeetings = hasUpcomingMeetings(a)
    const bHasMeetings = hasUpcomingMeetings(b)
    
    if (aHasMeetings && !bHasMeetings) return -1
    if (!aHasMeetings && bHasMeetings) return 1
    
    // Priority 4: Alphabetical
    return a.name.localeCompare(b.name)
  })
})

const displayedInstitutions = computed(() => sortedInstitutions.value.slice(0, maxDisplay))

const totalCheckIns = computed(() => derivedInstitutions.value.filter(inst => inst.active_check_in || !hasUpcomingMeetings(inst)).length)

// Helper functions
const getDaysUntilExpiry = (checkIn: any) => {
  if (!checkIn?.until_date) return 999
  return Math.ceil((new Date(checkIn.until_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
}

const hasUpcomingMeetings = (institution: any) => {
  return Array.isArray(institution?.meetings) && 
         institution.meetings.some((meeting: any) => new Date(meeting.start_time) > new Date())
}

const getExpiryText = (checkIn: any) => {
  if (!checkIn) return ''
  
  const days = getDaysUntilExpiry(checkIn)
  const mode = checkIn.mode === 'heads_up' ? $t('Heads-up iki') : $t('Pažyma iki')
  const dateStr = new Date(checkIn.until_date).toLocaleDateString()
  
  if (days <= 0) return `${mode} ${dateStr} (${$t('pasibaigė')})`
  if (days <= 3) return `${mode} ${dateStr} (${days} ${$t('d.')})`
  
  return `${mode} ${dateStr}`
}

const getStatusDot = (institution: any) => {
  if (institution.active_check_in) {
    const days = getDaysUntilExpiry(institution.active_check_in)
    if (days <= 0) return 'bg-gray-400'
    if (days <= 3) return 'bg-amber-400'
    return 'bg-green-400'
  }
  
  return hasUpcomingMeetings(institution) ? 'bg-blue-400' : 'bg-red-400'
}

const getRowClasses = (institution: any) => {
  if (institution.active_check_in) {
    const days = getDaysUntilExpiry(institution.active_check_in)
    if (days <= 0) return 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-700'
    if (days <= 3) return 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-700'
    return 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-700'
  }
  
  return hasUpcomingMeetings(institution) 
    ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-700'
    : 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-700'
}

const hasVerified = (checkIn: any) => Array.isArray(checkIn?.verifications)
  && !!props.currentUserId
  && checkIn.verifications.some((v: any) => v.user_id === props.currentUserId)

const isAuthor = (checkIn: any) => !!props.currentUserId && checkIn?.user_id === props.currentUserId

const canConfirm = (institution: any) => {
  const ci = institution.active_check_in
  if (!ci) return false
  const active = ci.state === 'App\\States\\InstitutionCheckIns\\Active'
  return active && !hasVerified(ci) && !isAuthor(ci)
}

const defaultAvatarUrl = (name?: string) => {
  return '/images/default-avatar.png'
}
</script>