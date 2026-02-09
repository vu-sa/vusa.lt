/**
 * Creates a mock Meeting entity
 */
export function createMockMeeting(
  overrides: Partial<App.Entities.Meeting> = {},
): App.Entities.Meeting {
  const now = new Date();
  const startTime = new Date(now.getTime() + 24 * 60 * 60 * 1000); // Tomorrow
  const endTime = new Date(startTime.getTime() + 2 * 60 * 60 * 1000); // 2 hours later

  return {
    id: '01HYMEETING123456789ABCDE',
    title: 'VU SA Tarybos posėdis',
    description: 'Reguliarus mėnesinis tarybos posėdis',
    start_time: startTime.toISOString(),
    end_time: endTime.toISOString(),
    created_at: '2025-01-01T00:00:00.000Z',
    updated_at: '2025-01-01T00:00:00.000Z',
    deleted_at: null,
    // Mutators
    is_public: true,
    completion_status: 'upcoming',
    has_protocol: false,
    has_report: false,
    // Counts
    agenda_items_count: 0,
    institutions_count: 1,
    comments_count: 0,
    types_count: 1,
    files_count: 0,
    fileable_files_count: 0,
    available_files_count: 0,
    tasks_count: 0,
    activities_count: 0,
    // Exists
    agenda_items_exists: false,
    institutions_exists: true,
    comments_exists: false,
    types_exists: true,
    files_exists: false,
    fileable_files_exists: false,
    available_files_exists: false,
    tasks_exists: false,
    activities_exists: false,
    // Optional relations
    agenda_items: undefined,
    institutions: undefined,
    comments: undefined,
    types: undefined,
    files: undefined,
    tasks: undefined,
    activities: undefined,
    ...overrides,
  };
}

/**
 * Creates a meeting with agenda items
 */
export function createMockMeetingWithAgenda(
  overrides: Partial<App.Entities.Meeting> = {},
): App.Entities.Meeting {
  return createMockMeeting({
    agenda_items: [
      {
        id: '01HYAGENDA1234567890ABCDE',
        title: 'Darbotvarkės tvirtinimas',
        description: null,
        order: 1,
        meeting_id: '01HYMEETING123456789ABCDE',
        brought_by_students: false,
        created_at: '2025-01-01T00:00:00.000Z',
        updated_at: '2025-01-01T00:00:00.000Z',
        activities_count: 0,
        meeting_exists: true,
        activities_exists: false,
      },
      {
        id: '01HYAGENDA2234567890ABCDE',
        title: 'Ataskaitų pristatymas',
        description: 'Kadencijos ataskaitos',
        order: 2,
        meeting_id: '01HYMEETING123456789ABCDE',
        brought_by_students: false,
        created_at: '2025-01-01T00:00:00.000Z',
        updated_at: '2025-01-01T00:00:00.000Z',
        activities_count: 0,
        meeting_exists: true,
        activities_exists: false,
      },
    ],
    agenda_items_count: 2,
    agenda_items_exists: true,
    ...overrides,
  });
}
