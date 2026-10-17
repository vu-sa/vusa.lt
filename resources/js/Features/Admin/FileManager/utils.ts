import type { FileEntry, TypeFilter } from './types';

import { EXTENSIONS_BY_KIND, getFileExtension, getFileKind } from '@/Utils/fileIcons';

export const ROOT_PATH = 'public/files';

/** Formats the server can resize (FileApiController::THUMBNAILABLE_EXTENSIONS). */
const THUMBNAILABLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

/** Images a browser renders as-is; the rest of IMAGE_EXTENSIONS (tiff, ico…) get an icon. */
const PREVIEWABLE_IMAGE_EXTENSIONS = [...THUMBNAILABLE_EXTENSIONS, 'gif', 'svg', 'avif'];

export function formatBytes(bytes?: number | null): string {
  if (bytes == null || isNaN(bytes)) return '—';
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  const idx = Math.min(i, sizes.length - 1);
  return `${Number.parseFloat((bytes / Math.pow(k, idx)).toFixed(idx > 1 ? 1 : 0))} ${sizes[idx]}`;
}

export function formatDate(timestamp: number | string | Date): string {
  if (!timestamp) return '—';
  const date = typeof timestamp === 'number'
    ? (timestamp < 10000000000 ? new Date(timestamp * 1000) : new Date(timestamp))
    : new Date(timestamp);

  if (isNaN(date.getTime())) return '—';

  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

export function publicUrl(path: string): string {
  return `/uploads/${path.replace(/^public\//, '')}`;
}

export function isPreviewableImage(nameOrPath: string): boolean {
  return PREVIEWABLE_IMAGE_EXTENSIONS.includes(getFileExtension(nameOrPath));
}

export function isThumbnailable(nameOrPath: string): boolean {
  return THUMBNAILABLE_EXTENSIONS.includes(getFileExtension(nameOrPath));
}

/** A resized copy when the server can make one, otherwise the original. */
export function imageSrc(path: string, width: 160 | 320 | 640 | 1600 | 2400): string {
  return isThumbnailable(path)
    ? route('api.v1.admin.files.thumbnail', { path, w: width })
    : publicUrl(path);
}

export function matchesTypeFilter(file: FileEntry, filter: TypeFilter): boolean {
  if (filter === 'all') return true;
  if (filter === 'folder') return false;
  return getFileKind(file.name) === filter;
}

/** Filter options in menu order; `extensions` is what the menu shows under each label. */
export const TYPE_FILTER_KINDS = [
  'image',
  'pdf',
  'document',
  'spreadsheet',
  'video',
  'audio',
  'archive',
  'code',
  'other',
] as const satisfies readonly Exclude<TypeFilter, 'all' | 'folder'>[];

export function extensionsForKind(kind: Exclude<TypeFilter, 'all' | 'folder'>): string[] {
  return kind === 'other' ? [] : EXTENSIONS_BY_KIND[kind];
}

export function parentPath(path: string): string {
  const parent = path.split('/').slice(0, -1).join('/');
  return parent.length < ROOT_PATH.length ? ROOT_PATH : parent;
}

/** Path below the root, for display ("" at the root). */
export function relativeToRoot(path: string): string {
  return path.replace(/^public\/files\/?/, '');
}

export type PreviewKind = 'image' | 'pdf' | 'video' | 'audio';

/** How the preview shows a file, or null when only a download makes sense. */
export function previewKind(name: string): PreviewKind | null {
  if (isPreviewableImage(name)) return 'image';
  const kind = getFileKind(name);
  return kind === 'pdf' || kind === 'video' || kind === 'audio' ? kind : null;
}
