import { effectScope, reactive } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import type { InertiaForm } from '@inertiajs/vue3';

import { createVote, useAgendaItemAutosave, type AgendaItemFormData, type EditableVote } from '../useAgendaItemAutosave';

interface PatchOptions {
  onSuccess: (page?: { props: { flash: { data: unknown } } }) => void;
  onFinish: () => void;
}

/** Just enough of an Inertia form to drive one request at a time by hand. */
function fakeForm() {
  const requests: PatchOptions[] = [];
  const form = reactive({
    type: null as AgendaItemFormData['type'],
    votes: [] as EditableVote[],
    isDirty: false,
    processing: false,
    data() {
      return { type: form.type, votes: form.votes };
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

  const respond = (success = true, flashData: unknown = null) => {
    const options = requests.at(-1)!;
    if (success) {
      options.onSuccess({ props: { flash: { data: flashData } } });
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
  it('leaves the given deferred props out of the reload after a save, so their panels keep the scroll', () => {
    const { form } = fakeForm();
    const autosave = effectScope().run(() => useAgendaItemAutosave(form as unknown as InertiaForm<AgendaItemFormData>, 'item-1', 700, ['goalLinks']))!;

    form.type = 'voting';
    form.isDirty = true;
    autosave.submit();

    expect(form.patch).toHaveBeenCalledWith(expect.any(String), expect.objectContaining({ preserveScroll: true, except: ['goalLinks'] }));
  });

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
    expect(form.defaults).toHaveBeenLastCalledWith({ type: 'informational', votes: [] });
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

  it('keeps the ids the server gave new votes, so the next save updates them instead of recreating them', () => {
    const { form, autosave, respond, requests } = setup();
    form.votes = [{ ...createVote(), id: 'vote-old' }, createVote()];
    form.isDirty = true;
    autosave.submit();

    respond(true, { voteIds: ['vote-old', 'vote-new'] });

    expect(form.votes.map(vote => vote.id)).toEqual(['vote-old', 'vote-new']);
    expect(form.defaults).toHaveBeenLastCalledWith(expect.objectContaining({
      votes: [expect.objectContaining({ id: 'vote-old' }), expect.objectContaining({ id: 'vote-new' })],
    }));
    expect(requests).toHaveLength(1);
  });

  it('gives a vote added while a save was in flight no id of its own, and saves it next', () => {
    const { form, autosave, respond, requests } = setup();
    form.votes = [createVote()];
    form.isDirty = true;
    autosave.submit();

    form.votes.push(createVote());
    respond(true, { voteIds: ['vote-first'] });

    expect(form.votes.map(vote => vote.id)).toEqual(['vote-first', null]);
    expect(requests).toHaveLength(2);
  });
});
