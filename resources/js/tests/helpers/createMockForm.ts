import { reactive } from 'vue';
import { vi } from 'vitest';

/**
 * Factory for creating reactive mock Inertia form objects.
 *
 * Use this in component tests that mount AdminForm (or any component
 * expecting an InertiaForm instance). The returned object is fully
 * reactive, so mutations like `form.processing = true` trigger Vue
 * reactivity updates inside the mounted component.
 */
export interface MockForm extends Record<string, any> {
  processing: boolean;
  recentlySuccessful: boolean;
  isDirty: boolean;
  hasErrors: boolean;
  errors: Record<string, string>;
  data: () => Record<string, any>;
  post: ReturnType<typeof vi.fn>;
  patch: ReturnType<typeof vi.fn>;
  put: ReturnType<typeof vi.fn>;
  delete: ReturnType<typeof vi.fn>;
  transform: ReturnType<typeof vi.fn>;
  reset: ReturnType<typeof vi.fn>;
  clearErrors: ReturnType<typeof vi.fn>;
  setError: ReturnType<typeof vi.fn>;
  defaults: ReturnType<typeof vi.fn>;
}

export function createMockForm(initialData: Record<string, any> = {}): MockForm {
  const form = reactive({
    ...initialData,
    processing: false,
    recentlySuccessful: false,
    isDirty: false,
    hasErrors: false,
    errors: {},
    data() {
      return { ...this };
    },
    post: vi.fn(),
    patch: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    transform: vi.fn(),
    reset: vi.fn(),
    clearErrors: vi.fn(),
    setError: vi.fn(),
    defaults: vi.fn(),
  });

  return form as MockForm;
}
