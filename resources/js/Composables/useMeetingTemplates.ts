import { ref, computed, onMounted } from 'vue';
import { useFetch } from '@vueuse/core';

import type { AgendaItemFormData } from './useMeetingCreation';

export interface MeetingTemplate {
  id: string;
  name: string;
  description: string;
  institutionId: string;
  agendaItems: string[];
}

export interface RecentMeeting {
  id: string;
  title: string;
  start_time: string;
  institution_id: string;
  institution_name: string;
  agenda_items: { title: string }[];
}

interface RecentMeetingsApiResponse {
  success: boolean;
  data: RecentMeeting[];
}

/**
 * Composable for meeting templates based on recent meetings.
 * Fetches recent meetings from API endpoint.
 */
export function useMeetingTemplates(providedRecentMeetings?: RecentMeeting[]) {
  const recentMeetings = ref<RecentMeeting[]>(providedRecentMeetings || []);
  const isLoading = ref(false);

  // Fetch recent meetings from API if not provided
  const fetchRecentMeetings = async () => {
    if (providedRecentMeetings && providedRecentMeetings.length > 0) {
      return; // Already provided, no need to fetch
    }

    isLoading.value = true;
    try {
      const { data } = await useFetch(route('api.v1.admin.meetings.recent')).json<RecentMeetingsApiResponse>();
      if (data.value?.success && Array.isArray(data.value.data)) {
        recentMeetings.value = data.value.data;
      }
    }
    catch (error) {
      console.error('Failed to fetch recent meetings:', error);
    }
    finally {
      isLoading.value = false;
    }
  };

  // Auto-fetch on mount if not provided
  onMounted(() => {
    if (!providedRecentMeetings || providedRecentMeetings.length === 0) {
      fetchRecentMeetings();
    }
  });

  // Convert recent meetings to templates
  const templates = computed<MeetingTemplate[]>(() => {
    return recentMeetings.value.map(meeting => ({
      id: `recent-${meeting.id}`,
      name: meeting.title,
      description: meeting.institution_name,
      institutionId: meeting.institution_id,
      agendaItems: meeting.agenda_items.map(item => item.title),
    }));
  });

  // Get templates filtered by institution
  const getTemplatesForInstitution = (institutionId?: string): MeetingTemplate[] => {
    if (!institutionId) {
      return templates.value;
    }

    // Use string comparison to avoid type mismatch issues
    const filtered = templates.value.filter(template =>
      String(template.institutionId) === String(institutionId),
    );

    // If no matches for this specific institution, return all templates
    // so users can still use templates from other institutions
    return filtered.length > 0 ? filtered : templates.value;
  };

  // Apply template to form - returns agenda items
  const applyTemplate = (templateId: string): AgendaItemFormData[] => {
    const template = templates.value.find(t => t.id === templateId);
    if (!template) {
      console.warn(`Template ${templateId} not found`);
      return [];
    }

    return template.agendaItems.map((title, index) => ({
      title,
      description: '',
      order: index + 1,
    }));
  };

  return {
    // State
    templates,
    recentMeetings: computed(() => recentMeetings.value),
    isLoading: computed(() => isLoading.value),

    // Methods
    getTemplatesForInstitution,
    applyTemplate,
    fetchRecentMeetings,
  };
}
