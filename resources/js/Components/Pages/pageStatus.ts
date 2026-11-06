import { contentStatuses, type StatusPresentation } from '@/Constants/statusIcons';
import type { PageSearchResult } from '@/Shared/Search/types';

export function pageStatus(page: PageSearchResult): StatusPresentation {
  return page.is_active ? contentStatuses.published : contentStatuses.draft;
}
