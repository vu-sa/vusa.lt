import type { InstitutionSearchFilters, InstitutionFacet } from '@/Types/InstitutionSearchTypes';

interface SearchParams {
  q: string;
  query_by: string;
  query_by_weights?: string;
  facet_by: string;
  max_facet_values: number;
  per_page: number;
  page: number;
  sort_by: string;
  filter_by?: string;
  prefix?: boolean;
  infix?: string;
  prioritize_exact_match?: boolean;
  prioritize_token_position?: boolean;
  typo_tokens_threshold?: number;
  min_len_1typo?: number;
  min_len_2typo?: number;
  drop_tokens_threshold?: number;
}

interface RawInstitutionDocument {
  name_lt?: string;
  name_en?: string;
  short_name_lt?: string;
  short_name_en?: string;
  type_slugs?: string[];
  type_titles_lt?: string[];
  type_titles_en?: string[];
  tenant_id?: number;
  tenant_alias?: string;
  tenant_shortname?: string;
  tenant_type?: string;
  contacts?: ContactHit[];
  duties?: DutyHit[];
  is_student_representation?: boolean;
  [key: string]: unknown;
}

interface SearchResponse {
  hits?: Array<{ document: RawInstitutionDocument }>;
  found?: number;
  facet_counts?: Array<{
    field_name: string;
    counts: Array<{
      value: string;
      count: number;
    }>;
  }>;
}

interface SearchClient {
  search: (collection: string, searchParams: SearchParams) => Promise<SearchResponse>;
}

interface ContactHit {
  id: string;
  name: string;
  duty_id?: string;
  duty_name?: string;
  profile_photo_path?: string | null;
  profile_photo_focal_point?: string | null;
  additional_photo?: string | null;
  additional_photo_focal_point?: string | null;
}

interface DutyHit {
  id: string;
  name: string;
  current_users: Array<{
    id: string;
    name: string;
    profile_photo_path: string | null;
    profile_photo_focal_point: string | null;
    pivot: {
      additional_photo: string | null;
      additional_photo_focal_point: string | null;
    };
  }>;
}

export class InstitutionSearchService {
  private typesenseClient: SearchClient | null = null;
  private abortController: AbortController | null = null;
  private collectionName: string;

  constructor(typesenseClient: SearchClient | null, collectionName?: string) {
    this.typesenseClient = typesenseClient;
    this.collectionName = collectionName || 'public_institutions';
  }

  setClient(client: SearchClient | null) {
    this.typesenseClient = client;
  }

  setCollectionName(name: string) {
    this.collectionName = name;
  }

  cancelCurrentSearch() {
    if (this.abortController) {
      this.abortController.abort();
    }
  }

  async performSearch(
    filters: InstitutionSearchFilters,
    perPage: number,
    isLoadMore = false,
    currentPage = 0,
    locale = 'lt',
  ): Promise<{
    hits: Array<Record<string, unknown>>;
    totalHits: number;
    facets: InstitutionFacet[];
    currentPage: number;
    totalPages: number;
  }> {
    if (!this.typesenseClient) {
      throw new Error('Typesense client not initialized');
    }

    // Cancel previous request
    this.cancelCurrentSearch();
    this.abortController = new AbortController();

    // Build search parameters
    const searchParams = this.buildSearchParams(filters, perPage, isLoadMore, currentPage, locale);

    try {
      // Execute search with timeout
      const timeoutPromise = new Promise<never>((_, reject) => {
        setTimeout(() => reject(new Error('Search request timed out')), 10000);
      });

      const searchPromise = this.typesenseClient.search(this.collectionName, searchParams);
      const response = await Promise.race([searchPromise, timeoutPromise]);

      if (this.abortController?.signal.aborted) {
        throw new Error('Search was cancelled');
      }

      // Process results — normalize Typesense flat fields into the shape components expect
      const hits = response.hits?.map(hit => this.normalizeDocument(hit.document, locale)) || [];
      const totalHits = response.found || 0;
      const totalPages = Math.ceil(totalHits / perPage);
      const newCurrentPage = isLoadMore ? currentPage + 1 : 1;

      // Process facets
      const facets = this.processFacets(response.facet_counts || []);

      return {
        hits,
        totalHits,
        facets,
        currentPage: newCurrentPage,
        totalPages,
      };
    }
    catch (error) {
      if (error instanceof Error && error.name === 'AbortError') {
        throw error;
      }
      throw new Error(`Search failed: ${error instanceof Error ? error.message : 'Unknown error'}`, { cause: error });
    }
  }

  private buildSearchParams(
    filters: InstitutionSearchFilters,
    perPage: number,
    isLoadMore: boolean,
    currentPage: number,
    locale: string,
  ): SearchParams {
    const query = filters.query.trim();

    // Search in locale-appropriate fields with weights (no descriptions - they can be very long)
    const queryByFields = locale === 'en'
      ? 'name_en,name_lt,short_name_en,short_name_lt,alias,current_user_names'
      : 'name_lt,name_en,short_name_lt,short_name_en,alias,current_user_names';

    const searchParams: SearchParams = {
      q: query || '*',
      query_by: queryByFields,
      query_by_weights: '10,8,6,4,3,5',
      facet_by: [
        'tenant_shortname',
        'type_slugs',
        'has_contacts',
      ].join(','),
      max_facet_values: 50,
      per_page: perPage,
      page: isLoadMore ? currentPage + 1 : 1,
      // Smart sorting: relevance for searches, logos first then alphabetical for browsing
      sort_by: (query && query !== '*')
        ? '_text_match:desc,has_logo:desc,name_lt:asc'
        : (locale === 'en' ? 'has_logo:desc,name_en:asc,name_lt:asc' : 'has_logo:desc,name_lt:asc,name_en:asc'),
      prefix: true,
      infix: 'fallback',
      prioritize_exact_match: true,
      prioritize_token_position: true,
      typo_tokens_threshold: 2,
      min_len_1typo: 3,
      min_len_2typo: 6,
      drop_tokens_threshold: 5,
    };

    // Build filter conditions
    const filterConditions = this.buildFilterConditions(filters);
    if (filterConditions.length > 0) {
      searchParams.filter_by = filterConditions.join(' && ');
    }

    return searchParams;
  }

