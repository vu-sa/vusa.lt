---
paths:
  - resources/js/Components/Public/Base/MediaFrame.vue
---

# Base

## MediaFrame supports a focalPoint prop for object-position
`MediaFrame` accepts an optional `focalPoint` prop (raw CSS `object-position`, e.g. "50% 30%") applied via inline style on its `<img>`. Pass a model's `*_focal_point` column through it rather than hand-rolling `:style="{ objectPosition: ... }"` wherever a `MediaFrame` is used with a cropped/off-center photo. Precedent: `NewInstitutionCard.vue` passing `institution.image_focal_point`.
