<?php

use Gigerit\LaravelCascadeDelete\Tests\Models\Comment;
use Gigerit\LaravelCascadeDelete\Tests\Models\Image;
use Gigerit\LaravelCascadeDelete\Tests\Models\Post;
use Gigerit\LaravelCascadeDelete\Tests\Models\Profile;
use Gigerit\LaravelCascadeDelete\Tests\Models\Role;
use Gigerit\LaravelCascadeDelete\Tests\Models\User;
use Illuminate\Support\Facades\DB;

it('cascades deletes to hasOne relationships', function () {
    $user = User::create(['name' => 'John Doe']);
    $profile = $user->profile()->create(['bio' => 'Developer']);

    expect($user->profile)->not->toBeNull();

    $user->delete();

    expect(User::find($user->id))->toBeNull();
    expect(Profile::where('user_id', $user->id)->count())->toBe(0);
});

it('cascades deletes to hasMany relationships', function () {
    $user = User::create(['name' => 'John Doe']);
    $post1 = $user->posts()->create(['title' => 'Post 1']);
    $post2 = $user->posts()->create(['title' => 'Post 2']);

    expect($user->posts)->toHaveCount(2);

    $user->delete();

    expect(User::find($user->id))->toBeNull();
    expect(Post::where('user_id', $user->id)->count())->toBe(0);
});

it('cascades soft deletes', function () {
    $user = User::create(['name' => 'John Doe']);
    $post = $user->posts()->create(['title' => 'Post 1']);
    $comment = $post->comments()->create(['body' => 'Comment 1']);

    $user->delete();

    expect(User::withTrashed()->find($user->id)->deleted_at)->not->toBeNull();
    expect(Post::withTrashed()->find($post->id)->deleted_at)->not->toBeNull();
    expect(Comment::withTrashed()->find($comment->id)->deleted_at)->not->toBeNull();
});

it('cascades force deletes', function () {
    $user = User::create(['name' => 'John Doe']);
    $post = $user->posts()->create(['title' => 'Post 1']);
    $comment = $post->comments()->create(['body' => 'Comment 1']);

    $user->forceDelete();

    expect(User::withTrashed()->find($user->id))->toBeNull();
    expect(Post::withTrashed()->find($post->id))->toBeNull();
    expect(Comment::withTrashed()->find($comment->id))->toBeNull();
});

it('detaches belongsToMany relationships', function () {
    $user = User::create(['name' => 'John Doe']);
    $role1 = Role::create(['name' => 'Admin']);
    $role2 = Role::create(['name' => 'Editor']);

    $user->roles()->attach([$role1->id, $role2->id]);

    expect($user->roles)->toHaveCount(2);

    $user->delete();

    expect(DB::table('role_user')->where('user_id', $user->id)->count())->toBe(0);
});

it('cascades deletes to morphMany relationships', function () {
    $post = Post::create(['title' => 'Post 1']);
    $post->images()->create(['url' => 'image1.jpg']);
    $post->images()->create(['url' => 'image2.jpg']);

    expect($post->images()->count())->toBe(2);

    $post->delete();

    expect(Post::find($post->id))->toBeNull();
    expect(Image::where('imageable_id', $post->id)->where('imageable_type', Post::class)->count())->toBe(0);
});

it('leaves orphans on bulk delete', function () {
    $post = Post::create(['title' => 'Post 1']);
    $post->images()->create(['url' => 'image1.jpg']);

    // Bulk delete does NOT fire events
    Post::where('id', $post->id)->delete();

    expect(Post::find($post->id))->toBeNull();
    // This should fail (it should have orphans)
    expect(Image::where('imageable_id', $post->id)->where('imageable_type', Post::class)->count())->toBe(1);
});
