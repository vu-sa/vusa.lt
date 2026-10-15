import { contentStatuses, type StatusPresentation } from '@/Constants/statuses';
import type { NewsSearchResult } from '@/Shared/Search/types';

/** A published article waits in "Suplanuota" until its publish time arrives. */
export function newsStatus(news: NewsSearchResult, now = Date.now()): StatusPresentation {
  if (news.draft) {
    return contentStatuses.draft;
  }

  if (news.publish_time && news.publish_time * 1000 > now) {
    return contentStatuses.scheduled;
  }

  return contentStatuses.published;
}
