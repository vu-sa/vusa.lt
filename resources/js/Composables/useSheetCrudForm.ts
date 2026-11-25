import type { FormDataConvertible, FormDataType, UseFormSubmitOptions } from '@inertiajs/core';
import type { InertiaForm } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import type { WritableComputedRef } from 'vue';
import { computed, toRaw, watch } from 'vue';

export interface SheetCrudRecord {
  id?: string | number | null;
}

export interface UseSheetCrudFormOptions<
  TForm extends FormDataType<TForm>,
  TRecord extends SheetCrudRecord = TForm & SheetCrudRecord,
> {
  open: () => boolean;
  onUpdateOpen: (value: boolean) => void;
  onSaved: () => void;
  record: () => TRecord | null | undefined;
  blank: () => TForm;
  /** Maps a deep-cloned raw record into form defaults; defaults to using the clone directly. */
  fromRecord?: (record: TRecord) => TForm;
  storeUrl: () => string;
  updateUrl: (id: string | number) => string;
  visitOptions?: Omit<UseFormSubmitOptions, 'preserveScroll' | 'onSuccess'>;
}

export interface UseSheetCrudFormReturn<TForm extends FormDataType<TForm>> {
  open: WritableComputedRef<boolean>;
  form: InertiaForm<TForm>;
  reset: () => void;
  submit: () => void;
}

/**
 * Bridges SheetForm's open/record lifecycle with an Inertia form: clones records out of Vue
 * proxies before populating defaults, clears dirtiness synchronously before submitting, and
 * closes/emits on success.
 */
export function useSheetCrudForm<
  TForm extends FormDataType<TForm>,
  TRecord extends SheetCrudRecord = TForm & SheetCrudRecord,
>(options: UseSheetCrudFormOptions<TForm, TRecord>): UseSheetCrudFormReturn<TForm> {
  const open = computed({
    get: () => options.open(),
    set: (value: boolean) => options.onUpdateOpen(value),
  });

  // Widened on purpose: useForm's generic over a caller's form type exceeds tsc's instantiation depth.
  const createForm = useForm as unknown as (data: Record<string, FormDataConvertible>) => InertiaForm<TForm>;
  const form = createForm(options.blank() as unknown as Record<string, FormDataConvertible>);

  watch(options.record, (record) => {
    if (record) {
      const raw = structuredClone(toRaw(record));
      const values = options.fromRecord ? options.fromRecord(raw) : (raw as unknown as TForm);
      form.defaults(values as Partial<TForm>);
    }
    else {
      form.defaults(options.blank() as Partial<TForm>);
    }
    form.reset();
    form.clearErrors();
  }, { immediate: true });

  function reset(): void {
    form.reset();
    form.clearErrors();
  }

  function submit(): void {
    const submitOptions: UseFormSubmitOptions = {
      ...options.visitOptions,
      preserveScroll: true,
      onSuccess: () => {
        // Only a saved value becomes the reset point; a rejected save must stay dirty.
        form.defaults();
        open.value = false;
        options.onSaved();
      },
    };

    const id = options.record()?.id;
    if (id) {
      form.patch(options.updateUrl(id), submitOptions);
    }
    else {
      form.post(options.storeUrl(), submitOptions);
    }
  }

  return {
    open,
    form,
    reset,
    submit,
  };
}
