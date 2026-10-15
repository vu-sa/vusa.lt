import { useDebouncedQuery } from '@/Composables/useDebouncedQuery';
import type { DuplicateUserMatch } from '@/Components/AdminForms/DuplicateUserWarning.vue';

/**
 * Looks for an existing account for the person being created.
 *
 * There should be one record per person, but only the unique email enforces that, so
 * the same student keeps getting re-created by a second unit — and merging the copies
 * afterwards needs `users.update.*`, which unit coordinators do not have. Catching it
 * at the point of creation is far cheaper than cleaning it up.
 *
 * Advisory only: the caller shows the matches, never blocks on them.
 */
export function useDuplicateUserCheck(name: () => string, email: () => string) {
  const { data: matches, isChecking, check } = useDebouncedQuery<DuplicateUserMatch[]>({
    url: () => {
      const currentName = (name() ?? '').trim();
      const currentEmail = (email() ?? '').trim();

      // Matching needs two name parts (see UserSimilarityFinder), so a half-typed name
      // can only ever come back empty — don't spend a request on it. An email alone is
      // still worth asking about, since it can match exactly.
      const hasFullName = currentName.split(/\s+/).filter(part => part.length >= 3).length >= 2;

      if (!hasFullName && currentEmail.length < 3) {
        return null;
      }

      const params = new URLSearchParams();
      if (currentName) params.set('name', currentName);
      if (currentEmail) params.set('email', currentEmail);

      return `${route('api.v1.admin.users.similar')}?${params.toString()}`;
    },
    watchSources: [() => name(), () => email()],
    debounceMs: 500,
    initialValue: [],
  });

  return { matches, isChecking, check };
}
