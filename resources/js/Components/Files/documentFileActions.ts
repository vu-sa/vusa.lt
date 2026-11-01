import { trans as $t } from 'laravel-vue-i18n';
import { ExternalLink, Folder, Pencil, Trash2 } from '@lucide/vue';

import type { DocumentFolderRow } from './types';

import type { AdminRecordAction } from '@/Features/Admin/RecordActions/types';

/** A document opens in its editor; without edit rights its public or SharePoint link takes the slot. */
export function documentFileActions(file: DocumentFolderRow): AdminRecordAction[] {
  const removed = Boolean(file.removed_from_sharepoint_at);
  const canEdit = file.can.update && !removed;
  const cleanup = removed && file.can.update;
  const publicLeads = Boolean(file.public_url) && !canEdit && !cleanup;
  const sharepointLeads = Boolean(file.sharepoint_web_url) && !removed && !canEdit && !file.public_url;

  return [
    ...(canEdit ? [{ key: 'edit', role: 'edit' as const, primary: true, label: $t('Redaguoti'), icon: Pencil, href: route('documents.edit', file.id) }] : []),
    ...(file.public_url ? [{ key: 'public', role: publicLeads ? 'open' as const : undefined, primary: publicLeads, label: $t('Atidaryti vusa.lt nuorodą'), icon: ExternalLink, href: file.public_url, external: true }] : []),
    ...(file.sharepoint_web_url && !removed ? [{ key: 'sharepoint', role: sharepointLeads ? 'open' as const : undefined, primary: sharepointLeads, label: $t('Atidaryti SharePoint'), icon: Folder, href: file.sharepoint_web_url, external: true }] : []),
    ...(cleanup ? [{ key: 'delete', label: $t('Ištrinti'), icon: Trash2, destructive: true, primary: true }] : []),
  ];
}
