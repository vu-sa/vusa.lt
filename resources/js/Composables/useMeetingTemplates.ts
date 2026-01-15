import { ref, computed } from 'vue'

import type { AgendaItemFormData } from './useMeetingCreation'

export interface MeetingTemplate {
  id: string
  name: string
  description: string
  institutionId: string
  agendaItems: string[]
}

export interface RecentMeeting {
  id: string
  title: string
  start_time: string
  institution_id: string
  institution_name: string
  agenda_items: { title: string }[]
}

export function useMeetingTemplates(providedRecentMeetings?: RecentMeeting[]) {
  const recentMeetings = ref<RecentMeeting[]>(providedRecentMeetings || [])

  // Convert recent meetings to templates
  const templates = computed<MeetingTemplate[]>(() => {
    return recentMeetings.value.map(meeting => ({
      id: `recent-${meeting.id}`,
      name: meeting.title,
      description: meeting.institution_name,
      institutionId: meeting.institution_id,
      agendaItems: meeting.agenda_items.map(item => item.title),
    }))
  })

  // Get templates filtered by institution
  const getTemplatesForInstitution = (institutionId?: string): MeetingTemplate[] => {
    if (!institutionId) {
      return templates.value
    }

    // Use string comparison to avoid type mismatch issues
    const filtered = templates.value.filter(template => 
      String(template.institutionId) === String(institutionId)
    )
    
    // If no matches for this specific institution, return all templates
    // so users can still use templates from other institutions
    return filtered.length > 0 ? filtered : templates.value
  }

  // Apply template to form - returns agenda items
  const applyTemplate = (templateId: string): AgendaItemFormData[] => {
    const template = templates.value.find(t => t.id === templateId)
    if (!template) {
      console.warn(`Template ${templateId} not found`)
      return []
    }

    return template.agendaItems.map((title, index) => ({
      title,
      description: '',
      order: index + 1
    }))
  }

  return {
    // State
    templates,
    recentMeetings: computed(() => recentMeetings.value),

    // Methods
    getTemplatesForInstitution,
    applyTemplate,
  }
}
