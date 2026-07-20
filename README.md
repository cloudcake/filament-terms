# cloudcake/filament-terms

# Terminology
Custom resource/model/column terminology per-panel and/or per-tenant.

## Quickstart

```php
use Cloudcake\Terms\Terminology
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    //...
    
    $panel->plugins([
        // ...
        
        Terminology::make()
               ->pageHeaderAction(false)
               ->topbarAction()
               ->descriptions()
    ]);
}
```

Then add the `Renamable` contract and `HasAlternativeName` concern to any resources that should be rename-able.

```php
use Cloudcake\Terms\Concerns\HasAlternativeTerm;
use Cloudcake\Terms\Contracts\Renamable;
use Filament\Resources\Resource;

class UserResource extends Resource implements Renamable
{
    use HasAlternativeTerm;
    
    //...
}
```

## Detailed API

### Model Column Terminology

If you also want to be able to rename columns on a resource's model, add the `RenamableColumns` contract and the `HasRenamableColumns` concern  to any
models that should be rename-able.

```php
use Cloudcake\Terms\Concerns\HasRenamableColumns;
use Cloudcake\Terms\Contracts\RenameableColumns;

class User extends Authenticatable implements RenamableColumns
{
    use HasRenamableColumns;
    
    //...
}
```

### Explicit Model Columns

If you want to explicitly define which columns should be rename-able, you can remove the `HasRenamableColumns` trait
explicitly define the `renamable()` method on the model. By default, all columns that are not hidden are rename-able.

```php
use Cloudcake\Terms\Concerns\HasRenamableColumns;
use Cloudcake\Terms\Contracts\RenamableColumns;

class User extends Authenticatable implements RenamableColumns
{
    //...

   /**
    * Retrieve columns that are renamable.
    * 
    * @return array
    */
    public static function renamable(): array {
        return ['id', 'name'];
    }
}

```