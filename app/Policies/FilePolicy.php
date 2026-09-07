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
        $this->pluralModelName = Str::plural(ModelEnum::FILE->label());
    }

    /**
     * Determine whether the user can view a directory.
     */
    public function viewDirectory(User $user, $directory): bool
    {
        $dir = $directory instanceof Model ? $directory->getAttribute('path') : $directory;

        return is_string($dir) && $this->allowsDirectory($user, $dir, $this->pluralModelName.'.read.padalinys');
    }

    /**
     * Determine whether the user can delete the directory.
     */
    public function deleteDirectory(User $user, $path): bool
    {
        $dir = $path instanceof Model ? $path->getAttribute('path') : $path;

        return is_string($dir) && $this->allowsDirectory($user, $dir, $this->pluralModelName.'.delete.padalinys');
    }

    /**
     * Determine whether the user can add files or folders inside a directory.
     *
     * `StoreFilesRequest::authorize()` only asks whether the actor may create files at all;
     * this is what binds the destination to a tenant they actually administer.
     */
    public function createInDirectory(User $user, $directory): bool
    {
        $dir = $directory instanceof Model ? $directory->getAttribute('path') : $directory;

        return is_string($dir) && $this->allowsDirectory($user, $dir, $this->pluralModelName.'.create.padalinys');
    }

    /**
     * Determine whether the user can modify files inside a directory.
     *
     * Rewriting a file in place is an update, not a read — image compression overwrites
     * the author's original.
     */
    public function updateInDirectory(User $user, $directory): bool
    {
        $dir = $directory instanceof Model ? $directory->getAttribute('path') : $directory;

        return is_string($dir) && $this->allowsDirectory($user, $dir, $this->pluralModelName.'.update.padalinys');
    }

    /**
     * Determine whether the user can delete files inside a directory.
     *
     * Same permission as deleting the directory itself, but named for the call site: the
     * argument is the directory a file lives in, not the thing being deleted.
     */
    public function deleteInDirectory(User $user, $directory): bool
    {
        $dir = $directory instanceof Model ? $directory->getAttribute('path') : $directory;

        return is_string($dir) && $this->allowsDirectory($user, $dir, $this->pluralModelName.'.delete.padalinys');
    }

    /**
     * Whether the actor's scope reaches the tenant folder this path sits in.
     */
    protected function allowsDirectory(User $user, string $directory, string $permission): bool
    {
        $scope = $this->authorizer->scope($user, $permission);

        if (! $scope->granted) {
            return false;
        }

        return $scope->isAllScope
            || $scope->tenants->contains('alias', $this->getDirectoryPadalinysAlias($directory));
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
