import { trans as $t } from 'laravel-vue-i18n';
import { Download, ExternalLink, Eye, FolderInput, Info, Link2, PencilLine, Star, StarOff, Trash2 } from '@lucide/vue';

import type { DirectoryEntry, FileEntry, FileManagerAbilities, ItemAction } from './types';
import { previewKind, publicUrl } from './utils';

export type FolderLike = Pick<DirectoryEntry, 'path' | 'name' | 'items'>;

/** A file's ⋯ menu, by what the reader may do with it. */
export function fileActionsFor(file: FileEntry, isStarred: boolean, abilities: FileManagerAbilities): ItemAction[] {
  const actions: ItemAction[] = [];

  if (previewKind(file.name) !== null) {
    actions.push({ key: 'preview', label: $t('files.ui.preview'), icon: Eye });
  }
  actions.push(
    { key: 'details', label: $t('files.ui.details'), icon: Info },
    { key: 'copyLink', label: $t('files.ui.copy_link'), icon: Link2 },
    { key: 'open', label: $t('files.ui.open_original'), icon: ExternalLink },
    { key: 'download', label: $t('files.ui.download'), icon: Download },
    { key: 'star', label: isStarred ? $t('files.ui.unstar') : $t('files.ui.star'), icon: isStarred ? StarOff : Star },
  );
  if (abilities.update) {
    actions.push(
      { key: 'rename', label: $t('files.ui.rename'), icon: PencilLine },
      { key: 'move', label: $t('files.ui.move'), icon: FolderInput },
    );
  }
  if (abilities.delete) {
    actions.push({ key: 'delete', label: $t('files.ui.delete'), icon: Trash2, destructive: true });
  }

  return actions;
}

/** A folder's ⋯ menu; a folder with contents cannot be deleted, so it gets a note instead. */
export function folderActionsFor(directory: FolderLike, abilities: FileManagerAbilities): { actions: ItemAction[]; note?: string } {
  const actions: ItemAction[] = [];
  let note: string | undefined;

  if (abilities.update) {
    actions.push({ key: 'rename', label: $t('files.ui.rename'), icon: PencilLine });
  }
  if (abilities.deleteDirectory) {
    if ((directory.items ?? 0) === 0) {
      actions.push({ key: 'delete', label: $t('files.ui.delete_folder'), icon: Trash2, destructive: true });
    }
    else {
      note = $t('files.ui.delete_folder_not_empty', { count: String(directory.items) });
    }
  }

  return { actions, note };
}

/** Browsers drop all but the first of several downloads started in one tick. */
export function downloadPaths(paths: string[]): void {
  paths.forEach((path, index) => {
    window.setTimeout(() => {
      const anchor = document.createElement('a');
      anchor.href = publicUrl(path);
      anchor.download = path.split('/').pop() ?? '';
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();
    }, index * 300);
  });
}
