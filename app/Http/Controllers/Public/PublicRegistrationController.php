<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use App\Models\Form;
use App\Models\FormField;
use App\Services\FormOptionResolver;
use App\Support\LocalizedRouteSlugs;
use Inertia\Inertia;

class PublicRegistrationController extends PublicController
{
    public function show(string $lang, string $registrationString, string $registrationForm, FormOptionResolver $optionResolver)
    {
        $this->getBanners();
        $this->getTenantLinks();

        $form = Form::query()->where('path->'.$lang, $registrationForm)->with(['formFields' => function ($query): void {
            $query->orderBy('order');
        }])->firstOrFail();

        if ($form->publish_time?->isFuture()) {
            abort(404);
        }

        $this->sharePublicEditLink($form);

        $otherLocale = app()->getLocale() === 'lt' ? 'en' : 'lt';

        Inertia::share('otherLangURL', LocalizedRouteSlugs::route('registrationPage', [
            'registrationForm' => $form->getTranslation('path', $otherLocale),
        ], $otherLocale));

        $this->applyPageHead(
            contentTenant: null,
            title: $form->name,
            description: [$form->description, __('seo.registration_description', ['form' => $form->name, 'tenant' => $this->tenant->shortname])],
        );

        $preselectedInstitutionId = request()->query('institution');

        return Inertia::render('Public/RegistrationPage', [
            'form' => [
                ...$form->toArray(),
                'form_fields' => $form->formFields->map(function (FormField $field) use ($form, $optionResolver, $preselectedInstitutionId) {
                    $options = $field->options;

                    if ($field->resolvedOptionSource() !== null) {
                        $options = $optionResolver->optionsFor($form, $field, $preselectedInstitutionId);
                    }

                    return [
                        ...$field->toArray(),
                        'option_source' => $field->resolvedOptionSource()?->value,
                        'options' => $options,
                    ];
                }),
            ],
        ]);
    }
}
