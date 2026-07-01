import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { nextTick, ref, computed, defineComponent, h } from 'vue';
import { router } from '@inertiajs/vue3';

import UserForm from '@/Components/AdminForms/UserForm.vue';
import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

// Captures the columns passed to each stubbed SimpleDataTable so the previous-duties
// delete cell handler can be invoked directly (the real table is stubbed out).
const capturedColumns: { previous?: any[] } = {};
const SimpleDataTableStub = defineComponent({
  props: ['data', 'columns'],
  setup(props) {
    if (Array.isArray(props.columns) && props.columns.some((c: any) => c.id === 'delete')) {
      capturedColumns.previous = props.columns;
    }
    return () => h('div', { 'data-testid': 'simple-data-table' });
  },
});

// Control variables for useApiMutation mock
const apiMockController = {
  execute: vi.fn(),
  isFetching: ref(false),
  isSuccess: ref(false),
  error: ref<string | null>(null),
};

vi.mock('@/Composables/useApi', async () => {
  return {
    useApi: vi.fn(() => ({
      data: { value: null },
      response: { value: null },
      error: { value: null },
      isFetching: { value: false },
      isFinished: { value: false },
      isSuccess: { value: false },
      execute: vi.fn(),
      abort: vi.fn(),
    })),
    useApiMutation: vi.fn(() => ({
      execute: apiMockController.execute,
      isFetching: apiMockController.isFetching,
      isSuccess: apiMockController.isSuccess,
      error: apiMockController.error,
      isFinished: { value: false },
      data: { value: null },
      response: { value: null },
      abort: vi.fn(),
    })),
  };
});

const createUser = (overrides: Record<string, any> = {}) => ({
  id: 'user-1',
  name: 'Petras Petraitis',
  email: 'petras@stud.vu.lt',
  phone: null,
  facebook_url: null,
  profile_photo_path: null,
  pronouns: { lt: '', en: '' },
  show_pronouns: false,
  has_password: false,
  last_action: null,
  current_duties: [
    {
      id: 'duty-1',
      name: 'Komunikacijos koordinatorius',
      email: 'komunikacija@vusa.lt',
      pivot: {
        id: 'dutiable-1',
        duty_id: 'duty-1',
        dutiable_id: 'user-1',
        start_date: '2024-09-01',
        end_date: null,
        additional_email: null,
      },
    },
    {
      id: 'duty-2',
      name: 'Studentų atstovas',
      email: 'atstovas@fsf.vu.lt',
      pivot: {
        id: 'dutiable-2',
        duty_id: 'duty-2',
        dutiable_id: 'user-1',
        start_date: '2024-09-01',
        end_date: null,
        additional_email: 'mano@example.com',
      },
    },
  ],
  previous_duties: [
    {
      id: 'duty-3',
      name: 'Buvęs koordinatorius',
      email: 'buvęs@vusa.lt',
      pivot: {
        id: 'dutiable-3',
        duty_id: 'duty-3',
        dutiable_id: 'user-1',
        start_date: '2023-09-01',
        end_date: '2024-06-30',
        additional_email: null,
      },
    },
  ],
  roles: [],
  ...overrides,
});

