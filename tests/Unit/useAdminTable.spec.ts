import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';

// Mock Vue's provide function
vi.mock('vue', async () => {
  const actual = await vi.importActual('vue');
  return {
    ...actual,
    provide: vi.fn(),
  };
});

// Mock the router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    reload: vi.fn()
  }
}));

// Import after mocking
import { useAdminTable } from '@/Composables/useTableState';
import { router } from '@inertiajs/vue3';

describe('useAdminTable composable', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('initializes with default values', () => {
    const { sorters, filters, loading, searchText, showSoftDeleted } = useAdminTable({
      modelName: 'test',
      tableColumns: () => []
    });

    expect(sorters.value).toEqual({});
    expect(filters.value).toEqual({});
    expect(loading.value).toBe(false);
    expect(searchText.value).toBe('');
    expect(showSoftDeleted.value).toBe(false);
  });

  it('initializes with provided values', () => {
    const initialSorters = { name: 'ascend' };
    const initialFilters = { 'type.id': [1, 2] };
    
    const { sorters, filters } = useAdminTable({
      modelName: 'test',
      initialSorters,
      initialFilters,
      tableColumns: () => []
    });

    expect(sorters.value).toEqual(initialSorters);
    expect(filters.value).toEqual(initialFilters);
  });

  it('generates columns using the provided builder function', () => {
    const mockColumns = [{ key: 'name', title: 'Name' }];
    const builder = vi.fn(() => mockColumns);

    const { columns } = useAdminTable({
      modelName: 'test',
      tableColumns: builder
    });
    
    // Access columns.value to trigger the computation
    const result = columns.value;
    
    expect(builder).toHaveBeenCalled();
    expect(result).toEqual(mockColumns);
  });

  it('encodes table state as JSON strings', () => {
    const initialSorters = { name: 'ascend' };
    const initialFilters = { 'type.id': [1, 2] };
    
    const { encodeTableState } = useAdminTable({
      modelName: 'test',
      initialSorters,
      initialFilters,
      tableColumns: () => []
    });

    const state = encodeTableState();
    
    expect(state.sorters).toBe(JSON.stringify(initialSorters));
    expect(state.filters).toBe(JSON.stringify(initialFilters));
    expect(state.page).toBe(1);
  });

  it('calls router.reload with correct params when reloadData is called', () => {
    const initialSorters = { name: 'ascend' };
    const initialFilters = { 'type.id': [1, 2] };
    
    const { reloadData } = useAdminTable({
      modelName: 'testModel',
      initialSorters,
      initialFilters,
      tableColumns: () => []
    });

    reloadData(2);

    expect(router.reload).toHaveBeenCalledWith({
      data: {
        page: 2,
        sorters: JSON.stringify(initialSorters),
        filters: JSON.stringify(initialFilters)
      },
      only: ['testModel'],
      onSuccess: expect.any(Function),
      onError: expect.any(Function)
    });
  });

  it('updates filters and sorters correctly', () => {
    const { updateSorter, updateFilter, sorters, filters } = useAdminTable({
      modelName: 'test',
      tableColumns: () => []
    });

    updateSorter('name', 'descend');
    expect(sorters.value.name).toBe('descend');

    updateFilter('status', ['active', 'pending']);
    expect(filters.value.status).toEqual(['active', 'pending']);
  });

  it('resets table state correctly', () => {
    const initialSorters = { name: 'ascend', date: 'descend' };
    const initialFilters = { 'type.id': [1, 2], status: ['active'] };
    
    const { resetTableState, sorters, filters } = useAdminTable({
      modelName: 'test',
      initialSorters,
      initialFilters,
      tableColumns: () => []
    });

    // Mock router.reload to prevent actual calls
    const reloadSpy = vi.spyOn(router, 'reload').mockImplementation(() => ({} as any));

    resetTableState();

    // Verify sorters are reset to false
    expect(sorters.value).toEqual({ name: false, date: false });
    
    // Verify filters are cleared (empty arrays)
    expect(filters.value['type.id']).toEqual([]);
    expect(filters.value.status).toEqual([]);
    
    // Verify reload was called
    expect(reloadSpy).toHaveBeenCalled();
  });
});