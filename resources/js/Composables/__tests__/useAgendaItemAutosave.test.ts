import { effectScope, reactive } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import type { InertiaForm } from '@inertiajs/vue3';

import { useAgendaItemAutosave, type AgendaItemFormData } from '../useAgendaItemAutosave';

interface PatchOptions {
  onSuccess: () => void;
  onFinish: () => void;
}

/** Just enough of an Inertia form to drive one request at a time by hand. */
function fakeForm() {
  const requests: PatchOptions[] = [];
  const form = reactive({
    type: null as AgendaItemFormData['type'],
    isDirty: false,
    processing: false,
    data() {
      return { type: form.type };
    },
    transform() {
      return form;
    },
    defaults: vi.fn(),
    patch: vi.fn((_url: string, options: PatchOptions) => {
      form.processing = true;
      requests.push(options);
    }),
  });

  const respond = (success = true) => {
    const options = requests.at(-1)!;
    if (success) {
      options.onSuccess();
    }
    form.processing = false;
    options.onFinish();
  };

  return { form, requests, respond };
}

const setup = () => {
  const fake = fakeForm();
  const scope = effectScope();
  const autosave = scope.run(() => useAgendaItemAutosave(fake.form as unknown as InertiaForm<AgendaItemFormData>, 'item-1'))!;

  return { ...fake, autosave };
};

describe('useAgendaItemAutosave', () => {
  it('navigates only after a save that was already in flight has landed', () => {
    const { form, autosave, respond } = setup();
    const navigate = vi.fn();

    form.type = 'voting';
    form.isDirty = true;
    autosave.submit();
    autosave.saveThen(navigate);

    expect(navigate).not.toHaveBeenCalled();

    respond();

    expect(navigate).toHaveBeenCalledOnce();
  });

  it('saves again an edit made while the previous save was in flight, before navigating', () => {
    const { form, autosave, respond, requests } = setup();
    const navigate = vi.fn();

    form.type = 'voting';
    form.isDirty = true;
    autosave.submit();

    form.type = 'informational';
    autosave.saveThen(navigate);
    respond();

    expect(requests).toHaveLength(2);
    expect(navigate).not.toHaveBeenCalled();

    respond();

    expect(navigate).toHaveBeenCalledOnce();
    expect(form.defaults).toHaveBeenLastCalledWith({ type: 'informational' });
  });

  it('stays on a save the server rejected', () => {
    const { form, autosave, respond } = setup();
    const navigate = vi.fn();

    form.type = 'voting';
    form.isDirty = true;
    autosave.saveThen(navigate);
    respond(false);

    expect(navigate).not.toHaveBeenCalled();
  });
});
