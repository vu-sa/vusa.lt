import type * as Inertia from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive, ref } from 'vue';

import { useSheetCrudForm } from '@/Composables/useSheetCrudForm';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

interface ItemForm {
  id?: number;
  name: { lt: string; en: string };
  notes: string;
}

interface ItemRecord {
  id?: number;
  name: { lt: string; en: string };
  notes: string | null;
}

describe('useSheetCrudForm', () => {
  beforeEach(async () => {
    vi.clearAllMocks();
    const inertia = await vi.importActual<typeof Inertia>('@inertiajs/vue3');
    vi.mocked(useForm).mockImplementation(inertia.useForm);
  });

  it('bridges open state through getter and callback', () => {
    const isOpen = ref(true);
    const onUpdateOpen = vi.fn((value: boolean) => {
      isOpen.value = value;
    });

    const { open } = useSheetCrudForm<ItemForm, ItemRecord>({
      open: () => isOpen.value,
      onUpdateOpen,
      onSaved: vi.fn(),
      record: () => null,
      blank: () => ({ name: { lt: '', en: '' }, notes: '' }),
      storeUrl: () => '/items',
      updateUrl: id => `/items/${id}`,
    });

    expect(open.value).toBe(true);
    open.value = false;
    expect(onUpdateOpen).toHaveBeenCalledWith(false);
    expect(isOpen.value).toBe(false);
  });

  it('posts a new record, clears dirtiness before the visit, and closes on success', () => {
    const isOpen = ref(true);
    const onUpdateOpen = vi.fn((value: boolean) => {
      isOpen.value = value;
    });
    const onSaved = vi.fn();

    const { form, submit } = useSheetCrudForm<ItemForm, ItemRecord>({
      open: () => isOpen.value,
      onUpdateOpen,
      onSaved,
      record: () => null,
      blank: () => ({ name: { lt: '', en: '' }, notes: '' }),
      storeUrl: () => '/items',
      updateUrl: id => `/items/${id}`,
      visitOptions: { headers: { 'X-Sheet': 'true' } },
    });

    form.name.lt = 'Naujas';
    expect(form.isDirty).toBe(true);

    let wasDirtyAtPost = true;
    const post = vi.spyOn(form, 'post').mockImplementation((_url, opts) => {
      wasDirtyAtPost = form.isDirty;
      (opts?.onSuccess as (() => void) | undefined)?.();
    });

    submit();

    expect(wasDirtyAtPost).toBe(false);
    expect(post).toHaveBeenCalledWith('/items', expect.objectContaining({
      preserveScroll: true,
      headers: { 'X-Sheet': 'true' },
    }));
    expect(onUpdateOpen).toHaveBeenCalledWith(false);
    expect(onSaved).toHaveBeenCalledTimes(1);
  });

  it('patches an existing record without mutating the reactive source', async () => {
    const source = reactive<ItemRecord>({
      id: 9,
      name: { lt: 'Pradinis', en: 'Initial' },
      notes: null,
    });
    const record = ref<ItemRecord | null>(source);

    const { form, submit, reset } = useSheetCrudForm<ItemForm, ItemRecord>({
      open: () => true,
      onUpdateOpen: vi.fn(),
      onSaved: vi.fn(),
      record: () => record.value,
      blank: () => ({ name: { lt: '', en: '' }, notes: '' }),
      fromRecord: raw => ({ id: raw.id, name: raw.name, notes: raw.notes ?? '' }),
      storeUrl: () => '/items',
      updateUrl: id => `/items/${id}`,
    });

    expect(form.name).toEqual({ lt: 'Pradinis', en: 'Initial' });
    expect(form.notes).toBe('');

    form.name.lt = 'Pakeistas';
    expect(source.name.lt).toBe('Pradinis');

    form.setError('notes', 'Klaida');
    reset();
    expect(form.name.lt).toBe('Pradinis');
    expect(form.errors.notes).toBeUndefined();

    const patch = vi.spyOn(form, 'patch').mockImplementation(() => {});
    submit();
    expect(patch).toHaveBeenCalledWith('/items/9', expect.objectContaining({ preserveScroll: true }));

    record.value = null;
    await nextTick();
    expect(form.name).toEqual({ lt: '', en: '' });
  });
});
