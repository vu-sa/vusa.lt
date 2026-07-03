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
  facetBy: 'meeting_year,student_vote,decision,student_benefit,is_complete,brought_by_students,vote_alignment_status,tenant_shortnames',
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
      field: 'student_benefit',
      label: 'Palankumas studentams',
      type: 'checkbox',
      icon: 'Scale',
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
 * News collection facet configuration
 */
export const NEWS_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'lang,tenant_name',
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
      field: 'tenant_name',
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
 * Page collection facet configuration
 */
export const PAGE_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'lang,tenant_name,category_name',
  queryBy: 'title,meta_description',
  defaultSortBy: 'created_at:desc',
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
      field: 'category_name',
      label: 'Kategorija',
      type: 'checkbox',
      icon: 'Tag',
      defaultOpen: false,
      sortBy: 'count',
    },
    {
      field: 'tenant_name',
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
 * Calendar collection facet configuration
 */
export const CALENDAR_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'tenant_name,lang',
  queryBy: 'title,title_lt,title_en',
  defaultSortBy: 'date:desc',
  fields: [
    {
      field: 'tenant_name',
      label: 'Padalinys',
      type: 'checkbox',
      icon: 'Users',
      defaultOpen: true,
      maxValues: 15,
      sortBy: 'count',
    },
    {
      field: 'lang',
      label: 'Kalba',
      type: 'checkbox',
      icon: 'Globe',
      defaultOpen: false,
      sortBy: 'count',
    },
  ],
};

/**
 * Institution collection facet configuration (for future use)
 */
export const INSTITUTION_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'tenant_shortname,type_titles',
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
    {
      field: 'type_titles',
      label: 'Tipas',
      type: 'checkbox',
      icon: 'Building2',
      defaultOpen: false,
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
 * Duty collection facet configuration
 */
export const DUTY_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'tenant_shortname,type_titles',
  queryBy: 'name_lt,name_en,email,institution_name_lt,institution_name_en,current_user_names,previous_user_names',
  defaultSortBy: 'name_lt:asc',
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
    {
      field: 'type_titles',
      label: 'Tipas',
      type: 'checkbox',
      icon: 'Tag',
      defaultOpen: false,
      maxValues: 15,
      sortBy: 'count',
    },
  ],
};

/**
 * User collection facet configuration
 */
export const USER_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'tenant_shortname,current_duty_names,is_active',
  queryBy: 'name,email,phone,current_duty_names,previous_duty_names',
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
    {
      field: 'current_duty_names',
      label: 'Pareigos',
      type: 'checkbox',
      icon: 'Briefcase',
      defaultOpen: false,
      maxValues: 20,
      sortBy: 'count',
    },
    {
      field: 'is_active',
      label: 'Aktyvus',
      type: 'checkbox',
      icon: 'CheckCircle',
      defaultOpen: false,
      sortBy: 'count',
    },
  ],
};

/**
 * Document collection facet configuration
 */
