<?php

namespace App\Http\Requests;

use App\Models\Form;
use App\Models\FormField;
use App\Services\FormOptionResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $form = $this->route('form');

        return $form instanceof Form && ! $form->publish_time?->isFuture();
    }

    public function rules(): array
    {
        $fields = $this->fields();
        $rules = ['data' => ['required', 'array:'.implode(',', $fields->modelKeys())]];

        foreach ($fields as $field) {
            $path = "data.{$field->id}";
            $rules[$path] = ['bail', $field->is_required ? 'required' : 'sometimes', 'array:value'];
            $rules["{$path}.value"] = [
                'bail',
                $field->is_required ? 'required' : Rule::when($this->exists($path), 'present', 'sometimes'),
                'nullable',
                ...$this->valueRules($field),
            ];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->fields() as $field) {
            $label = $field->getTranslation('label', app()->getLocale());
            $attributes["data.{$field->id}"] = $label;
            $attributes["data.{$field->id}.value"] = $label;
        }

        return $attributes;
    }

    private function valueRules(FormField $field): array
    {
        return match ($field->type) {
            'string', 'text' => $field->subtype === 'email' ? ['string', 'email'] : ['string'],
            'email' => ['string', 'email'],
            'number' => ['numeric'],
            'boolean' => $field->is_required ? ['boolean', 'accepted'] : ['boolean'],
            'date' => ['date'],
            'enum', 'select' => [$this->optionRule($field)],
            default => [Rule::prohibitedIf(true)],
        };
    }

    private function optionRule(FormField $field): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($field): void {
            if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
                $fail('validation.in')->translate();

                return;
            }

            if ($field->resolvedOptionSource() !== null) {
                $accepted = app(FormOptionResolver::class)->accepts($field, $value);
            } else {
                $options = collect($field->options ?? [])->map(fn ($option) => is_array($option) ? ($option['value'] ?? null) : $option);
                $accepted = $options->contains(fn ($option) => $option !== null && (string) $option === (string) $value);
            }

            if (! $accepted) {
                $fail('validation.in')->translate();
            }
        };
    }

    /** @return Collection<int, FormField> */
    private function fields(): Collection
    {
        $form = $this->route('form');

        return $form instanceof Form ? $form->loadMissing('formFields')->formFields : new Collection;
    }
}
