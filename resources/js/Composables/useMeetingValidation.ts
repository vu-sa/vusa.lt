import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { toTypedSchema } from '@vee-validate/zod'
import * as z from 'zod'
import type { MeetingFormData, AgendaItemFormData } from './useMeetingCreation'

export interface ValidationRules {
  institution: z.ZodSchema
  meeting: z.ZodSchema
  agendaItems: z.ZodSchema
}

export interface ConflictCheckOptions {
  institutionId: string
  startTime: string
  excludeMeetingId?: string
}

export interface ScheduleConflict {
  id: string
  title: string
  start_time: string
  institution_name: string
  type: 'meeting' | 'event' | 'reservation'
}

export function useMeetingValidation() {
  
  // Zod validation schemas
  const institutionSchema = toTypedSchema(
    z.object({
      institution_id: z.string({
        required_error: $t('Institucija yra privaloma'),
      }).min(1, $t('Pasirinkite instituciją'))
    })
  )

  const meetingSchema = toTypedSchema(
    z.object({
      start_time: z.string({
        required_error: $t('Data ir laikas yra privalomi'),
      }).min(1, $t('Įveskite susitikimo datą ir laiką')),
      
      type_id: z.number({
        required_error: $t('Posėdžio tipas yra privalomas'),
      }).min(1, $t('Pasirinkite posėdžio tipą')),
      
      description: z.string().optional(),
      
      location: z.string().optional(),
    })
  )

  const agendaItemsSchema = toTypedSchema(
    z.object({
      agendaItemTitles: z.array(
        z.string().min(1, $t('Klausimas negali būti tuščias'))
      ).optional().default([])
      .refine(items => {
        // All items must be non-empty if provided
        return !items || items.every(item => item && item.trim().length > 0)
      }, { 
        message: $t('Klausimas negali būti tuščias') 
      })
    })
  )

  // Validation rules object
  const validationRules: ValidationRules = {
    institution: institutionSchema,
    meeting: meetingSchema,
    agendaItems: agendaItemsSchema
  }

  // Business logic validation
  const validateMeetingTime = (startTime: string): { isValid: boolean; errors: string[] } => {
    const errors: string[] = []
    const meetingDate = new Date(startTime)
    const now = new Date()

    // Check if date is in the past
    if (meetingDate <= now) {
      errors.push($t('Susitikimo laikas negali būti praeityje'))
    }

    // Check if it's too far in the future (e.g., more than 1 year)
    const oneYearFromNow = new Date()
    oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1)
    
    if (meetingDate > oneYearFromNow) {
      errors.push($t('Susitikimo laikas negali būti daugiau nei metai į priekį'))
    }

    // Check if it's during reasonable hours (7 AM - 10 PM)
    const hour = meetingDate.getHours()
    if (hour < 7 || hour > 22) {
      errors.push($t('Rekomenduojama planuoti susitikimus 7:00-22:00 laiku'))
    }

    // Check if it's on weekend (warning, not error)
    const dayOfWeek = meetingDate.getDay()
    if (dayOfWeek === 0 || dayOfWeek === 6) {
      // This is just a warning, not blocking validation
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }

  const validateAgendaItems = (items: AgendaItemFormData[]): { isValid: boolean; errors: string[] } => {
    const errors: string[] = []

    // Check for empty titles
    const hasEmptyTitles = items.some(item => !item.title || item.title.trim().length === 0)
    if (hasEmptyTitles) {
      errors.push($t('Visi darbotvarkės klausimai turi turėti pavadinimus'))
    }

    // Check for duplicate titles
    const titles = items.map(item => item.title.trim().toLowerCase()).filter(Boolean)
    const uniqueTitles = new Set(titles)
    if (titles.length !== uniqueTitles.size) {
      errors.push($t('Darbotvarkės klausimai negali kartotis'))
    }

    // Check reasonable length limits
    const hasLongTitles = items.some(item => item.title && item.title.length > 200)
    if (hasLongTitles) {
      errors.push($t('Darbotvarkės klausimo pavadinimas negali viršyti 200 simbolių'))
    }

    // Check for too many items
    if (items.length > 50) {
      errors.push($t('Per daug darbotvarkės klausimų (maksimalus kiekis: 50)'))
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }

  // Schedule conflict detection removed: always return no conflicts
  const checkScheduleConflicts = async (_options: ConflictCheckOptions): Promise<ScheduleConflict[]> => {
    return []
  }

  // Suggestion generators
  const suggestMeetingTimes = (institutionId: string, preferredTime?: string): string[] => {
    const suggestions: string[] = []
    const now = new Date()
    
    // Default to next business day at 10 AM if no preferred time
    const baseDate = preferredTime ? new Date(preferredTime) : new Date()
    if (!preferredTime) {
      baseDate.setDate(baseDate.getDate() + 1)
      baseDate.setHours(10, 0, 0, 0)
      
      // Skip weekends
      while (baseDate.getDay() === 0 || baseDate.getDay() === 6) {
        baseDate.setDate(baseDate.getDate() + 1)
      }
    }

    // Generate suggestions around the base time
    const times = [10, 14, 16] // 10 AM, 2 PM, 4 PM
    
    for (let dayOffset = 0; dayOffset < 7; dayOffset++) {
      for (const hour of times) {
        const suggestionDate = new Date(baseDate)
        suggestionDate.setDate(suggestionDate.getDate() + dayOffset)
        suggestionDate.setHours(hour, 0, 0, 0)
        
        // Skip weekends and past dates
        if (suggestionDate.getDay() !== 0 && 
            suggestionDate.getDay() !== 6 && 
            suggestionDate > now) {
          suggestions.push(suggestionDate.toISOString())
        }
        
        if (suggestions.length >= 6) break
      }
      if (suggestions.length >= 6) break
    }

    return suggestions
  }

  const getValidationMessage = (field: string, value: any): string | null => {
    switch (field) {
      case 'institution_id':
        if (!value) return $t('Pasirinkite instituciją')
        break
      
      case 'start_time':
        if (!value) return $t('Įveskite susitikimo datą ir laiką')
        const timeValidation = validateMeetingTime(value)
        return timeValidation.errors[0] || null
      
      case 'type_id':
        if (!value || value === 0) return $t('Pasirinkite posėdžio tipą')
        break
      
      case 'agendaItems':
        if (Array.isArray(value)) {
          const agendaValidation = validateAgendaItems(value.map(title => ({ title, order: 0 })))
          return agendaValidation.errors[0] || null
        }
        break
    }
    
    return null
  }

  // Utility functions for form validation
  const isValidDateTime = (dateTime: string): boolean => {
    const date = new Date(dateTime)
    return !isNaN(date.getTime()) && date > new Date()
  }

  const isBusinessHour = (dateTime: string): boolean => {
    const date = new Date(dateTime)
    const hour = date.getHours()
    const day = date.getDay()
    
    return day >= 1 && day <= 5 && hour >= 8 && hour <= 18
  }

  const isWeekend = (dateTime: string): boolean => {
    const date = new Date(dateTime)
    const day = date.getDay()
    return day === 0 || day === 6
  }

  // Format validation errors for display
  const formatValidationErrors = (errors: Record<string, string[]>): string[] => {
    const formattedErrors: string[] = []
    
    Object.entries(errors).forEach(([field, fieldErrors]) => {
      fieldErrors.forEach(error => {
        formattedErrors.push(error)
      })
    })
    
    return formattedErrors
  }

  return {
    // Schemas
    validationRules,
    institutionSchema,
    meetingSchema,
    agendaItemsSchema,
    
    // Validation functions
    validateMeetingTime,
    validateAgendaItems,
    checkScheduleConflicts,
    getValidationMessage,
    
    // Utility functions
    isValidDateTime,
    isBusinessHour,
    isWeekend,
    formatValidationErrors,
    
    // Suggestions
    suggestMeetingTimes
  }
}