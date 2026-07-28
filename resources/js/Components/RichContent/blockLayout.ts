import { getContentType, type BlockWidth } from './Types';

const WIDTH_CLASS: Record<BlockWidth, string> = {
  prose: '',
  content: 'rc-content',
  wide: 'rc-wide',
  full: 'rc-full',
};

export interface LayoutableElement {
  type: string;
  options?: Record<string, unknown> | null;
}

/**
 * Resolve a block's canvas column + flow classes from its type's registry defaults and
 * `options.width` override. The single implementation behind `RichContentParser`,
 * `ContentEditorFactory`'s per-block preview, `RichContentEditor`'s insert-preview and
 * `BlockPickerDialog` — before this existed, only the parser applied it, so every other
 * preview surface silently clamped `full`/`wide` blocks to the prose column regardless
 * of what the author picked.
 */
export function blockLayoutClasses(element: LayoutableElement): string[] {
  const contentType = getContentType(element.type);
  const width = (element.options?.width as BlockWidth | undefined) ?? contentType.defaultWidth;
  const widthClass = WIDTH_CLASS[width];
  const flushClass = contentType.selfSpaced ? 'rc-flush' : '';
  return [widthClass, flushClass].filter(Boolean);
}
