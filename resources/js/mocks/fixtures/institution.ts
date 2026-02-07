/**
 * Creates a mock Institution entity
 * Note: Uses type assertion for test mocks where not all properties are needed
 */
export function createMockInstitution(
  overrides: Record<string, unknown> = {}
): App.Entities.Institution {
  return {
    id: '1',
    name: ['VU SA Taryba'],
    short_name: ['Taryba'],
    description: ['Aukščiausiasis VU SA organas'],
    alias: 'vusa-taryba',
    image_url: null,
    tenant_id: 1,
    is_active: true,
    contacts_layout: 'default',
    created_at: '2025-01-01T00:00:00.000Z',
    updated_at: '2025-01-01T00:00:00.000Z',
    deleted_at: null,
    // Mutators
    related_institutions: [],
    maybe_short_name: 'Taryba',
    has_public_meetings: true,
    has_protocol: false,
    has_report: false,
    translations: {},
    // Counts
    duties_count: 15,
    types_count: 1,
    documents_count: 0,
    check_ins_count: 0,
    meetings_count: 12,
    followers_count: 0,
    available_trainings_count: 0,
    comments_count: 0,
    outgoing_relationships_count: 0,
    incoming_relationships_count: 0,
    files_count: 0,
    fileable_files_count: 0,
    available_files_count: 0,
    tasks_count: 0,
    activities_count: 0,
    // Exists
    duties_exists: true,
    types_exists: true,
    tenant_exists: true,
    documents_exists: false,
    check_ins_exists: false,
    meetings_exists: true,
    followers_exists: false,
    available_trainings_exists: false,
    comments_exists: false,
    outgoing_relationships_exists: false,
    incoming_relationships_exists: false,
    files_exists: false,
    fileable_files_exists: false,
    available_files_exists: false,
    tasks_exists: false,
    activities_exists: false,
    ...overrides,
  } as App.Entities.Institution;
}

/**
 * Common institutions for testing
 */
export const mockInstitutions = {
  council: createMockInstitution({
    id: '1',
    name: ['VU SA Taryba'],
    short_name: ['Taryba'],
    alias: 'vusa-taryba',
  }),
  parliament: createMockInstitution({
    id: '2',
    name: ['VU SA Parlamentas'],
    short_name: ['Parlamentas'],
    alias: 'vusa-parlamentas',
  }),
  board: createMockInstitution({
    id: '3',
    name: ['VU SA Valdyba'],
    short_name: ['Valdyba'],
    alias: 'vusa-valdyba',
  }),
};
