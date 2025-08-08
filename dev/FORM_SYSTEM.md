# Form System Architecture

This document explains the dynamic form system used in vusa.lt for member registrations and other forms.

## Core Components

### FormField Model
Represents a single field in a dynamic form with support for multiple types and localization.

**Field Types:**
- `string` - Text input (subtypes: `name`, `email`, `textarea`)
- `boolean` - Checkbox/toggle
- `enum` - Select dropdown with options
- `number` - Numeric input
- `date` - Date picker

**Key Properties:**
- `label` - Translatable field label (`{ lt: "...", en: "..." }`)
- `type` - Field type (see above)
- `subtype` - Additional field behavior (e.g., email validation)
- `options` - For enum fields: `[{ value: "id", label: { lt: "...", en: "..." } }]`
- `is_required` - Whether field is mandatory
- `use_model_options` - Load options from database model (e.g., Tenant list)

### FieldResponse Model
Stores user responses to form fields with consistent structure.

**Response Structure:**
```json
{
  "value": "actual_user_input"
}
```

**Why the wrapper?**
1. **Consistency** - Same access pattern for all field types
2. **Type Safety** - Always an object with predictable structure
3. **Extensibility** - Room for future metadata without breaking changes

**Helper Methods:**
- `getValue()` - Returns `response['value']` safely
- `hasValue()` - Checks if response has a non-empty value

## Data Flow

### Form Creation (Admin)
1. Admin creates Form with multiple FormFields
2. Each FormField defines validation rules and display options
3. Form is published with unique URL path

### Form Submission (Public)
1. Frontend generates Zod schema from FormField definitions
2. User fills form, data validated client-side
3. Submission transforms to: `{ "field_id": { "value": "user_input" } }`
4. Backend validates and stores FieldResponse records

### Data Access
```php
// Always use helper methods for consistency
$email = $fieldResponse->getValue();
$hasResponse = $fieldResponse->hasValue();

// Export and display logic
$responses->map(fn($r) => $r->getValue());
```

## Key Design Decisions

### Structured Response Storage
- **Database**: JSON column for structured data
- **Cast**: `'response' => 'array'` in Eloquent model
- **Access**: Always via `getValue()` helper method

### Localization Support
- Form fields have translatable labels and descriptions
- Enum options support multi-language labels
- Frontend selects appropriate language at runtime

### Validation Strategy
- **Frontend**: Zod schema generated from FormField definitions
- **Backend**: Request validation ensures response structure consistency
- **Database**: JSON constraints maintain data integrity

## Security Considerations

- Form submissions validate against actual FormField definitions
- Tenant isolation prevents cross-organization data access
- Response structure validation prevents malformed data

## Usage Examples

### Creating a Form Field
```php
FormField::create([
    'form_id' => $form->id,
    'type' => 'enum',
    'label' => ['lt' => 'Padalinys', 'en' => 'Division'],
    'options' => [
        ['value' => '1', 'label' => ['lt' => 'VU SA', 'en' => 'VU SR']],
        ['value' => '2', 'label' => ['lt' => 'VU SA IF', 'en' => 'VU SR IF']]
    ],
    'is_required' => true
]);
```

### Processing Responses
```php
// In listeners, exports, etc.
$email = $fieldResponses->first(fn($r) => $r->formField->subtype === 'email')?->getValue();
$name = $fieldResponses->first(fn($r) => $r->formField->subtype === 'name')?->getValue();
```

This architecture provides a robust, scalable foundation for dynamic forms while maintaining type safety and consistency across the application.