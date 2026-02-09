import { DegreeEnum } from '@/Types/enums';

/**
 * Get form options for degree select components
 */
export function getDegreeOptions() {
  return [
    { label: 'Bakalauras (BA)', value: DegreeEnum.BA },
    { label: 'Magistras (MA)', value: DegreeEnum.MA },
    { label: 'Daktaras (PhD)', value: DegreeEnum.PHD },
    { label: 'Vientisosiosios studijos (Integrated Studies)', value: DegreeEnum.INTEGRATED_STUDIES },
    { label: 'Profesinės pedagogikos studijos (Professional Pedagogy)', value: DegreeEnum.PROFESSIONAL_PEDAGOGY },
    { label: 'Kita (Other)', value: DegreeEnum.OTHER },
  ];
}

/**
 * Get human-readable label for a degree value
 */
export function getDegreeLabel(degree: DegreeEnum): string {
  const options = getDegreeOptions();
  return options.find(option => option.value === degree)?.label || degree;
}

/**
 * Get all degree values
 */
export function getAllDegreeValues(): DegreeEnum[] {
  return Object.values(DegreeEnum);
}

/**
 * Check if a value is a valid degree enum value
 */
export function isValidDegree(value: string): value is DegreeEnum {
  return Object.values(DegreeEnum).includes(value as DegreeEnum);
}
