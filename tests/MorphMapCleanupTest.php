<?php

use Gigerit\LaravelCascadeDelete\Tests\Models\Image;
use Gigerit\LaravelCascadeDelete\Tests\Models\Post;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Relation::morphMap([
        'custom_post' => Post::class,
    ]);
});

afterEach(function () {
    Relation::morphMap([], false);
});

it('cleans residual morph relations with custom morph map', function () {
    $post = Post::create(['title' => 'Post with Morph Map']);

    // Ensure the image record uses the morph map alias 'custom_post'
    $post->images()->create(['url' => 'image-with-map.jpg']);

    $image = Image::first();
    expect($image->imageable_type)->toBe('custom_post');

    // Force delete leaves orphans because bulk delete skips events
    Post::where('id', $post->id)->forceDelete();
    expect(Post::count())->toBe(0);
    expect(Image::count())->toBe(1);

    // Clean orphans - should recognize 'custom_post' and delete the image
    $deleted = (new Post)->clearOrphanMorphRelations();

    expect($deleted)->toBe(1);
    expect(Image::count())->toBe(0);
});

it('cleans residual morph relations via command with custom morph map', function () {
    config(['cascade-delete.models_paths' => [__DIR__ . '/Models']]);

    $post = Post::create(['title' => 'Post via command with Morph Map']);
    $post->images()->create(['url' => 'image-via-command.jpg']);

    $image = Image::first();
    expect($image->imageable_type)->toBe('custom_post');

    Post::where('id', $post->id)->forceDelete();
    expect(Image::count())->toBe(1);

    Artisan::call('cascade-delete:clean');

    expect(Image::count())->toBe(0);
});
