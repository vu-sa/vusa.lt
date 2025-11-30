import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'

import type { AgendaItemFormData } from './useMeetingCreation'

export interface MeetingTemplate {
  id: string
  name: string
  description: string
  type: 'standard' | 'custom' | 'recent'
  institutionTypes?: string[]
  agendaItems: string[]
  suggestedDuration?: number // in minutes
  suggestedLocation?: string
  isDefault?: boolean
}

export interface RecentMeeting {
  id: string
  title: string
  start_time: string
  institution_name: string
  agenda_items: { title: string }[]
}

export function useMeetingTemplates(providedRecentMeetings?: RecentMeeting[]) {
  const page = usePage()
  const isLoading = ref(false)
  const templates = ref<MeetingTemplate[]>([])
  const recentMeetings = ref<RecentMeeting[]>(providedRecentMeetings || [])

  // Standard meeting templates
  const standardTemplates: MeetingTemplate[] = []

  // Initialize templates with recent meetings if provided
  if (providedRecentMeetings && providedRecentMeetings.length > 0) {
    const recentTemplates: MeetingTemplate[] = providedRecentMeetings
      .slice(0, 3) // Limit to 3 most recent
      .map(meeting => ({
        id: `recent-${meeting.id}`,
        name: `${$t('Kaip')} "${meeting.title}"`,
        description: `${$t('Naudoti')} ${meeting.institution_name} ${$t('posėdžio šabloną')}`,
        type: 'recent' as const,
        agendaItems: meeting.agenda_items.map(item => item.title),
        suggestedDuration: 60,
        isDefault: false
      }))

    templates.value = [...standardTemplates, ...recentTemplates]
  } else {
    templates.value = [...standardTemplates]
  }

  // Get templates filtered by institution type or context
  const getTemplatesForInstitution = (institution?: App.Entities.Institution): MeetingTemplate[] => {
    if (!institution) {
      return templates.value.filter(t => t.isDefault)
    }

    // Filter by institution type if available
    const institutionType = institution.types?.[0]?.slug || ''

    const relevantTemplates = templates.value.filter(template => {
      if (template.type === 'recent') return true
      if (!template.institutionTypes) return true

      return template.institutionTypes.some(type =>
        institutionType.toLowerCase().includes(type.toLowerCase())
      )
    })

    // Always include default templates
    const defaultTemplates = templates.value.filter(t =>
      t.isDefault && !relevantTemplates.find(rt => rt.id === t.id)
    )

    return [...relevantTemplates, ...defaultTemplates]
  }

  // Load recent meetings for templates
  const loadRecentMeetings = async (institutionId?: string) => {
    if (isLoading.value) return

    isLoading.value = true
    try {
      // Filter provided meetings by institution if specified
      let meetings = recentMeetings.value
      if (institutionId && meetings.length > 0) {
        // This would filter by institution, but since we don't have institution_id in RecentMeeting type,
        // we'll use the meetings as-is for now
        meetings = meetings
      }

      // Create templates from recent meetings
      const recentTemplates: MeetingTemplate[] = meetings
        .slice(0, 3) // Limit to 3 most recent
        .map(meeting => ({
          id: `recent-${meeting.id}`,
          name: `${$t('Kaip')} "${meeting.title}"`,
          description: `${$t('Naudoti')} ${meeting.institution_name} ${$t('posėdžio šabloną')}`,
          type: 'recent' as const,
          agendaItems: meeting.agenda_items.map(item => item.title),
          suggestedDuration: 60,
          isDefault: false
        }))

      // Add recent templates to the list
      templates.value = [
        ...standardTemplates,
        ...recentTemplates
      ]

    } catch (error) {
      console.warn('Failed to process recent meetings:', error)
      // Fallback to standard templates only
      templates.value = [...standardTemplates]
    } finally {
      isLoading.value = false
    }
  }

  // Apply template to form
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

  // Create custom template from current form data
  const createCustomTemplate = (
    name: string,
    description: string,
    agendaItems: string[],
    institutionId?: string
  ): MeetingTemplate => {
    const customTemplate: MeetingTemplate = {
      id: `custom-${Date.now()}`,
      name,
      description,
      type: 'custom',
      agendaItems,
      suggestedDuration: 60,
      isDefault: false
    }

    templates.value.push(customTemplate)

    // TODO: Save to backend if needed
    // saveCustomTemplate(customTemplate, institutionId)

    return customTemplate
  }

  // Smart agenda suggestions based on context
  const getSuggestedAgendaItems = (
    institution?: App.Entities.Institution,
    meetingType?: string
  ): string[] => {
    // Base suggestions
    let suggestions: string[] = []

    // Add type-specific suggestions
    if (meetingType) {
      switch (meetingType.toLowerCase()) {
        case 'valdyba':
        case 'board':
          suggestions = [
            $t('Praėjusio posėdžio protokolo tvirtinimas'),
            $t('Finansų ataskaita'),
            $t('Einamųjų projektų aptarimas'),
            $t('Naujų iniciatyvų pristatymas')
          ]
          break
        case 'komisija':
        case 'committee':
          suggestions = [
            $t('Veiklų ataskaitos'),
            $t('Sprendimų priėmimas'),
            $t('Ekspertų nuomonės'),
            $t('Rekomendacijų rengimas')
          ]
          break
        default:
          suggestions = [
            $t('Darbotvarkės pristatymas'),
            $t('Svarstytini klausimai'),
            $t('Sprendimų priėmimas')
          ]
      }
    }

    // Add institution-specific suggestions if available
    if (institution && institution.types) {
      const institutionType = institution.types[0]?.slug

      // Add context-specific items based on institution type
      if (institutionType?.includes('student')) {
        suggestions.push(
          $t('Studentų atstovas reikalai'),
          $t('Akademinių klausimų aptarimas'),
          $t('Studentų gerovės klausimai')
        )
      }
    }

    // Always add common ending items
    suggestions.push($t('Kiti klausimai'))

    return [...new Set(suggestions)] // Remove duplicates
  }

  // Get meeting duration suggestion based on agenda
  const suggestMeetingDuration = (agendaItemsCount: number, templateId?: string): number => {
    if (templateId) {
      const template = templates.value.find(t => t.id === templateId)
      if (template?.suggestedDuration) {
        return template.suggestedDuration
      }
    }

    // Calculate duration based on agenda items
    const baseDuration = 30 // 30 minutes base
    const itemDuration = 15 // 15 minutes per agenda item

    return Math.min(
      baseDuration + (agendaItemsCount * itemDuration),
      180 // Maximum 3 hours
    )
  }

  // Get quick agenda templates for common scenarios
  const quickAgendaTemplates = computed(() => [
    {
      id: 'quick-info',
      name: $t('Informacinis'),
      items: [
        $t('Informacijos pristatymas'),
        $t('Klausimų aptarimas')
      ]
    },
    {
      id: 'quick-decision',
      name: $t('Sprendimų priėmimas'),
      items: [
        $t('Situacijos aptarimas'),
        $t('Variantų svarstimas'),
        $t('Sprendimo priėmimas')
      ]
    },
    {
      id: 'quick-planning',
      name: $t('Planavimas'),
      items: [
        $t('Tikslų nustatymas'),
        $t('Veiksmų planavimas'),
        $t('Atsakomybių paskirstymas')
      ]
    }
  ])

  return {
    // State
    templates: computed(() => templates.value),
    recentMeetings: computed(() => recentMeetings.value),
    isLoading: computed(() => isLoading.value),
    quickAgendaTemplates,

    // Methods
    getTemplatesForInstitution,
    loadRecentMeetings,
    applyTemplate,
    createCustomTemplate,
    getSuggestedAgendaItems,
    suggestMeetingDuration
  }
}
