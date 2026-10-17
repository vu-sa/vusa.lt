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
