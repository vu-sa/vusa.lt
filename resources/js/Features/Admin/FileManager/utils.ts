import type { FileEntry, FileKind, TypeFilter } from './types';

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

export function getFileKind(name: string, mimeType?: string): FileKind {
  const ext = (name || '').split('.').pop()?.toLowerCase() || '';

  if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif'].includes(ext) || mimeType?.startsWith('image/')) {
    return 'image';
  }
  if (['pdf', 'doc', 'docx', 'odt', 'txt', 'rtf'].includes(ext) || mimeType?.includes('pdf') || mimeType?.includes('word')) {
    return 'document';
  }
  if (['xls', 'xlsx', 'ods', 'csv'].includes(ext) || mimeType?.includes('sheet') || mimeType?.includes('excel')) {
    return 'spreadsheet';
  }
  if (['mp4', 'webm', 'mov', 'avi', 'mkv'].includes(ext) || mimeType?.startsWith('video/')) {
    return 'video';
  }
  if (['mp3', 'wav', 'ogg', 'm4a', 'flac'].includes(ext) || mimeType?.startsWith('audio/')) {
    return 'audio';
  }
  if (['zip', 'rar', '7z', 'tar', 'gz'].includes(ext) || mimeType?.includes('zip') || mimeType?.includes('archive')) {
    return 'archive';
  }
  return 'other';
}

export function matchesTypeFilter(file: FileEntry, filter: TypeFilter): boolean {
  if (filter === 'all') return true;
  const kind = getFileKind(file.name, file.mimeType);
  if (filter === 'image') return kind === 'image';
  if (filter === 'document') return kind === 'document' || kind === 'spreadsheet' || kind === 'archive';
  if (filter === 'media') return kind === 'video' || kind === 'audio';
  if (filter === 'folder') return false;
  return true;
}
