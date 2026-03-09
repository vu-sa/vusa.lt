/**
 * Creates a mock User entity
 * Note: Uses type assertion for test mocks where not all properties are needed
 */
export function createMockUser(
  overrides: Record<string, unknown> = {}
): App.Entities.User {
  return {
    id: '1',
    name: 'Test User',
    email: 'test@vusa.lt',
    phone: '+37061234567',
    pronouns: null,
    show_pronouns: false,
    facebook_url: null,
    profile_photo_path: null,
    is_active: true,
    last_action: null,
    microsoft_token: null,
    created_at: '2025-01-01T00:00:00.000Z',
    updated_at: '2025-01-01T00:00:00.000Z',
    deleted_at: null,
    // Mutators
    has_password: false,
    translations: {},
    // Counts
    duties_count: 1,
    previous_duties_count: 0,
    current_duties_count: 1,
    dutiables_count: 0,
    tasks_count: 0,
    followed_institutions_count: 0,
    muted_institutions_count: 0,
    reservations_count: 0,
    memberships_count: 0,
    trainings_count: 0,
    available_trainings_through_user_count: 0,
    push_subscriptions_count: 0,
    roles_count: 0,
    permissions_count: 0,
    activities_count: 0,
    notifications_count: 0,
    // Exists
    duties_exists: true,
    previous_duties_exists: false,
    current_duties_exists: true,
    dutiables_exists: false,
    tasks_exists: false,
    followed_institutions_exists: false,
    muted_institutions_exists: false,
    reservations_exists: false,
    memberships_exists: false,
    trainings_exists: false,
    available_trainings_through_user_exists: false,
    push_subscriptions_exists: false,
    roles_exists: false,
    permissions_exists: false,
    activities_exists: false,
    notifications_exists: false,
    ...overrides,
  } as App.Entities.User;
}

/**
 * Creates a mock User with current_duties (for auth context)
 */
export function createMockAuthUser(
  overrides: Partial<App.Entities.User> = {}
): App.Entities.User & { current_duties: Array<{ institution: { id: string; name: string; shortname: string } }> } {
  const user = createMockUser(overrides);
  return {
    ...user,
    current_duties: [
      {
        institution: {
          id: 'vusa',
          name: 'Vilniaus universiteto Studentų atstovybė',
          shortname: 'VU SA',
        },
      },
    ],
  } as App.Entities.User & { current_duties: Array<{ institution: { id: string; name: string; shortname: string } }> };
}
