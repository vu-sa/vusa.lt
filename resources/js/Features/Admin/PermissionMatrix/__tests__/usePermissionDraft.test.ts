import { describe, expect, it } from 'vitest';
import { ref } from 'vue';

import { usePermissionDraft } from '../usePermissionDraft';

describe('usePermissionDraft', () => {
  it('replaces every scope of the edited ability and nothing else', () => {
    const draft = usePermissionDraft(ref(['news.update.own', 'news.update.padalinys', 'pages.read.*']));

    expect(draft.scopeOf('news', 'update')).toBe('padalinys');

    draft.setScope('news', 'update', '*');

    expect(draft.names.value).toEqual(['news.update.*', 'pages.read.*']);
    expect(draft.added.value).toEqual(['news.update.*']);
    expect(draft.removed.value).toEqual(['news.update.own', 'news.update.padalinys']);
  });

  it('keeps an untouched multi-scope ability exactly as saved', () => {
    const draft = usePermissionDraft(ref(['news.update.own', 'news.update.padalinys']));

    draft.setScope('pages', 'read', 'padalinys');

    expect(draft.names.value).toContain('news.update.own');
    expect(draft.names.value).toContain('news.update.padalinys');
  });

  it('clears a cell and resets back to the saved set', () => {
    const draft = usePermissionDraft(ref(['tasks.read.own']));

    draft.setScope('tasks', 'read', null);
    expect(draft.isDirty.value).toBe(true);
    expect(draft.scopeOf('tasks', 'read')).toBeNull();

    draft.reset();
    expect(draft.isDirty.value).toBe(false);
  });
});
