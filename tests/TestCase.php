<?php

namespace Gigerit\LaravelCascadeDelete\Tests;

use Gigerit\LaravelCascadeDelete\LaravelCascadeDeleteServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn(string $modelName) => 'Gigerit\\LaravelCascadeDelete\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelCascadeDeleteServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('profiles', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bio');
            $table->softDeletes();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('posts', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('comments', function ($table) {
            $table->id();
            $table->foreignId('post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('body');
            $table->softDeletes();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('role_user', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('images', function ($table) {
            $table->id();
            $table->string('url');
            $table->morphs('imageable');
            $table->timestamps();
        });
    }
}
