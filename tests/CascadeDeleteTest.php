<?php

use Gigerit\LaravelCascadeDelete\Tests\Models\Comment;
use Gigerit\LaravelCascadeDelete\Tests\Models\Post;
use Gigerit\LaravelCascadeDelete\Tests\Models\Profile;
use Gigerit\LaravelCascadeDelete\Tests\Models\Role;
use Gigerit\LaravelCascadeDelete\Tests\Models\User;

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
