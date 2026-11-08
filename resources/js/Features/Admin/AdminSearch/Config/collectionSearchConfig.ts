import { buildProfileParams } from '@/Shared/Search/profiles';
import type { AdminSearchConfig } from '@/Shared/Search/types';

import { buildInfix } from '../Utils/searchParams';

interface CollectionSearchFallback {
  queryBy: string;
  weights: string;
  defaultSort: string;
  numTypos?: string;
}

export const ADMIN_SEARCH_FALLBACKS: Record<string, CollectionSearchFallback> = {
  meetings: { queryBy: 'title,description,institution_name_lt,institution_name_en,institution_names,tenant_shortnames', weights: '10,5,4,4,3,2', defaultSort: 'start_time:desc' },
  agenda_items: { queryBy: 'title,description,student_benefit,meeting_title', weights: '10,5,3,4', defaultSort: 'meeting_start_time:desc' },
  news: { queryBy: 'title,short', weights: '10,5', defaultSort: 'publish_time:desc' },
  pages: { queryBy: 'title,meta_description', weights: '10,4', defaultSort: 'created_at:desc' },
  calendar: { queryBy: 'title,title_lt,title_en', weights: '10,8,8', defaultSort: 'date:desc' },
  institutions: { queryBy: 'name_lt,name_en,short_name_lt,short_name_en,alias,email', weights: '10,8,6,4,3,2', defaultSort: 'created_at:desc' },
  resources: { queryBy: 'name_lt,name_en,description_lt,description_en,location', weights: '10,8,4,4,3', defaultSort: 'created_at:desc', numTypos: '2,2,2,2,0' },
  duties: { queryBy: 'name_lt,name_en,email,former_names,institution_name_lt,institution_name_en,current_user_names,previous_user_names', weights: '10,8,4,4,4,3,3,2', defaultSort: 'name_lt:asc' },
  users: { queryBy: 'name,email,phone,current_duty_names,previous_duty_names', weights: '10,6,4,3,2', defaultSort: 'created_at:desc' },
  documents: { queryBy: 'title,summary,content_type,document_year', weights: '10,5,3,2', defaultSort: 'document_date:desc' },
};

export function buildAdminSearchParams(collection: string, config: AdminSearchConfig | null, input: Record<string, unknown>): Record<string, unknown> {
  const profile = config?.searchProfiles?.[collection];
  if (profile) return buildProfileParams(profile, input, config?.searchLocale);

  const fallback = ADMIN_SEARCH_FALLBACKS[collection];
  if (!fallback) return { ...input };
  const queryBy = String(input.query_by || fallback.queryBy);
  const query = String(input.q ?? '*').trim();
  const infix = buildInfix(queryBy, collection);
  return {
    query_by: queryBy,
    ...(queryBy === fallback.queryBy ? { query_by_weights: fallback.weights, num_typos: fallback.numTypos ?? 2 } : {}),
    ...(infix ? { infix } : {}),
    sort_by: query && query !== '*' ? `_text_match:desc,${fallback.defaultSort}` : fallback.defaultSort,
    prioritize_exact_match: true,
    prioritize_token_position: true,
    typo_tokens_threshold: 1,
    ...input,
  };
}
