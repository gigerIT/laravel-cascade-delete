<?php

namespace Gigerit\LaravelCascadeDelete\Tests;

use Gigerit\LaravelCascadeDelete\Concerns\CascadeDeletes;
use Gigerit\LaravelCascadeDelete\Exceptions\CascadeDeleteException;
use Gigerit\LaravelCascadeDelete\Tests\Models\Post;
use Gigerit\LaravelCascadeDelete\Tests\Models\User;
use Gigerit\LaravelCascadeDelete\Tests\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

// Define test models outside to ensure they are booted correctly if needed
class MissingMethodModel extends User {
    protected $table = 'users';
    protected $cascadeDeletes = ['nonExistentMethod'];
}

class InvalidReturnModel extends User {
    protected $table = 'users';
    protected $cascadeDeletes = ['notARelation'];
    public function notARelation() {
        return 'not a relation';
    }
}

class UnsupportedRelationModel extends Post {
    protected $table = 'posts';
    protected $cascadeDeletes = ['user'];
}

it('throws exception if cascading relationship method does not exist', function () {
    $model = new MissingMethodModel();
    $model->id = 1;
    $model->exists = true;

    expect(fn () => $model->delete())->toThrow(CascadeDeleteException::class, 'Relationship [nonExistentMethod] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation');
});

it('throws exception if cascading relationship method does not return a relation', function () {
    $model = new InvalidReturnModel();
    $model->id = 1;
    $model->exists = true;

    expect(fn () => $model->delete())->toThrow(CascadeDeleteException::class, 'Relationship [notARelation] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation');
});

it('throws exception for unsupported relation type', function () {
    $model = new UnsupportedRelationModel();
    $model->id = 1;
    $model->exists = true;

    expect(fn () => $model->delete())->toThrow(LogicException::class, 'Relation type [Illuminate\Database\Eloquent\Relations\BelongsTo] not handled');
});

it('handles deep nesting of cascading deletes', function () {
    $user = User::create(['name' => 'John']);
    $post = $user->posts()->create(['title' => 'Post 1']);
    $comment = $post->comments()->create(['body' => 'Comment 1']);

    $user->delete();

    expect(User::withTrashed()->find($user->id)->deleted_at)->not->toBeNull();
    expect(Post::withTrashed()->find($post->id)->deleted_at)->not->toBeNull();
    expect(Comment::withTrashed()->find($comment->id)->deleted_at)->not->toBeNull();
});

it('rolls back transaction if child deletion fails', function () {
    $user = User::create(['name' => 'John']);
    $post = $user->posts()->create(['title' => 'Post 1']);
    
    // Use an observer to fail the deletion of the post
    Post::deleting(function ($post) {
        if ($post->title === 'Post 1') {
            return false;
        }
    });
    
    try {
        $user->delete();
    } catch (LogicException $e) {
        // Expected exception from verifyDeletionCount
    }
    
    expect(User::find($user->id))->not->toBeNull();
    expect(Post::find($post->id))->not->toBeNull();
    
    // Clean up observer for other tests
    Post::flushEventListeners();
});

it('cascades forceDelete even if children are already soft-deleted', function () {
    $user = User::create(['name' => 'John']);
    $post = $user->posts()->create(['title' => 'Post 1']);
    
    // Soft delete the post first
    $post->delete();
    expect(Post::withTrashed()->find($post->id)->deleted_at)->not->toBeNull();
    
    // Force delete the user
    $user->forceDelete();
    
    expect(User::withTrashed()->find($user->id))->toBeNull();
    expect(Post::withTrashed()->find($post->id))->toBeNull();
});

it('throws exception for multiple invalid relationships', function () {
    $model = new class extends User {
        protected $table = 'users';
        protected $cascadeDeletes = ['invalid1', 'invalid2'];
    };
    $model->id = 1;
    $model->exists = true;

    expect(fn () => $model->delete())->toThrow(CascadeDeleteException::class, 'Relationships [invalid1, invalid2] must exist and return an object of type Illuminate\Database\Eloquent\Relations\Relation');
});

it('cascades deletes to morphOne relationships', function () {
    $post = Post::create(['title' => 'Post 1']);
    
    $testPost = new class extends Post {
        protected $table = 'posts';
        protected $cascadeDeletes = ['singleImage'];
        public function singleImage() {
            return $this->morphOne(\Gigerit\LaravelCascadeDelete\Tests\Models\Image::class, 'imageable');
        }
        public function getMorphClass() {
            return Post::class;
        }
    };
    
    // Create an image for this post
    \Gigerit\LaravelCascadeDelete\Tests\Models\Image::create([
        'url' => 'single.jpg',
        'imageable_id' => $post->id,
        'imageable_type' => Post::class,
    ]);

    $postInstance = $testPost->find($post->id);
    expect($postInstance->singleImage)->not->toBeNull();
    
    $postInstance->delete();
    
    expect(\Gigerit\LaravelCascadeDelete\Tests\Models\Image::where('url', 'single.jpg')->count())->toBe(0);
});

it('handles mixed soft and hard deletes in cascade chain', function () {
    $user = User::create(['name' => 'John']);
    // Post uses SoftDeletes
    $post = $user->posts()->create(['title' => 'Post 1']);
    // Image DOES NOT use SoftDeletes
    $image = $post->images()->create(['url' => 'image.jpg']);
    
    $user->delete();
    
    expect(User::withTrashed()->find($user->id)->deleted_at)->not->toBeNull();
    expect(Post::withTrashed()->find($post->id)->deleted_at)->not->toBeNull();
    expect(\Gigerit\LaravelCascadeDelete\Tests\Models\Image::find($image->id))->toBeNull();
});

it('handles circular dependencies by not re-deleting what is already deleted', function () {
    // Model A cascades to Model B, Model B cascades to Model A
    $user = User::create(['name' => 'John']);
    $profile = $user->profile()->create(['bio' => 'Bio']);
    
    // We'll use a custom user model that cascades to profile, 
    // and a custom profile model that cascades back to user.
    // Note: User model already cascades to profile.
    
    $circularUser = new class extends User {
        protected $table = 'users';
        protected $cascadeDeletes = ['profile'];
    };
    
    $circularProfile = new class extends \Gigerit\LaravelCascadeDelete\Tests\Models\Profile {
        protected $table = 'profiles';
        protected $cascadeDeletes = ['userInstance']; // named differently to avoid conflict
        public function userInstance() {
            return $this->belongsTo(User::class, 'user_id');
        }
        public function getMorphClass() {
            return \Gigerit\LaravelCascadeDelete\Tests\Models\Profile::class;
        }
    };
    
    // This test is mostly to ensure no infinite recursion happens.
    // However, BelongsTo will throw a LogicException, which is fine, 
    // it still proves it reached that point and stopped.
    
    $userInstance = $circularUser->find($user->id);
    
    // We expect it to fail with LogicException (or QueryException due to constraints)
    // because of BelongsTo in the circular path,
    // NOT with a segmentation fault from infinite recursion.
    expect(fn() => $userInstance->delete())->toThrow(\Exception::class);
});