describe('UserForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const createWrapper = (props = {}) => {
    return mount(UserForm, {
      props: {
        user: createUser(),
        roles: [],
        tenantsWithDuties: [],
        permissableTenants: [],
        ...props,
      },
      global: {
        stubs: {
          ...commonStubs,
          // Stub heavy components
          TransferList: { template: '<div data-testid="transfer-list" />' },
          Tree: { template: '<div data-testid="tree" />' },
          TiptapEditor: { template: '<div data-testid="tiptap" />' },
          ImageUpload: { template: '<div data-testid="image-upload" />' },
          SimpleDataTable: SimpleDataTableStub,
          MultiSelect: { template: '<div data-testid="multi-select" />' },
          SingleSelect: { template: '<div data-testid="single-select" />' },
          DatePicker: { template: '<div data-testid="date-picker" />' },
          // Icons
          IFluentAdd24Filled: { template: '<span class="icon-add" />' },
          IFluentCopy16Regular: { template: '<span class="icon-copy" />' },
          PersonEdit24Regular: { template: '<span class="icon-edit" />' },
          Delete24Regular: { template: '<span class="icon-delete" />' },
          Eye16Regular: { template: '<span class="icon-eye" />' },
        },
      },
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
    capturedColumns.previous = undefined;
    apiMockController.execute.mockResolvedValue(undefined);
    apiMockController.isFetching.value = false;
    apiMockController.isSuccess.value = false;
    apiMockController.error.value = null;
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  describe('email warning', () => {
    it('shows warning when user email ends with @vusa.lt', async () => {
      wrapper = createWrapper({
        user: createUser({ email: 'koordinatorius@vusa.lt' }),
      });
      await nextTick();

      const warning = wrapper.find('.text-amber-600');
      expect(warning.exists()).toBe(true);
      expect(warning.text()).toContain('koordinatorius@vusa.lt');
      expect(warning.text()).toContain('@vusa.lt');
    });

    it('does not show warning for .stud.vu.lt student email', async () => {
      wrapper = createWrapper();
      await nextTick();

      const warning = wrapper.find('.text-amber-600');
      expect(warning.exists()).toBe(false);
    });

    it('does not show warning for personal email', async () => {
      wrapper = createWrapper({
        user: createUser({ email: 'petras@gmail.com' }),
      });
      await nextTick();

      const warning = wrapper.find('.text-amber-600');
      expect(warning.exists()).toBe(false);
    });
  });

  describe('duty email context block', () => {
    it('renders only @vusa.lt duty emails in context block', async () => {
      wrapper = createWrapper();
      await nextTick();

      const contextBlock = wrapper.find('.bg-blue-50');
      expect(contextBlock.exists()).toBe(true);
      expect(contextBlock.text()).toContain('Komunikacijos koordinatorius');
      expect(contextBlock.text()).toContain('komunikacija@vusa.lt');
      // .vu.lt duty should NOT appear here
      expect(contextBlock.text()).not.toContain('atstovas@fsf.vu.lt');
    });

    it('shows grey message when no duties have any email', async () => {
      wrapper = createWrapper({
        user: createUser({
          current_duties: [
            {
              id: 'duty-1',
              name: 'Pareiga be pašto',
              email: null,
              pivot: {
                id: 'dutiable-1',
                duty_id: 'duty-1',
                dutiable_id: 'user-1',
                start_date: '2024-09-01',
                end_date: null,
                additional_email: null,
              },
            },
          ],
        }),
      });
      await nextTick();

      const contextBlock = wrapper.find('.bg-blue-50');
      expect(contextBlock.exists()).toBe(false);

      const greyMessage = wrapper.find('.text-muted-foreground');
      expect(greyMessage.text()).toContain('vienintelis naudojamas prisijungimui');
    });
  });

  describe('form rendering', () => {
    it('renders email input with student placeholder', async () => {
      wrapper = createWrapper();
      await nextTick();

      const emailInput = wrapper.find('input[placeholder="vardas.pavarde@stud.vu.lt"]');
      expect(emailInput.exists()).toBe(true);
    });

    it('renders duty tables', async () => {
      wrapper = createWrapper();
      await nextTick();

      const tables = wrapper.findAll('[data-testid="simple-data-table"]');
      expect(tables.length).toBe(2);
    });
  });

  describe('previous-duty deletion self-lockout guard', () => {
    const invokeDeleteCell = () => {
      const deleteColumn = capturedColumns.previous?.find((c: any) => c.id === 'delete');
      expect(deleteColumn).toBeDefined();
      const vnode = deleteColumn!.cell({ row: { original: { pivot: { id: 'dutiable-3' } } } }) as any;
      // The rendered Button vnode carries the onClick handler.
      vnode.props.onClick();
    };

    it('routes the delete through the guard with acknowledge_access_change = false', async () => {
      wrapper = createWrapper();
      await nextTick();

      invokeDeleteCell();

      expect(router.delete).toHaveBeenCalledTimes(1);
      expect(router.delete).toHaveBeenCalledWith(
        expect.stringContaining('/mocked-route/dutiables.destroy'),
        expect.objectContaining({
          data: { acknowledge_access_change: false },
          preserveState: true,
          preserveScroll: true,
        }),
      );
    });

    it('renders the access-change warning dialog', async () => {
      wrapper = createWrapper();
      await nextTick();

      expect(wrapper.findComponent(AccessChangeWarningDialog).exists()).toBe(true);
    });
  });
});
