# Eloquent HasDuplicateAttributes

Laravel Eloquent trait for duplicating attributes from related models automatically.

## Installation

You can install the package via composer:

```bash
composer require denizgolbas/eloquent-hasduplicate-attirbutes
```

## Usage

Use the `HasDuplicateAttributes` trait in your Eloquent model:

```php
use Denizgolbas\EloquentHasduplicateAttirbutes\HasDuplicateAttributes;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use HasDuplicateAttributes;

    protected array $duplicates = [
        // Default: override = true (always copy from source)
        'local_field' => ['related_field', 'relation'],
        
        // Override = false (only copy if local field is empty)
        'slip_no' => ['slip_no', 'source', false],
    ];
}
```

### Configuration Options

The `$duplicates` array accepts the following format:

```php
protected array $duplicates = [
    'local_field' => [
        'related_field',  // Field name in the related model
        'relation',       // Relation method name
        false             // Optional: override flag (default: true)
    ],
];
```

- **override = true** (default): Always copy the value from the related model, even if the local field already has a value.
- **override = false**: Only copy the value if the local field is empty.

### Example

```php
class CustomerSlip extends Model
{
    use HasDuplicateAttributes;

    protected array $duplicates = [
        'name' => ['name', 'customer'],
        'code' => ['code', 'customer', false], // Only copy if code is empty
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
```

### Preventing Attribute Copying

You can prevent attribute copying for a specific save operation:

```php
$model->withoutCopyingRelatedAttributes()->save();
```

## Events

The trait automatically copies attributes on the following events:
- `creating`
- `updating`
- `saving`

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

