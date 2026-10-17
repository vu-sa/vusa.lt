<?php

use App\Models\Banner;
use App\Support\Media\ImageConversions;
use Spatie\MediaLibrary\Conversions\Conversion;

test('image conversions are not re-encoded by the optimizer chain', function (): void {
    $model = new Banner;
    ImageConversions::register($model);

    $conversions = collect($model->mediaConversions);

    expect($conversions->map(fn (Conversion $conversion) => $conversion->getName())->all())
        ->toBe(array_keys(ImageConversions::WIDTHS))
        ->and($conversions->every(fn (Conversion $conversion) => $conversion->getManipulations()->getManipulationArgument('optimize') === null))
        ->toBeTrue();
});
