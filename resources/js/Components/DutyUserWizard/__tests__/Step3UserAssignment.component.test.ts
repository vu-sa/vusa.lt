import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { reactive, nextTick } from 'vue';

import Step3UserAssignment from '@/Components/DutyUserWizard/Step3UserAssignment.vue';
import { commonStubs } from '@/tests/stubs';

/**
 * This component previously had no test at all, which let two setup-time crashes
 * through a passing build and full suite: a temporal-dead-zone reference (a watch
 * registered above the ref it watched) and a computed still reading a prop that had
 * been removed. Both are invisible to esbuild and to the type checker, and neither
 * is reachable without actually mounting the component — so mount it.
 */

const apiMock = {
  data: { value: [] as unknown[] },
  isFetching: { value: false },
  execute: vi.fn(),
  /** The url ref useApi was handed, so the query string can be asserted. */
  lastUrl: '',
};

vi.mock('@/Composables/useApi', () => ({
  useApi: vi.fn((url: { value: string }) => ({
    data: apiMock.data,
    isFetching: apiMock.isFetching,
    execute: vi.fn((...args: unknown[]) => {
      apiMock.lastUrl = url?.value ?? '';
      return apiMock.execute(...args);
    }),
    error: { value: null },
    isFinished: { value: false },
    isSuccess: { value: false },
    response: { value: null },
    abort: vi.fn(),
  })),
  useApiMutation: vi.fn(() => ({
    execute: vi.fn(),
    isFetching: { value: false },
    isSuccess: { value: false },
    error: { value: null },
    isFinished: { value: false },
    data: { value: null },
    response: { value: null },
    abort: vi.fn(),
  })),
}));

const makeWizard = (overrides: Record<string, unknown> = {}) => {
  const state = reactive({
    currentStep: 3,
    maxCompletedStep: 2,
    institution: { id: 'inst-1', name: 'MIF' },
    duty: {
      id: 'duty-1',
      name: 'Studentų atstovas',
      current_users: [],
      places_to_occupy: 3,
    },
    userChanges: [],
    newUsersToCreate: [],
    loading: { submission: false, institutions: false, duties: false, stepData: false },
    errors: {},
    validation: { institution: true, duty: true, users: true, canProceed: true },
    ...overrides,
  });

  return {
    state,
    hasChanges: false,
    addUserToAdd: vi.fn(),
    addUserToRemove: vi.fn(),
    removeUserChange: vi.fn(),
    updateUserChange: vi.fn(),
    addNewUserToCreate: vi.fn(),
    removeNewUserToCreate: vi.fn(),
    setAllAddedUsersStartDate: vi.fn(),
    setAllAddedUsersEndDate: vi.fn(),
    setAllRemovedUsersEndDate: vi.fn(),
  };
};

const mountStep = (wizard = makeWizard()) =>
  mount(Step3UserAssignment, {
    global: {
      provide: { dutyUserWizard: wizard },
      stubs: { ...commonStubs },
    },
  });

describe('Step3UserAssignment.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    apiMock.data.value = [];
    apiMock.isFetching.value = false;
    apiMock.lastUrl = '';
  });

  it('mounts without throwing', () => {
    // The regression guard: both previous breakages threw during setup, so simply
    // reaching a rendered wrapper is the assertion that matters here.
    expect(() => mountStep()).not.toThrow();
  });

  it('renders the assignment UI once step data has loaded', () => {
    const wrapper = mountStep();

    expect(wrapper.find('input[placeholder]').exists()).toBe(true);
    expect(wrapper.text()).not.toContain('Kraunami duomenys...');
  });

  it('shows the loading state while lazy step data is in flight', () => {
    const wrapper = mountStep(makeWizard({
      loading: { submission: false, institutions: false, duties: false, stepData: true },
    }));

    expect(wrapper.text()).toContain('Kraunami duomenys...');
  });

  it('does not query the server until the term is long enough', async () => {
    vi.useFakeTimers();
    const wrapper = mountStep();

    const input = wrapper.findAll('input').find(i => i.attributes('placeholder')?.includes('el. pašt'));
    await input!.setValue('a');
    vi.advanceTimersByTime(500);
    await flushPromises();

    expect(apiMock.execute).not.toHaveBeenCalled();
    vi.useRealTimers();
  });

  it('searches every tenant, not just the admin own', async () => {
    // Assigning somebody from another unit is how a person joins a new one. Scoping
    // this picker to the admin's own tenant is what drives them to create a second
    // account for a person who already exists elsewhere.
    vi.useFakeTimers();
    const wrapper = mountStep();

    const input = wrapper.findAll('input').find(i => i.attributes('placeholder')?.includes('el. pašt'));
    await input!.setValue('Jonas');
    vi.advanceTimersByTime(500);
    await flushPromises();

    expect(apiMock.execute).toHaveBeenCalled();
    expect(apiMock.lastUrl).toContain('scope=all');
    vi.useRealTimers();
  });

  it('labels each result with its unit so identical names can be told apart', async () => {
    apiMock.data.value = [
      { id: 'user-3', name: 'Jonas Jonaitis', email: 'j***@stud.vu.lt', duties_count: 2, tenants: ['VU SA MIF'] },
    ];

    const wrapper = mountStep();
    const input = wrapper.findAll('input').find(i => i.attributes('placeholder')?.includes('el. pašt'));
    await input!.trigger('focus');
    await input!.setValue('Jonas');
    await nextTick();

    expect(wrapper.text()).toContain('VU SA MIF');
  });

  it('excludes members already on the duty from the results', async () => {
    apiMock.data.value = [
      { id: 'user-1', name: 'Jau Narys', email: 'jau@stud.vu.lt', duties_count: 1 },
      { id: 'user-2', name: 'Naujas Zmogus', email: 'naujas@stud.vu.lt', duties_count: 0 },
    ];

    const wizard = makeWizard({
      duty: {
        id: 'duty-1',
        name: 'Studentų atstovas',
        current_users: [{ id: 'user-1', name: 'Jau Narys' }],
        places_to_occupy: 3,
      },
    });
    const wrapper = mountStep(wizard);

    const input = wrapper.findAll('input').find(i => i.attributes('placeholder')?.includes('el. pašt'));
    // The dropdown only opens on focus, and only once the term is long enough.
    await input!.trigger('focus');
    await input!.setValue('as');
    await nextTick();

    const dropdown = wrapper.findAll('button').filter(b => b.text().includes('@stud.vu.lt'));

    expect(dropdown.map(b => b.text()).join(' ')).toContain('Naujas Zmogus');
    expect(dropdown.map(b => b.text()).join(' ')).not.toContain('Jau Narys');
  });
});
