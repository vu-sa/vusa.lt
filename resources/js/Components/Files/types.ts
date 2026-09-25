export interface FileableFileItem {
  id: string;
  name: string;
  file_type?: string | null;
  file_date?: string | null;
  formatted_size?: string | null;
  /** Loaded for type reference files, whose type title names where they come from. */
  fileable?: { id: string | number; title?: string | null } | null;
}
