import type { Component } from 'vue';

import IFluentCode24Regular from '~icons/fluent/code-24-regular';
import IFluentDocument24Regular from '~icons/fluent/document-24-regular';
import IFluentDocumentPdf24Regular from '~icons/fluent/document-pdf-24-regular';
import IFluentDocumentTable24Regular from '~icons/fluent/document-table-24-regular';
import IFluentDocumentText24Regular from '~icons/fluent/document-text-24-regular';
import IFluentFolder24Filled from '~icons/fluent/folder-24-filled';
import IFluentFolderZip24Regular from '~icons/fluent/folder-zip-24-regular';
import IFluentImage24Regular from '~icons/fluent/image-24-regular';
import IFluentMusicNote24Regular from '~icons/fluent/music-note-2-24-regular';
import IFluentVideo24Regular from '~icons/fluent/video-24-regular';

export type FileKind
  = | 'folder'
    | 'image'
    | 'pdf'
    | 'document'
    | 'spreadsheet'
    | 'video'
    | 'audio'
    | 'archive'
    | 'code'
    | 'other';

/**
 * Extensions the file manager treats as images. Exported because callers also use it to
 * decide whether a tile can show a thumbnail, not only which icon to draw.
 */
export const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'ico'];

const EXTENSIONS_BY_KIND: Record<Exclude<FileKind, 'folder' | 'other'>, string[]> = {
  image: IMAGE_EXTENSIONS,
  pdf: ['pdf'],
  document: ['doc', 'docx', 'odt', 'txt', 'rtf'],
  spreadsheet: ['xls', 'xlsx', 'csv', 'ods'],
  video: ['mp4', 'avi', 'mkv', 'mov', 'webm', 'wmv', 'flv', 'm4v'],
  audio: ['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a', 'wma'],
  archive: ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'],
  code: ['js', 'ts', 'vue', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'h', 'json', 'xml', 'yml', 'yaml'],
};

const ICONS_BY_KIND: Record<FileKind, Component> = {
  folder: IFluentFolder24Filled,
  image: IFluentImage24Regular,
  pdf: IFluentDocumentPdf24Regular,
  document: IFluentDocumentText24Regular,
  spreadsheet: IFluentDocumentTable24Regular,
  video: IFluentVideo24Regular,
  audio: IFluentMusicNote24Regular,
  archive: IFluentFolderZip24Regular,
  code: IFluentCode24Regular,
  other: IFluentDocument24Regular,
};

/** Lowercase extension without the dot, or '' when the name carries none. */
export function getFileExtension(nameOrPath: string): string {
  if (!nameOrPath) return '';
  const name = nameOrPath.split('/').pop() ?? '';
  const parts = name.split('.');
  return parts.length > 1 ? (parts.pop() ?? '').toLowerCase() : '';
}

export function getFileKind(nameOrPath: string): FileKind {
  const extension = getFileExtension(nameOrPath);
  if (!extension) return 'other';

  for (const [kind, extensions] of Object.entries(EXTENSIONS_BY_KIND)) {
    if (extensions.includes(extension)) {
      return kind as FileKind;
    }
  }

  return 'other';
}

export function isImageFile(nameOrPath: string): boolean {
  return getFileKind(nameOrPath) === 'image';
}

/**
 * Resolve the icon component for a name or path. Returns a component, never a component
 * *name* — `<component :is>` silently renders nothing for an unregistered name string.
 */
export function getFileIcon(nameOrPath: string, isFolder = false): Component {
  return isFolder ? ICONS_BY_KIND.folder : ICONS_BY_KIND[getFileKind(nameOrPath)];
}

export function formatFileSize(bytes?: number | null): string {
  if (!bytes) return '—';
  const units = ['B', 'KB', 'MB', 'GB'];
  const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
  const value = bytes / 1024 ** exponent;
  return `${exponent === 0 ? value : parseFloat(value.toFixed(1))} ${units[exponent]}`;
}
