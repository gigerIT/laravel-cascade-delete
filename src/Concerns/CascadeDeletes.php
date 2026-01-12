<?php

declare(strict_types=1);

namespace Gigerit\LaravelCascadeDelete\Concerns;

use Gigerit\LaravelCascadeDelete\Exceptions\CascadeDeleteException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

trait CascadeDeletes
{
    /**
     * Boot the trait.
     *
     * Listen for the deleting event of a model, and run
     * the delete operation for any configured relationship methods.
     *
     * @return void
     */
    protected static function bootCascadeDeletes()
    {
        static::deleting(function ($model) {
            $model->getConnection()->transaction(function () use ($model) {
                $model->validateCascadingDeletes();

                $model->runCascadingDeletes();
            });
        });
    }

    /**
     * Validate that the calling model is correctly setup for cascading deletes.
     *
     * @return void
     * @throws \Gigerit\LaravelCascadeDelete\Exceptions\CascadeDeleteException
     */
    protected function validateCascadingDeletes()
    {
        if ($invalidCascadingRelationships = $this->hasInvalidCascadingRelationships()) {
            throw CascadeDeleteException::invalidRelationships($invalidCascadingRelationships);
        }
    }

    /**
     * Run the cascading deletes for this model.
     *
     * @return void
     */
    protected function runCascadingDeletes()
    {
        $forceDeleting = $this->isCascadeDeletesForceDeleting();

        foreach ($this->getCascadingDeletes() as $relationship) {
            $this->cascadeDeletes($relationship, $forceDeleting);
        }
    }

    /**
     * Cascade delete the given relationship.
     *
     * @param  string  $relationshipName
     * @param  bool  $forceDeleting
     * @return void
     * @throws \LogicException
     */
    protected function cascadeDeletes(string $relationshipName, bool $forceDeleting)
    {
        $relation = $this->{$relationshipName}();
        $deleteMethod = $forceDeleting ? 'forceDelete' : 'delete';

        if ($relation instanceof BelongsToMany) {
            $this->handleBelongsToManyDeletion($relationshipName, $relation);
        } elseif ($relation instanceof HasOneOrMany) {
            $this->handleHasOneOrManyDeletion($relationshipName, $relation, $deleteMethod);
        } else {
            throw new LogicException(sprintf(
                '[%s]: error occurred deleting [%s]. Relation type [%s] not handled.',
                static::class,
                $relationshipName,
                get_class($relation)
            ));
        }
    }

    /**
     * Handle cascading for BelongsToMany relationships (detach).
     *
     * @param  string  $relationshipName
     * @param  \Illuminate\Database\Eloquent\Relations\BelongsToMany  $relation
     * @return void
     */
    protected function handleBelongsToManyDeletion(string $relationshipName, BelongsToMany $relation)
    {
        $expected = $relation->count();
        $deleted = $relation->detach();

        $this->verifyDeletionCount($relationshipName, $expected, $deleted);
    }

    /**
     * Handle cascading for HasOneOrMany relationships (delete/forceDelete).
     *
     * @param  string  $relationshipName
     * @param  \Illuminate\Database\Eloquent\Relations\HasOneOrMany  $relation
     * @param  string  $deleteMethod
     * @return void
     */
    protected function handleHasOneOrManyDeletion(string $relationshipName, HasOneOrMany $relation, string $deleteMethod)
    {
        $query = $relation;

        if ($deleteMethod === 'forceDelete' && method_exists($query, 'withTrashed')) {
            $query = $query->withTrashed();
        }

        $children = $query->get()->filter(function ($child) {
            return $child instanceof Model && $child->exists;
        });

        $expected = $children->count();
        $deleted = 0;

        foreach ($children as $child) {
            $deleted += (int) $child->{$deleteMethod}();
        }

        $this->verifyDeletionCount($relationshipName, $expected, $deleted);
    }

    /**
     * Verify that the number of deleted records matches expectations.
     *
     * @param  string  $relationshipName
     * @param  int  $expected
     * @param  int  $deleted
     * @return void
     * @throws \LogicException
     */
    protected function verifyDeletionCount(string $relationshipName, int $expected, int $deleted)
    {
        if ($deleted !== $expected) {
            throw new LogicException(sprintf(
                '[%s]: error occurred deleting [%s]. Deleted [%d] out of [%d] records.',
                static::class,
                $relationshipName,
                $deleted,
                $expected
            ));
        }
    }

    /**
     * Determine if the cascading delete should be a force delete.
     *
     * @return bool
     */
    protected function isCascadeDeletesForceDeleting(): bool
    {
        return property_exists($this, 'forceDeleting') && $this->forceDeleting;
    }

    /**
     * Determine if the current model has any invalid cascading relationships defined.
     *
     * @return array
     */
    protected function hasInvalidCascadingRelationships(): array
    {
        return array_filter($this->getCascadingDeletes(), function ($relationship) {
            return ! method_exists($this, $relationship) || ! $this->{$relationship}() instanceof Relation;
        });
    }

    /**
     * Fetch the defined cascading deletes for this model.
     *
     * @return array
     */
    protected function getCascadingDeletes(): array
    {
        return isset($this->cascadeDeletes) ? (array) $this->cascadeDeletes : [];
    }
}
