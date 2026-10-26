import { describe, expect, it } from 'vitest';

import { fieldOptions, initialValue, localizedLabel, type RegistrationFieldDefinition } from '../types';

const field: RegistrationFieldDefinition = { id: 1, type: 'string', label: 'Answer', is_required: false };

describe('registration field values', () => {
  it('resolves scalar, numeric and translated labels independently', () => {
    expect(localizedLabel('Tenant', 'en')).toBe('Tenant');
    expect(localizedLabel({ lt: 1, en: 2 }, 'en')).toBe('2');
    expect(localizedLabel({ lt: 'Veikla', en: 'Activity' }, 'en')).toBe('Activity');
    expect(localizedLabel({ lt: 'Veikla' }, 'en')).toBe('Veikla');
    expect(localizedLabel(null, 'lt')).toBe('');
  });

  it('keeps option values separate from duplicate localized labels', () => {
    const choice = { ...field, type: 'enum', options: [{ value: 0, label: { lt: 'Tas pats', en: 'Same' } }, { value: 2, label: { lt: 'Tas pats', en: 'Same' } }] };
    expect(initialValue(choice, 'en', { value: '2' })).toBe(2);
    expect(initialValue(choice, 'lt', { value: 0 })).toBe(0);
    expect(fieldOptions(choice).map(option => option.value)).toEqual([0, 2]);
  });

  it('preserves legacy scalar options and label-based defaults', () => {
    expect(fieldOptions({ ...field, type: 'select', options: ['A', 0] })).toEqual([{ value: 'A', label: 'A' }, { value: 0, label: 0 }]);
    expect(initialValue({ ...field, type: 'enum', default_value: { lt: 'Pirmas' }, options: [{ value: 3, label: { lt: 'Pirmas' } }] }, 'lt')).toBe(3);
  });

  it('initializes numbers and booleans without discarding falsy values', () => {
    expect(initialValue({ ...field, type: 'number', default_value: 0 }, 'lt')).toBe(0);
    expect(initialValue({ ...field, type: 'boolean', default_value: false }, 'lt')).toBe(false);
    expect(initialValue({ ...field, type: 'boolean', default_value: { lt: 'false' } }, 'lt')).toBe(false);
    expect(initialValue({ ...field, type: 'boolean', default_value: { lt: '1' } }, 'lt')).toBe(true);
    expect(initialValue(field, 'lt')).toBeNull();
  });
});
