import { trans as $t } from 'laravel-vue-i18n';
import type { Component } from 'vue';
import { Settings2 } from '@lucide/vue';

import type { PermissionAbility, PermissionScope } from './types';

import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import { ModelEnum } from '@/Types/enums';
import { pluralizeModels } from '@/Utils/String';

export const ABILITIES: PermissionAbility[] = ['read', 'create', 'update', 'delete', 'forceDelete'];
export const SCOPES: PermissionScope[] = ['own', 'padalinys', '*'];

const camel = (value: string) => value.replace(/_([a-z])/g, (_, letter: string) => letter.toUpperCase());

/** `studyPrograms` → `study_program`, the way the backend pluralises ModelEnum labels. */
export function modelForResource(resource: string): string | null {
  return Object.values(ModelEnum).find(model => pluralizeModels(camel(model)) === resource) ?? null;
}

export function resourceLabel(resource: string, model?: string | null): string {
  const definition = getEntityTypeDefinition(model ?? modelForResource(resource) ?? '');

  return definition ? $t(definition.pluralLabel) : $t(`responsibilities.matrix.resources.${resource}`);
}

export function resourceIcon(resource: string, model?: string | null): Component {
  return getEntityTypeDefinition(model ?? modelForResource(resource) ?? '')?.icon ?? Settings2;
}

export const abilityLabel = (ability: string) => $t(`responsibilities.matrix.abilities.${ability}`);

export const scopeLabel = (scope: string) => $t(`responsibilities.matrix.scopes.${scope === '*' ? 'all' : scope}`);

/** `meetings.update.padalinys` → "Posėdžiai · redaguoti · padalinyje". */
export function permissionLabel(name: string): string {
  const [resource = '', ability = '', scope = ''] = name.split('.');

  return [resourceLabel(resource), abilityLabel(ability).toLocaleLowerCase(), scopeLabel(scope).toLocaleLowerCase()].join(' · ');
}
