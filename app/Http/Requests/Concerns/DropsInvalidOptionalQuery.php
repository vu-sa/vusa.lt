<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

trait DropsInvalidOptionalQuery
{
    #[\Override]
    protected function getValidatorInstance(): Validator
    {
        /** @var Validator $validator */
        $validator = parent::getValidatorInstance();

        if ($this->isMethod('GET') && $validator->fails()) {
            $data = $validator->getData();

            foreach ($validator->errors()->keys() as $key) {
                Arr::forget($data, Str::before($key, '.'));
            }

            $validator->setData($data);
        }

        return $validator;
    }
}
