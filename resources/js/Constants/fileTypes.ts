/**
 * Semantic types of a record's SharePoint files. `value` is what SharePoint's Type column and
 * `FileableFile::fileTypes()` store (plural); `label` names one file of it, as a `$t()` key.
 */
export const FILEABLE_FILE_TYPES = [
  { value: 'Protokolai', label: 'Protokolas' },
  { value: 'Ataskaitos', label: 'Ataskaita' },
  { value: 'Darbotvarkės', label: 'Darbotvarkė' },
  { value: 'Pristatymai', label: 'Pristatymas' },
  { value: 'Veiklą reglamentuojantys dokumentai', label: 'Reglamentuojantis dokumentas' },
  { value: 'Metodinė medžiaga', label: 'Metodinė medžiaga' },
  { value: 'Šablonai', label: 'Šablonas' },
  { value: 'Kita', label: 'Kita' },
] as const;

export type FileableFileType = typeof FILEABLE_FILE_TYPES[number]['value'];

export const MEETING_PRIMARY_FILE_TYPES: FileableFileType[] = ['Protokolai', 'Ataskaitos'];

export const FILEABLE_FILE_ACCEPT = '.pdf,.docx,.pptx,.xlsx';

export const fileTypeLabel = (value: string | null | undefined): string =>
  FILEABLE_FILE_TYPES.find(type => type.value === value)?.label ?? value ?? 'Kita';

const NAME_HINTS: Array<[RegExp, FileableFileType]> = [
  [/protokol/i, 'Protokolai'],
  [/ataskait|report/i, 'Ataskaitos'],
  [/darbotvark|agenda/i, 'Darbotvarkės'],
  [/(?:pristatym.*|.*\.pptx)$/i, 'Pristatymai'],
  [/šablon|sablon|template/i, 'Šablonai'],
];

export const guessFileType = (fileName: string): FileableFileType | null =>
  NAME_HINTS.find(([pattern]) => pattern.test(fileName))?.[1] ?? null;
