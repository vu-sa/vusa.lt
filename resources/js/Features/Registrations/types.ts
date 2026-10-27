export type RegistrationValue = string | number | boolean | null;
export type LocalizedLabel = string | number | Record<string, string | number | null> | null;

export interface RegistrationOption {
  value: string | number;
  label: LocalizedLabel;
}

export interface RegistrationFieldDefinition {
  id: number | string;
  label: LocalizedLabel;
  description?: LocalizedLabel;
  placeholder?: LocalizedLabel;
  default_value?: LocalizedLabel | boolean;
  type: string;
  subtype?: string | null;
  is_required: boolean;
  option_source?: 'tenant' | 'institution' | null;
  options?: Array<RegistrationOption | string | number> | null;
}

export interface RegistrationDefinition {
  id: string | number;
  name: string;
  description?: string | null;
  form_fields: RegistrationFieldDefinition[];
}

export interface PrefilledValue {
  value: string | number | boolean;
  hidden?: boolean;
}

export function localizedLabel(label: LocalizedLabel | undefined, locale: string): string {
  if (label === null || label === undefined) return '';
  if (typeof label !== 'object') return String(label);
  return String(label[locale] ?? label.lt ?? Object.values(label).find(value => value !== null) ?? '');
}

export function fieldOptions(field: RegistrationFieldDefinition): RegistrationOption[] {
  return (field.options ?? []).map(option => typeof option === 'object' ? option : { value: option, label: option });
}

export function initialValue(field: RegistrationFieldDefinition, locale: string, prefill?: PrefilledValue): RegistrationValue {
  const value = prefill?.value ?? (typeof field.default_value === 'boolean' ? field.default_value : localizedLabel(field.default_value, locale));
  if (field.type === 'boolean') return [true, 'true', 1, '1'].includes(value);
  if (value === '') return null;
  if (field.type === 'number') return Number.isFinite(Number(value)) ? Number(value) : String(value);
  if (['enum', 'select'].includes(field.type)) {
    const options = fieldOptions(field);
    const option = options.find(option => String(option.value) === String(value))
      ?? (!prefill ? options.find(option => localizedLabel(option.label, locale) === String(value)) : undefined);
    return option?.value ?? value;
  }
  return String(value);
}
