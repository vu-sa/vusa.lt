<?php

namespace App\Actions\Institutions;

use App\Actions\Media\SyncImageMedia;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/** The one create path for the institution page and the duty wizard, which sends fewer fields. */
class CreateInstitution
{
    public function __construct(private readonly SyncImageMedia $syncImage) {}

    /**
     * @param  array<string, mixed>  $data  StoreInstitutionRequest::validated()
     */
    public function execute(array $data, User $actor): Institution
    {
        $institution = DB::transaction(function () use ($data, $actor): Institution {
            $institution = new Institution;
            $institution->fill(Arr::except($data, ['types', 'image_media', 'logo_media']))->save();
            $institution->syncAudited('types', $data['types'] ?? []);

            $this->syncImage->fromValidated($institution, 'image', $data, 'image_media', $actor);
            $this->syncImage->fromValidated($institution, 'logo', $data, 'logo_media', $actor);

            return $institution;
        });

        return $institution->load('tenant:id,shortname', 'types');
    }
}
