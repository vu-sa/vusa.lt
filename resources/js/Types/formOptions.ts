import { AllowedRelationshipablesEnum, ModelEnum } from '@/Types/enums';

const uppercase = (string: string) => {
  return string[0].toUpperCase() + string.substring(1);
};

export const modelDefaults = {
};

// Morph aliases (see App\Support\MorphMap) — what `*_type` columns store and what the
// backend validates a submitted model type against. Labels are derived from them for display.
export const modelTypes = {
  // Derived from the generated AllowedRelationshipablesEnum so this list cannot drift from
  // the PHP allowlist that actually validates the submission.
  relationshipable: Object.values(AllowedRelationshipablesEnum).map(
    value => value.toLowerCase(),
  ),
  sharepointFile: [
    'Ataskaitos',
    'Metodinė medžiaga',
    'Protokolai',
    'Pristatymai',
    'Šablonai',
    'Veiklą reglamentuojantys dokumentai',
  ],
  // Must match Type::TYPEABLE_RELATIONS. Meeting used to be offered here but is rejected by
  // Store/UpdateTypeRequest, so picking it produced a validation error with no explanation.
  type: [
    ModelEnum.DUTY,
    ModelEnum.INSTITUTION,
  ],
};

/** "duty" -> "Duty", for showing a morph alias in a select. */
export const modelTypeLabel = (alias: string) => uppercase(alias);

export const modelStatus = {
};

export const documentTemplate = {
  name: '',
  file: {
    mimeType: '',
  },
  createdDateTime: {
    date: '',
  },
  size: 0,
  type: '',
  description: '',
};
