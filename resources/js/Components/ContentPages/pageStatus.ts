import { contentStatuses, type StatusPresentation } from '@/Constants/statusIcons';
import type { PageSearchResult } from '@/Search/core/types';

export function pageStatus(page: PageSearchResult): StatusPresentation {
  return page.is_active ? contentStatuses.published : contentStatuses.draft;
}
