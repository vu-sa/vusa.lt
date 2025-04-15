<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FilePolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::FILE()->label);
    }

    /**
     * Determine whether the user can view a directory.
     */
    public function viewDirectory(User $user, $directory): bool
    {
        $directoryPath = $directory; // Assuming 'path' is an attribute of the Model
        $check = $this->authorizer->forUser($user)->check($this->pluralModelName.'.read.padalinys');

        $padalinysDirectory = $this->getDirectoryPadalinysAlias($directoryPath);

        if ($check) {
            if ($this->authorizer->isAllScope) {
                return true;
            }

            if (in_array($padalinysDirectory, $this->authorizer->getTenants()->pluck('alias')->toArray())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function deleteDirectory(User $user, $path): bool
    {
        $filePath = $path->getAttribute('path'); // Assuming 'path' is an attribute of the Model
        $check = $this->authorizer->forUser($user)->check($this->pluralModelName.'.read.padalinys');

        $padalinysDirectory = $this->getDirectoryPadalinysAlias($filePath);

        if ($check) {
            if ($this->authorizer->isAllScope) {
                return true;
            }

            if (in_array($padalinysDirectory, $this->authorizer->getTenants()->pluck('alias')->toArray())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method to extract padalinys alias from a directory path.
     */
    protected function getDirectoryPadalinysAlias(string $directory): string
    {
        $path = explode('/', $directory);

        if (in_array('padaliniai', $path)) {
            $index = array_search('padaliniai', $path) + 1;

            if ($index < count($path)) {
                $padalinys = $path[$index];

                if (Str::startsWith($padalinys, 'vusa')) {
                    return substr($padalinys, 4);
                }

                return $padalinys;
            }
        }

        return '';
    }
}
