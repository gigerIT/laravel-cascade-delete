<?php

declare(strict_types=1);

namespace Gigerit\LaravelCascadeDelete\Support;

use Gigerit\LaravelCascadeDelete\Concerns\CascadeDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Finder;

class Morph
{
    /**
     * Clean residual polymorphic relationships from all Models.
     */
    public function clearOrphanAllModels(bool $dryRun = false): int
    {
        $numRowsDeleted = 0;

        foreach ($this->getCascadeDeleteModels() as $model) {
            $numRowsDeleted += $this->clearOrphanByModel($model, $dryRun);
        }

        return $numRowsDeleted;
    }

    /**
     * Clean residual polymorphic relationships from a Model.
     */
    public function clearOrphanByModel(Model $model, bool $dryRun = false): int
    {
        $numRowsDeleted = 0;

        foreach ($this->getValidMorphRelationsFromModel($model) as $relation) {
            $numRowsDeleted += $this->queryClearOrphan($model, $relation, $dryRun);
        }

        return $numRowsDeleted;
    }

    /**
     * Get the classes that use the trait CascadeDelete.
     *
     * @return \Illuminate\Database\Eloquent\Model[]
     */
    protected function getCascadeDeleteModels(): array
    {
        $this->loadModels();

        $models = [];

        foreach (get_declared_classes() as $class) {
            if (in_array(CascadeDeletes::class, class_uses_recursive($class), true)) {
                $reflection = new \ReflectionClass($class);
                if (!$reflection->isAbstract() && $reflection->isSubclassOf(Model::class)) {
                    $models[] = new $class();
                }
            }
        }

        return $models;
    }

    /**
     * Query to clear orphan morph relations.
     */
    protected function queryClearOrphan(Model $parentModel, Relation $relation, bool $dryRun = false): int
    {
        [$childTable, $childFieldType, $childFieldId] = $this->getStructureMorphRelation($relation);

        $query = DB::table($childTable)
            ->where($childFieldType, $parentModel->getMorphClass())
            ->whereNotExists(function ($query) use ($parentModel, $childTable, $childFieldId) {
                $query->select(DB::raw(1))
                    ->from($parentModel->getTable())
                    ->whereColumn($parentModel->getTable() . '.' . $parentModel->getKeyName(), '=', $childTable . '.' . $childFieldId);
            });

        return $dryRun ? $query->count() : $query->delete();
    }

    /**
     * Get structure of morph relation.
     *
     * @return array{0: string, 1: string, 2: string} [$table, $fieldType, $fieldId]
     */
    protected function getStructureMorphRelation(Relation $relation): array
    {
        if ($relation instanceof MorphOneOrMany) {
            return [
                $relation->getRelated()->getTable(),
                $relation->getMorphType(),
                $relation->getForeignKeyName(),
            ];
        }

        if ($relation instanceof MorphToMany) {
            return [
                $relation->getTable(),
                $relation->getMorphType(),
                $relation->getForeignPivotKeyName(),
            ];
        }

        throw new \InvalidArgumentException('Invalid morph relation');
    }

    /**
     * Fetch polymorphic relationships from a Model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\Relation[]
     */
    protected function getValidMorphRelationsFromModel(Model $model): array
    {
        if (!method_exists($model, 'getCascadingDeletes')) {
            return [];
        }

        $relations = [];

        foreach ($model->getCascadingDeletes() as $relationshipName) {
            if (!method_exists($model, $relationshipName)) {
                continue;
            }

            $relation = $model->{$relationshipName}();

            if ($relation instanceof MorphOneOrMany || $relation instanceof MorphToMany) {
                $relations[] = $relation;
            }
        }

        return $relations;
    }

    /**
     * Load models from the application.
     */
    protected function loadModels(): void
    {
        $paths = config('cascade-delete.models_paths', [app_path('Models'), app_path()]);

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach (Finder::create()->files()->in($path)->name('*.php') as $file) {
                require_once $file->getRealPath();
            }
        }
    }
}
