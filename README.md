# Laravel Cascade Delete

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gigerit/laravel-cascade-delete.svg?style=flat-square)](https://packagist.org/packages/gigerit/laravel-cascade-delete)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/gigerit/laravel-cascade-delete/CI.yml?branch=main&label=tests&style=flat-square)](https://github.com/gigerit/laravel-cascade-delete/actions?query=workflow%3ACI+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/gigerit/laravel-cascade-delete.svg?style=flat-square)](https://packagist.org/packages/gigerit/laravel-cascade-delete)

Smart cascading deletes for Laravel Eloquent with support for soft deletes, polymorphic relations, and automatic detaching.

The main advantage of this package over others is its **unified approach**. While many packages handle only soft deletes or only standard relations, this package provides a single trait that intelligently manages all variants:
- **Standard Relations** (`HasOne`, `HasMany`)
- **Soft Deletes** (Recursive soft/hard deletion)
- **Polymorphic Relations** (`MorphOne`, `MorphMany`)
- **Many-to-Many Relations** (`BelongsToMany`, `MorphToMany`) via automatic detaching
- **Orphan Cleanup**: Tools to clean residual polymorphic records after bulk deletions

## Features

- **All-in-One Trait**: Handles all relationship types in a single implementation.
- **Transaction Safety**: All cascading operations are wrapped in a database transaction to ensure atomicity.
- **Integrity Verification**: Verifies that the number of deleted or detached records matches expectations.
- **Intelligent Detaching**: Automatically calls `detach()` for many-to-many relations instead of deleting the related models.
- **Recursive Force Deleting**: Correctly handles `forceDelete()` by propagating it to related models, even those using soft deletes.

## Requirements

- PHP 8.4 or higher
- Laravel 12.x

## Installation

You can install the package via composer:

```bash
composer require gigerit/laravel-cascade-delete
```

## Usage

Simply add the `CascadeDeletes` trait to your model and define the `$cascadeDeletes` property:

```php
namespace App\Models;

use Gigerit\LaravelCascadeDelete\Concerns\CascadeDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes, CascadeDeletes;

    protected $cascadeDeletes = [
        'posts',      // HasMany
        'profile',    // HasOne
        'roles',      // BelongsToMany (will detach)
        'comments',   // MorphMany
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
```

### How it works

- **Delete / Soft Delete**: When you call `$model->delete()`, the package will iterate through the defined relations. If the child models support soft deletes, they will be soft deleted. If not, they will be hard deleted.
- **Force Delete**: If you call `$model->forceDelete()`, the package will automatically use `withTrashed()` on the relations to find and permanently delete all related records.
- **Many-to-Many**: For `BelongsToMany` or `MorphToMany` relations, the package will call `detach()` on the relationship, ensuring the pivot records are removed without deleting the actual related models.
- **Transactions**: If any deletion fails or the record count doesn't match, the entire operation is rolled back.

## Handling Orphan Polymorphic Relations

Bulk deletes in Laravel (e.g., `User::where('active', false)->delete()`) do not fire model events, which can leave orphaned records in polymorphic relationships. This package provides tools to identify and clean these up.

### Artisan Command

You can clean up all orphaned polymorphic relations defined in your models:

```bash
php artisan cascade-delete:clean
```

Use the `--dry-run` flag to see how many records would be deleted without actually removing them:

```bash
php artisan cascade-delete:clean --dry-run
```

### Manual Cleanup

You can also trigger cleanup for a specific model class:

```php
(new User)->clearOrphanMorphRelations();
```

### Configuration

You can publish the config file to customize where the package searches for models:

```bash
php artisan vendor:publish --tag="cascade-delete-config"
```

The default configuration looks for models in `app/`:

```php
return [
    'models_paths' => [
        app_path(),
    ],
];
```

## Handling Failures

If the number of records deleted or detached does not exactly match the number of records found in the relationship, a `\LogicException` will be thrown, and the database transaction will be rolled back.

This prevents silent failures where some related records might have been left orphaned due to database constraints or other issues.

## Testing

```bash
composer test
```

## Credits

- [gigerit](https://github.com/gigerit)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
