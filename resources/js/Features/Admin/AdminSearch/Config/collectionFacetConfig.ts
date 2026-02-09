/**
 * Collection Facet Configuration
 *
 * Defines facet fields, UI types, and search parameters for each admin collection.
 * These configurations drive the facet sidebar rendering and search API calls.
 */

import type { CollectionFacetConfig, SortOption } from '../Types/AdminSearchTypes';

/**
 * Meeting collection facet configuration
 */
export const MEETING_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'year,completion_status,vote_alignment_status,institution_type_title,tenant_shortnames',
  queryBy: 'title,description,institution_name_lt,institution_name_en,institution_names,tenant_shortnames',
  defaultSortBy: 'start_time:desc',
  fields: [
    {
      field: 'year',
      label: 'Metai',
      type: 'year-pills',
      icon: 'Calendar',
      defaultOpen: true,
      sortBy: 'alpha', // Years sorted descending
    },
    {
      field: 'completion_status',
      label: 'Būsena',
      type: 'checkbox',
      icon: 'CheckCircle',
      defaultOpen: true,
      sortBy: 'count',
    },
    {
      field: 'vote_alignment_status',
      label: 'Balsavimo atitikimas',
      type: 'checkbox',
      icon: 'Vote',
      defaultOpen: false,
      sortBy: 'count',
    },
    {
      field: 'institution_type_title',
      label: 'Institucijos tipas',
      type: 'checkbox',
      icon: 'Building2',
      defaultOpen: false,
      maxValues: 10,
      sortBy: 'count',
    },
    {
      field: 'tenant_shortnames',
      label: 'Padalinys',
      type: 'checkbox',
      icon: 'Users',
      defaultOpen: false,
      maxValues: 15,
      sortBy: 'count',
    },
  ],
};

/**
 * Agenda items collection facet configuration
 */
export const AGENDA_ITEM_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'meeting_year,student_vote,decision,is_complete,brought_by_students,vote_alignment_status,tenant_shortnames',
  queryBy: 'title,description,student_benefit,meeting_title',
  defaultSortBy: 'meeting_start_time:desc',
  fields: [
    {
      field: 'meeting_year',
      label: 'Posėdžio metai',
      type: 'year-pills',
      icon: 'Calendar',
      defaultOpen: true,
      sortBy: 'alpha',
    },
    {
      field: 'is_complete',
      label: 'Užpildymo būsena',
      type: 'checkbox',
      icon: 'CheckCircle',
      defaultOpen: true,
      sortBy: 'count',
    },
    {
      field: 'student_vote',
      label: 'Studentų balsas',
      type: 'checkbox',
      icon: 'ThumbsUp',
      defaultOpen: false,
      sortBy: 'count',
    },
    {
      field: 'decision',
      label: 'Sprendimas',
      type: 'checkbox',
      icon: 'Gavel',
      defaultOpen: false,
      sortBy: 'count',
    },
    {
      field: 'brought_by_students',
      label: 'Pateikė studentai',
      type: 'checkbox',
      icon: 'UserCheck',
      defaultOpen: false,
      sortBy: 'count',
    },
    {
      field: 'vote_alignment_status',
      label: 'Balsavimo atitikimas',
      type: 'checkbox',
      icon: 'Vote',
      defaultOpen: false,
      sortBy: 'count',
    },
    {
      field: 'tenant_shortnames',
      label: 'Padalinys',
      type: 'checkbox',
      icon: 'Users',
      defaultOpen: false,
      maxValues: 15,
      sortBy: 'count',
    },
  ],
};

/**
 * News collection facet configuration (for future use)
 */
export const NEWS_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'lang,tenant_shortname',
  queryBy: 'title,short',
  defaultSortBy: 'publish_time:desc',
  fields: [
    {
      field: 'lang',
      label: 'Kalba',
      type: 'checkbox',
      icon: 'Globe',
      defaultOpen: true,
      sortBy: 'count',
    },
    {
      field: 'tenant_shortname',
      label: 'Padalinys',
      type: 'checkbox',
      icon: 'Users',
      defaultOpen: false,
      maxValues: 15,
      sortBy: 'count',
    },
  ],
};

/**
 * Institution collection facet configuration (for future use)
 */
