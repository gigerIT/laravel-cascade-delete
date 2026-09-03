<?php

use Gigerit\LaravelCascadeDelete\Support\Morph;
use Gigerit\LaravelCascadeDelete\Tests\Models\Image;
use Gigerit\LaravelCascadeDelete\Tests\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
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
    $deleted = (new Post)->clearOrphanMorphRelations();

    expect($deleted)->toBe(2);
    expect(Image::count())->toBe(0);
});

it('cleans residual morph relations via command', function () {
    // Setup: point to our test models directory
    config(['cascade-delete.models_paths' => [__DIR__.'/Models']]);

    $post = Post::create(['title' => 'Post 1']);
    $post->images()->create(['url' => 'image1.jpg']);

    Post::where('id', $post->id)->forceDelete();
    expect(Image::count())->toBe(1);

    Artisan::call('cascade-delete:clean');

    expect(Image::count())->toBe(0);
});

it('reports residual morph relations via command dry-run', function () {
    config(['cascade-delete.models_paths' => [__DIR__.'/Models']]);

    $post = Post::create(['title' => 'Post 1']);
    $post->images()->create(['url' => 'image1.jpg']);

    Post::where('id', $post->id)->forceDelete();
    expect(Image::count())->toBe(1);

    Artisan::call('cascade-delete:clean', ['--dry-run' => true]);
    $output = Artisan::output();

    expect($output)->toContain('Found 1 residual polymorphic records');
    expect(Image::count())->toBe(1);
});

it('skips excluded directories while discovering models', function (string $directory, ?array $configuredExclusions) {
    $filesystem = new Filesystem;
    $root = sys_get_temp_dir().'/cascade-delete-'.bin2hex(random_bytes(8));
    $filesystem->makeDirectory($root.'/'.$directory, 0755, true);
    file_put_contents($root.'/Loadable.php', '<?php $GLOBALS[\'cascade_delete_loadable_fixture\'] = true;');
    file_put_contents($root.'/'.$directory.'/Explodes.php', '<?php throw new RuntimeException(\'Excluded fixture was loaded.\');');
    unset($GLOBALS['cascade_delete_loadable_fixture']);

    if ($configuredExclusions === null) {
        config(['cascade-delete' => ['models_paths' => [$root]]]);
    } else {
        config([
            'cascade-delete.models_paths' => [$root],
            'cascade-delete.models_excluded_directories' => $configuredExclusions,
        ]);
    }

    try {
        Artisan::call('cascade-delete:clean', ['--dry-run' => true]);

        expect($GLOBALS['cascade_delete_loadable_fixture'] ?? false)->toBeTrue();
    } finally {
        unset($GLOBALS['cascade_delete_loadable_fixture']);
        $filesystem->deleteDirectory($root);
    }
})->with([
    'default uppercase Tests directory' => ['Tests', null],
    'default lowercase tests directory' => ['tests', null],
    'consumer-configured directory' => ['Specifications', ['Specifications']],
]);

it('discovers nested models using the cascade trait indirectly across configured paths', function () {
    $filesystem = new Filesystem;
    $root = sys_get_temp_dir().'/cascade-delete-'.bin2hex(random_bytes(8));
    $filesystem->makeDirectory($root.'/Domain/Models', 0755, true);
    file_put_contents($root.'/Domain/Models/NestedModel.php', <<<'PHP'
<?php

namespace Gigerit\LaravelCascadeDelete\Tests\Fixtures;

class NestedModel extends \Gigerit\LaravelCascadeDelete\Tests\Models\Post
{
    protected $cascadeDeletes = [];
}
PHP);

    config([
        'cascade-delete.models_paths' => [
            $root.'/missing',
            __DIR__.'/Models',
            $root,
        ],
    ]);

    $morph = new class extends Morph
    {
        /**
         * @return Model[]
         */
        public function getModels(): array
        {
            return $this->getCascadeDeleteModels();
        }
    };

    try {
        $models = array_filter(
            $morph->getModels(),
            fn (Model $model): bool => $model::class === 'Gigerit\LaravelCascadeDelete\Tests\Fixtures\NestedModel',
        );

        expect($models)->toHaveCount(1);
    } finally {
        $filesystem->deleteDirectory($root);
    }
});
