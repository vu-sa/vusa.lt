import { describe, expect, it } from 'vitest';

import {
  entityTypeRegistry,
  getEntityTypeDefinition,
  getModelIcon,
} from '@/Constants/entityTypes';
import { ModelEnum } from '@/Types/enums';

describe('entity type registry', () => {
  it('contains exactly one definition for every model enum value', () => {
    expect(Object.keys(entityTypeRegistry).sort()).toEqual(Object.values(ModelEnum).sort());
  });

  it('keeps the eight primary entity assignments stable', () => {
    expect(entityTypeRegistry[ModelEnum.MEETING].category).toBe(1);
    expect(entityTypeRegistry[ModelEnum.RESERVATION].category).toBe(2);
    expect(entityTypeRegistry[ModelEnum.USER].category).toBe(3);
    expect(entityTypeRegistry[ModelEnum.DUTY].category).toBe(4);
    expect(entityTypeRegistry[ModelEnum.NEWS].category).toBe(5);
    expect(entityTypeRegistry[ModelEnum.CALENDAR].category).toBe(6);
    expect(entityTypeRegistry[ModelEnum.DOCUMENT].category).toBe(7);
    expect(entityTypeRegistry[ModelEnum.INSTITUTION].category).toBe(8);
  });

  it('resolves both backend values and generated enum keys', () => {
    expect(getEntityTypeDefinition('meeting')).toBe(entityTypeRegistry[ModelEnum.MEETING]);
    expect(getEntityTypeDefinition('MEETING')).toBe(entityTypeRegistry[ModelEnum.MEETING]);
    expect(getEntityTypeDefinition('missing')).toBeNull();
  });

  it('keeps the compatibility icon helper backed by the registry', () => {
    expect(getModelIcon('MEETING')).toBe(entityTypeRegistry[ModelEnum.MEETING].icon);
    expect(getModelIcon('MEETING', 'filled')).toBe(entityTypeRegistry[ModelEnum.MEETING].iconFilled);
  });
});
