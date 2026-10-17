/**
 * Base interface for content part data structure
 * Matches the backend ContentPart model
 */
export interface ContentPart<
  TContent = Record<string, any>,
  TOptions = Record<string, any>,
> {
  id?: number;
  type: string;
  json_content: TContent;
  options?: TOptions;
  key?: string;
  order?: number;
}
