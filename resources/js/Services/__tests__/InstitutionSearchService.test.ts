import { describe, it, expect, beforeEach, vi } from 'vitest';
import type { Client } from 'typesense';

import { InstitutionSearchService } from '../InstitutionSearchService';

import type { InstitutionSearchFilters } from '@/Types/InstitutionSearchTypes';

interface MockSearchClient {
  search: ReturnType<typeof vi.fn>;
}

describe('InstitutionSearchService', () => {
  let mockClient: MockSearchClient;
  let service: InstitutionSearchService;

  beforeEach(() => {
    mockClient = {
      search: vi.fn().mockResolvedValue({
        hits: [
          {
            document: {
              id: 'inst-1',
              title: 'VU SA Centrinis biuras',
              name_lt: 'Centrinis biuras',
              name_en: 'Central Office',
              current_user_names: ['Mykolas Liaudanskas'],
              is_student_representation: true,
              contacts: [
                {
                  id: 'user-1',
                  name: 'Mykolas Liaudanskas',
                  duty_id: 'duty-1',
                  duty_name: 'Koordinatorius',
                  profile_photo_path: '/photos/mykolas.jpg',
                },
              ],
            },
          },
        ],
        found: 1,
        facet_counts: [],
      }),
    };

    service = new InstitutionSearchService(mockClient as unknown as Client, 'public_institutions');
  });

  it('includes current_user_names in query_by parameters for search (lt locale)', async () => {
    const filters: InstitutionSearchFilters = {
      query: 'Mykolas',
      tenants: [],
      types: [],
      hasContacts: null,
    };

    const result = await service.performSearch(filters, 24, false, 1, 'lt');

    expect(mockClient.search).toHaveBeenCalledWith(
      'public_institutions',
      expect.objectContaining({
        q: 'Mykolas',
        query_by: expect.stringContaining('current_user_names'),
      }),
    );

    const callArgs = mockClient.search.mock.calls[0][1];
    expect(callArgs.query_by).toBe('name_lt,name_en,short_name_lt,short_name_en,alias,current_user_names');
    expect(callArgs.query_by_weights).toBe('10,8,6,4,3,5');

    expect(result.hits).toHaveLength(1);
    expect(result.hits[0].name).toBe('Centrinis biuras');
    expect(result.hits[0].contacts).toHaveLength(1);
    expect(result.hits[0].contacts[0].name).toBe('Mykolas Liaudanskas');
    expect(result.hits[0].is_student_representation).toBe(true);
  });

  it('includes current_user_names in query_by parameters for search (en locale)', async () => {
    const filters: InstitutionSearchFilters = {
      query: 'Mykolas',
      tenants: [],
      types: [],
      hasContacts: null,
    };

    await service.performSearch(filters, 24, false, 1, 'en');

    const callArgs = mockClient.search.mock.calls[0][1];
    expect(callArgs.query_by).toBe('name_en,name_lt,short_name_en,short_name_lt,alias,current_user_names');
    expect(callArgs.query_by_weights).toBe('10,8,6,4,3,5');
  });
});
