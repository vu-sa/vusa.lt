import type { DocumentFolderRow } from './types';

import { DocumentStatus } from '@/Types/enums';

/**
 * Published without a link: `pending` while the queued sync may still create it, `failed` once it
 * finished without one — refused by SharePoint, or never attempted — so a retry is offered.
 */
export function linkState(file: Pick<DocumentFolderRow, 'status' | 'public_url' | 'sync_status'>): 'failed' | 'pending' | null {
  if (file.status !== DocumentStatus.Published || file.public_url) return null;

  return file.sync_status === 'pending' || file.sync_status === 'syncing' ? 'pending' : 'failed';
}