  private buildFilterConditions(filters: InstitutionSearchFilters): string[] {
    const filterConditions: string[] = [];

    // Tenant filters - use exact match for each tenant
    if (filters.tenants.length > 0) {
      const tenantConditions = filters.tenants.map(t => `tenant_shortname:="${t}"`);
      filterConditions.push(`(${tenantConditions.join(' || ')})`);
    }

    // Type filters - use exact match for each type slug
    if (filters.types.length > 0) {
      const typeConditions = filters.types.map(t => `type_slugs:="${t}"`);
      filterConditions.push(`(${typeConditions.join(' || ')})`);
    }

    // Has contacts filter
    if (filters.hasContacts !== null) {
      filterConditions.push(`has_contacts:=${filters.hasContacts}`);
    }

    return filterConditions;
  }

  private normalizeDocument(doc: RawInstitutionDocument, locale: string): Record<string, unknown> {
    // Pick the locale-appropriate name/short_name, falling back to the other locale
    const name = (locale === 'en' ? doc.name_en || doc.name_lt : doc.name_lt || doc.name_en) || '';
    const short_name = (locale === 'en' ? doc.short_name_en || doc.short_name_lt : doc.short_name_lt || doc.short_name_en) || null;

    // Reconstruct types array from parallel Typesense arrays
    const typeSlugs: string[] = doc.type_slugs || [];
    const typeTitlesLt: string[] = doc.type_titles_lt || [];
    const typeTitlesEn: string[] = doc.type_titles_en || [];
    const types = typeSlugs.map((slug, i) => ({
      slug,
      title: locale === 'en' ? (typeTitlesEn[i] || typeTitlesLt[i] || slug) : (typeTitlesLt[i] || typeTitlesEn[i] || slug),
    }));

    // Reconstruct nested tenant object from flat tenant_* fields
    const tenant = doc.tenant_alias != null
      ? {
          id: doc.tenant_id ?? null,
          alias: doc.tenant_alias,
          shortname: doc.tenant_shortname || null,
          type: doc.tenant_type || null,
        }
      : null;

    const contacts = Array.isArray(doc.contacts) ? doc.contacts : [];

    // Reconstruct lightweight duties array so components expecting duties.current_users work seamlessly
    const duties = Array.isArray(doc.duties) && doc.duties.length > 0
      ? doc.duties
      : (contacts.length > 0
          ? Object.values(
              contacts.reduce((acc: Record<string, DutyHit>, c: ContactHit) => {
                const dutyId = c.duty_id || 'default';
                if (!acc[dutyId]) {
                  acc[dutyId] = {
                    id: dutyId,
                    name: c.duty_name || '',
                    current_users: [],
                  };
                }
                acc[dutyId].current_users.push({
                  id: c.id,
                  name: c.name,
                  profile_photo_path: c.profile_photo_path ?? null,
                  profile_photo_focal_point: c.profile_photo_focal_point ?? null,
                  pivot: {
                    additional_photo: c.additional_photo ?? null,
                    additional_photo_focal_point: c.additional_photo_focal_point ?? null,
                  },
                });
                return acc;
              }, {}),
            )
          : []);

    return {
      ...doc,
      name,
      short_name,
      types,
      tenant,
      contacts,
      duties,
      is_student_representation: Boolean(doc.is_student_representation),
    };
  }

  private processFacets(facetCounts: Array<{
    field_name: string;
    counts: Array<{ value: string; count: number }>;
  }>): InstitutionFacet[] {
    return facetCounts.map(facetData => ({
      field: facetData.field_name,
      label: this.getFacetLabel(facetData.field_name),
      values: (facetData.counts || []).map(countData => ({
        value: countData.value,
        label: countData.value,
        count: countData.count,
      })).sort((a, b) => b.count - a.count),
    }));
  }

  private getFacetLabel(field: string): string {
    const labels: Record<string, string> = {
      tenant_shortname: 'Organization',
      type_slugs: 'Type',
      has_contacts: 'Has Contacts',
    };
    return labels[field] || field;
  }

  async loadInitialFacets(): Promise<InstitutionFacet[]> {
    if (!this.typesenseClient) {
      return [];
    }

    try {
      const searchRequest: SearchParams = {
        q: '*',
        query_by: 'name_lt,name_en,short_name_lt,short_name_en,alias,current_user_names',
        facet_by: [
          'tenant_shortname',
          'type_slugs',
          'has_contacts',
        ].join(','),
        max_facet_values: 50,
        per_page: 1,
        page: 1,
        sort_by: 'has_logo:desc,name_lt:asc',
      };

      const response = await this.typesenseClient.search(this.collectionName, searchRequest);

      return this.processFacets(response.facet_counts || []);
    }
    catch (error) {
      console.error('Failed to load initial facets:', error);
      return [];
    }
  }
}