export const INSTITUTION_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'tenant_shortname',
  queryBy: 'name_lt,name_en,short_name_lt,short_name_en,alias,email',
  defaultSortBy: 'created_at:desc',
  fields: [
    {
      field: 'tenant_shortname',
      label: 'Padalinys',
      type: 'checkbox',
      icon: 'Users',
      defaultOpen: true,
      maxValues: 15,
      sortBy: 'count',
    },
  ],
};

/**
 * Resource collection facet configuration
 */
export const RESOURCE_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'category_name,tenant_shortname,is_reservable',
  queryBy: 'name_lt,name_en,description_lt,description_en,location',
  defaultSortBy: 'created_at:desc',
  fields: [
    {
      field: 'category_name',
      label: 'Kategorija',
      type: 'checkbox',
      icon: 'Tag',
      defaultOpen: true,
      sortBy: 'count',
    },
    {
      field: 'tenant_shortname',
      label: 'Padalinys',
      type: 'checkbox',
      icon: 'Users',
      defaultOpen: false,
      maxValues: 15,
      sortBy: 'count',
    },
    {
      field: 'is_reservable',
      label: 'Ar skolinamas',
      type: 'checkbox',
      icon: 'CalendarCheck',
      defaultOpen: false,
      sortBy: 'count',
    },
  ],
};

/**
 * Get facet config for a collection
 */
export function getCollectionFacetConfig(collection: string): CollectionFacetConfig | null {
  switch (collection) {
    case 'meetings':
      return MEETING_FACET_CONFIG;
    case 'agenda_items':
      return AGENDA_ITEM_FACET_CONFIG;
    case 'news':
      return NEWS_FACET_CONFIG;
    case 'institutions':
      return INSTITUTION_FACET_CONFIG;
    case 'resources':
      return RESOURCE_FACET_CONFIG;
    default:
      console.warn(`No facet config for collection: ${collection}`);
      return null;
  }
}

/**
 * Sort options for meetings
 */
export const MEETING_SORT_OPTIONS: SortOption[] = [
  { value: 'start_time:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
  { value: 'start_time:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
  { value: 'agenda_items_count:desc', label: 'Daugiausiai punktų', icon: 'ListOrdered' },
  { value: 'updated_at:desc', label: 'Paskutiniai atnaujinti', icon: 'RefreshCw' },
];

/**
 * Sort options for agenda items
 */
export const AGENDA_ITEM_SORT_OPTIONS: SortOption[] = [
  { value: 'meeting_start_time:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
  { value: 'meeting_start_time:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
  { value: 'updated_at:desc', label: 'Paskutiniai atnaujinti', icon: 'RefreshCw' },
];

/**
 * Get sort options for a collection
 */
export function getCollectionSortOptions(collection: string): SortOption[] {
  switch (collection) {
    case 'meetings':
      return MEETING_SORT_OPTIONS;
    case 'agenda_items':
      return AGENDA_ITEM_SORT_OPTIONS;
    default:
      return [
        { value: 'created_at:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
        { value: 'created_at:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
      ];
  }
}

/**
 * Human-readable labels for facet values
 */
export const FACET_VALUE_LABELS: Record<string, Record<string, string>> = {
  completion_status: {
    complete: 'Užbaigtas',
    incomplete: 'Neužbaigtas',
    partial: 'Dalinai užbaigtas',
  },
  vote_alignment_status: {
    'aligned': 'Atitinka',
    'misaligned': 'Neatitinka',
    'incomplete': 'Nepilna informacija',
    'unknown': 'Nežinoma',
    'no-vote': 'Nebalsuota',
  },
  student_vote: {
    for: 'Už',
    against: 'Prieš',
    abstain: 'Susilaikė',
  },
  decision: {
    approved: 'Pritarta',
    rejected: 'Atmesta',
    postponed: 'Atidėta',
    noted: 'Susipažinta',
  },
  is_complete: {
    true: 'Pilnai užpildyta',
    false: 'Nepilnai užpildyta',
  },
  brought_by_students: {
    true: 'Taip',
    false: 'Ne',
  },
  is_reservable: {
    true: 'Taip',
    false: 'Ne',
  },
};

/**
 * Get human-readable label for a facet value
 */
export function getFacetValueLabel(field: string, value: string): string {
  return FACET_VALUE_LABELS[field]?.[value] || value;
}