export const DOCUMENT_FACET_CONFIG: CollectionFacetConfig = {
  facetBy: 'document_year,content_type_category,language_code,tenant_shortname,is_active',
  queryBy: 'title,summary,content_type,document_year',
  defaultSortBy: 'document_date:desc',
  fields: [
    {
      field: 'document_year',
      label: 'Metai',
      type: 'year-pills',
      icon: 'Calendar',
      defaultOpen: true,
      sortBy: 'alpha',
    },
    {
      field: 'content_type_category',
      label: 'Tipas',
      type: 'checkbox',
      icon: 'FileText',
      defaultOpen: true,
      sortBy: 'count',
    },
    {
      field: 'language_code',
      label: 'Kalba',
      type: 'checkbox',
      icon: 'Globe',
      defaultOpen: false,
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
      field: 'is_active',
      label: 'Aktyvus',
      type: 'checkbox',
      icon: 'CheckCircle',
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
    case 'pages':
      return PAGE_FACET_CONFIG;
    case 'calendar':
      return CALENDAR_FACET_CONFIG;
    case 'institutions':
      return INSTITUTION_FACET_CONFIG;
    case 'resources':
      return RESOURCE_FACET_CONFIG;
    case 'duties':
      return DUTY_FACET_CONFIG;
    case 'documents':
      return DOCUMENT_FACET_CONFIG;
    case 'users':
      return USER_FACET_CONFIG;
    default:
      console.warn(`No facet config for collection: ${collection}`);
      return null;
  }
}

/**
 * Sentinel sort value meaning "rank by Typesense relevance". Resolved to a
 * concrete `_text_match(...)` expression (with the collection's date tiebreak)
 * at search time, so it only makes sense while a query is active.
 */
export const RELEVANCE_SORT_VALUE = 'relevance';

/** The relevance sort option, prepended to every collection's sort list. */
const RELEVANCE_SORT_OPTION: SortOption = {
  value: RELEVANCE_SORT_VALUE,
  label: 'Pagal aktualumą',
  icon: 'Sparkles',
};

/**
 * Resolve the relevance sentinel into a concrete Typesense sort expression for a
 * collection, falling back to the collection's default date tiebreak. Other sort
 * values pass through unchanged.
 */
export function resolveSortValue(collection: string, sortBy: string): string {
  if (sortBy !== RELEVANCE_SORT_VALUE) {
    return sortBy;
  }
  const config = getCollectionFacetConfig(collection);
  const tiebreak = config?.defaultSortBy ?? 'created_at:desc';
  return `_text_match(buckets:10):desc,${tiebreak}`;
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
 * Sort options for news
 */
export const NEWS_SORT_OPTIONS: SortOption[] = [
  { value: 'publish_time:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
  { value: 'publish_time:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
  { value: 'title:asc', label: 'Pagal pavadinimą (A–Z)', icon: 'ArrowDownAZ' },
  { value: 'title:desc', label: 'Pagal pavadinimą (Z–A)', icon: 'ArrowUpAZ' },
];

/**
 * Sort options for pages
 */
export const PAGE_SORT_OPTIONS: SortOption[] = [
  { value: 'created_at:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
  { value: 'created_at:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
  { value: 'title:asc', label: 'Pagal pavadinimą (A–Z)', icon: 'ArrowDownAZ' },
  { value: 'title:desc', label: 'Pagal pavadinimą (Z–A)', icon: 'ArrowUpAZ' },
];

/**
 * Sort options for calendar
 */
export const CALENDAR_SORT_OPTIONS: SortOption[] = [
  { value: 'date:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
  { value: 'date:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
  { value: 'title:asc', label: 'Pagal pavadinimą (A–Z)', icon: 'ArrowDownAZ' },
  { value: 'title:desc', label: 'Pagal pavadinimą (Z–A)', icon: 'ArrowUpAZ' },
];

/**
 * Sort options for duties
 */
export const DUTY_SORT_OPTIONS: SortOption[] = [
  { value: 'name_lt:asc', label: 'Pagal pavadinimą (A–Z)', icon: 'ArrowDownAZ' },
  { value: 'name_lt:desc', label: 'Pagal pavadinimą (Z–A)', icon: 'ArrowUpAZ' },
  { value: 'created_at:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
  { value: 'created_at:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
];

/**
 * Sort options for users
 */
export const USER_SORT_OPTIONS: SortOption[] = [
  { value: 'created_at:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
  { value: 'created_at:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
  { value: 'name:asc', label: 'Pagal vardą (A–Z)', icon: 'ArrowDownAZ' },
  { value: 'name:desc', label: 'Pagal vardą (Z–A)', icon: 'ArrowUpAZ' },
];

/**
 * Sort options for documents
 */
export const DOCUMENT_SORT_OPTIONS: SortOption[] = [
  { value: 'document_date:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
  { value: 'document_date:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
  { value: 'title:asc', label: 'Pagal pavadinimą (A–Z)', icon: 'ArrowDownAZ' },
  { value: 'title:desc', label: 'Pagal pavadinimą (Z–A)', icon: 'ArrowUpAZ' },
];

/**
 * Get sort options for a collection
 */
export function getCollectionSortOptions(collection: string): SortOption[] {
  const baseOptions = ((): SortOption[] => {
    switch (collection) {
      case 'meetings':
        return MEETING_SORT_OPTIONS;
      case 'agenda_items':
        return AGENDA_ITEM_SORT_OPTIONS;
      case 'news':
        return NEWS_SORT_OPTIONS;
      case 'pages':
        return PAGE_SORT_OPTIONS;
      case 'calendar':
        return CALENDAR_SORT_OPTIONS;
      case 'duties':
        return DUTY_SORT_OPTIONS;
      case 'documents':
        return DOCUMENT_SORT_OPTIONS;
      case 'users':
        return USER_SORT_OPTIONS;
      default:
        return [
          { value: 'created_at:desc', label: 'Naujausi pirmi', icon: 'ArrowDown' },
          { value: 'created_at:asc', label: 'Seniausi pirmi', icon: 'ArrowUp' },
        ];
    }
  })();

  return [RELEVANCE_SORT_OPTION, ...baseOptions];
}

/**
 * Human-readable labels for facet values
 */
export const FACET_VALUE_LABELS: Record<string, Record<string, string>> = {
  completion_status: {
    complete: 'Užbaigtas',
    incomplete: 'Neužbaigtas',
    partial: 'Dalinai užbaigtas',
    no_items: 'Be punktų',
  },
  // Meeting values: all_match, mixed, all_mismatch, neutral.
  // Agenda item values: match, mismatch, mixed, incomplete, neutral.
  vote_alignment_status: {
    all_match: 'Visi sutampa',
    match: 'Sutampa',
    mixed: 'Iš dalies sutampa',
    all_mismatch: 'Visi nesutampa',
    mismatch: 'Nesutampa',
    incomplete: 'Nepilna informacija',
    neutral: 'Neutralu',
  },
  student_vote: {
    positive: 'Pritarė',
    negative: 'Nepritarė',
    neutral: 'Susilaikė',
  },
  decision: {
    positive: 'Priimtas',
    negative: 'Nepriimtas',
    neutral: 'Susilaikyta',
  },
  student_benefit: {
    positive: 'Palanku',
    negative: 'Nepalanku',
    neutral: 'Neutralu',
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
