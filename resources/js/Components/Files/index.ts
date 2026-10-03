/**
 * File/document components driven by a polymorphic `fileable` ({ id, type }),
 * so they work for any model the SharePoint integration knows about.
 */

export { default as FileableFilesPanel } from './FileableFilesPanel.vue';
export { default as FileableUploadSheet } from './FileableUploadSheet.vue';
export { default as ReferenceDocumentTiles } from './ReferenceDocumentTiles.vue';
export type { FileableFileItem } from './types';
