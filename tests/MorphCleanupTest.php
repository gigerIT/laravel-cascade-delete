<?php

use Gigerit\LaravelCascadeDelete\Tests\Models\Image;
use Gigerit\LaravelCascadeDelete\Tests\Models\Post;
use Illuminate\Support\Facades\Artisan;

it('cleans residual morph relations for a specific model instance', function () {
    $post = Post::create(['title' => 'Post 1']);
    $post->images()->create(['url' => 'image1.jpg']);
    $post->images()->create(['url' => 'image2.jpg']);

    expect(Image::count())->toBe(2);

    // Force delete leaves orphans because bulk delete skips events
    Post::where('id', $post->id)->forceDelete();
    expect(Post::count())->toBe(0);
    expect(Image::count())->toBe(2);

    // Clean orphans
    $deleted = (new Post())->clearOrphanMorphRelations();

    expect($deleted)->toBe(2);
    expect(Image::count())->toBe(0);
});

it('cleans residual morph relations via command', function () {
    // Setup: point to our test models directory
    config(['cascade-delete.models_paths' => [__DIR__ . '/Models']]);

    $post = Post::create(['title' => 'Post 1']);
    $post->images()->create(['url' => 'image1.jpg']);

    Post::where('id', $post->id)->forceDelete();
    expect(Image::count())->toBe(1);

    Artisan::call('cascade-delete:clean');

    expect(Image::count())->toBe(0);
});

it('reports residual morph relations via command dry-run', function () {
    config(['cascade-delete.models_paths' => [__DIR__ . '/Models']]);

    $post = Post::create(['title' => 'Post 1']);
    $post->images()->create(['url' => 'image1.jpg']);

    Post::where('id', $post->id)->forceDelete();
    expect(Image::count())->toBe(1);

    Artisan::call('cascade-delete:clean', ['--dry-run' => true]);
    $output = Artisan::output();

    expect($output)->toContain('Found 1 residual polymorphic records');
    expect(Image::count())->toBe(1);
});
