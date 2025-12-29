# Eloquent HasDuplicateAttributes

[![Latest Version on Packagist](https://img.shields.io/packagist/v/denizgolbas/eloquent-hasduplicate-attirbutes.svg?style=flat-square&label=Packagist)](https://packagist.org/packages/denizgolbas/eloquent-hasduplicate-attirbutes)
[![Total Downloads](https://img.shields.io/packagist/dt/denizgolbas/eloquent-hasduplicate-attirbutes.svg?style=flat-square&label=Downloads)](https://packagist.org/packages/denizgolbas/eloquent-hasduplicate-attirbutes)
[![Tests](https://github.com/denizgolbas/eloquent-hasduplicate-attirbutes/actions/workflows/tests.yml/badge.svg?branch=master)](https://github.com/denizgolbas/eloquent-hasduplicate-attirbutes/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/denizgolbas/eloquent-hasduplicate-attirbutes.svg?style=flat-square&label=License)](https://packagist.org/packages/denizgolbas/eloquent-hasduplicate-attirbutes)

A powerful Laravel Eloquent trait that automatically duplicates attributes from related models when creating or updating records. This package simplifies the process of copying data from parent or related models to child models, with flexible override options.

## Features

- 🚀 **Automatic Attribute Copying**: Automatically copies attributes from related models on `creating`, `updating`, and `saving` events
- ⚙️ **Flexible Override Control**: Choose whether to always override or only copy when fields are empty
- 🔄 **Relation Support**: Works with both single model relations and collections
- 🎯 **Selective Copying**: Prevent copying for specific save operations when needed
- ✅ **Well Tested**: Comprehensive test suite with multiple scenarios

## Installation

You can install the package via Composer:

```bash
composer require denizgolbas/eloquent-hasduplicate-attirbutes
```

The package will automatically register its service provider.

## Basic Usage

### Step 1: Use the Trait

Add the `HasDuplicateAttributes` trait to your Eloquent model:

```php
use Denizgolbas\EloquentHasduplicateAttirbutes\HasDuplicateAttributes;
use Illuminate\Database\Eloquent\Model;

class CustomerSlip extends Model
{
    use HasDuplicateAttributes;
    
    // ... your model code
}
```

### Step 2: Define the Duplicates Configuration

Define which attributes should be copied from which relations using the `$duplicates` array:

```php
protected array $duplicates = [
    // Format: 'local_field' => ['related_field', 'relation_method', override_flag]
    'name' => ['name', 'customer'],           // Always copy customer name
    'code' => ['code', 'customer', false],    // Only copy if code is empty
];
```

### Step 3: Define the Relation

Make sure you have the relation method defined in your model:

```php
public function customer()
{
    return $this->belongsTo(Customer::class);
}
```

## Configuration Options

### Override Flag

The third parameter in the configuration array controls the override behavior:

- **`true` (default)**: Always copy the value from the related model, even if the local field already has a value
- **`false`**: Only copy the value if the local field is empty (null, empty string, etc.)

### Configuration Format

```php
protected array $duplicates = [
    'local_field_name' => [
        'related_field_name',  // The field name in the related model
        'relation_method',      // The relation method name (e.g., 'customer', 'source')
        true                    // Optional: override flag (default: true)
    ],
];
```

## Detailed Examples

### Example 1: Customer Slip with Customer Information

This example shows how to automatically copy customer information to a slip when creating it:

```php
<?php

namespace App\Models;

use Denizgolbas\EloquentHasduplicateAttirbutes\HasDuplicateAttributes;
use Illuminate\Database\Eloquent\Model;

class CustomerSlip extends Model
{
    use HasDuplicateAttributes;

    protected $fillable = ['customer_id', 'slip_no', 'customer_name', 'customer_code', 'total'];

    /**
     * Define which attributes to copy from the customer relation
     */
    protected array $duplicates = [
        // Always copy customer name (override = true by default)
        'customer_name' => ['name', 'customer'],
        
        // Only copy customer code if slip code is empty (override = false)
        'customer_code' => ['code', 'customer', false],
    ];

    /**
     * Define the customer relation
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
```

**Usage:**

```php
// Create a customer
$customer = Customer::create([
    'name' => 'John Doe',
    'code' => 'CUST001',
]);

// Create a slip - customer_name and customer_code will be automatically copied
$slip = CustomerSlip::create([
    'customer_id' => $customer->id,
    'slip_no' => 'SLIP001',
    'total' => 1000.00,
]);

// Result:
// $slip->customer_name = 'John Doe' (copied from customer)
// $slip->customer_code = 'CUST001' (copied from customer)

// If you create a slip with existing customer_code, it won't be overridden
$slip2 = CustomerSlip::create([
    'customer_id' => $customer->id,
    'slip_no' => 'SLIP002',
    'customer_code' => 'MANUAL001', // This will NOT be overridden
    'total' => 2000.00,
]);

// Result:
// $slip2->customer_name = 'John Doe' (copied from customer)
// $slip2->customer_code = 'MANUAL001' (NOT overridden because override = false)
```

### Example 2: Invoice Line with Invoice Header Information

This example demonstrates copying data from a parent model (invoice header) to child models (invoice lines):

```php
<?php

namespace App\Models;

use Denizgolbas\EloquentHasduplicateAttirbutes\HasDuplicateAttributes;
use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    use HasDuplicateAttributes;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'invoice_date',
        'invoice_no',
        'customer_name',
        'quantity',
        'price',
    ];

    /**
     * Copy invoice header information to each line
     */
    protected array $duplicates = [
        'invoice_date' => ['invoice_date', 'invoice'],
        'invoice_no' => ['invoice_no', 'invoice'],
        'customer_name' => ['customer_name', 'invoice'],
    ];

    /**
     * Define the invoice relation
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
```

**Usage:**

```php
// Create an invoice header
$invoice = Invoice::create([
    'invoice_no' => 'INV-2024-001',
    'invoice_date' => '2024-01-15',
    'customer_name' => 'Acme Corporation',
    'total' => 5000.00,
]);

// Create invoice lines - header information is automatically copied
$line1 = InvoiceLine::create([
    'invoice_id' => $invoice->id,
    'product_id' => 1,
    'quantity' => 10,
    'price' => 500.00,
]);

$line2 = InvoiceLine::create([
    'invoice_id' => $invoice->id,
    'product_id' => 2,
    'quantity' => 5,
    'price' => 300.00,
]);

// Both lines now have:
// - invoice_date = '2024-01-15'
// - invoice_no = 'INV-2024-001'
// - customer_name = 'Acme Corporation'
```

### Example 3: Using with Collections

The trait also works with collection relations (hasMany, belongsToMany, etc.):

```php
<?php

namespace App\Models;

use Denizgolbas\EloquentHasduplicateAttirbutes\HasDuplicateAttributes;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasDuplicateAttributes;

    protected $fillable = ['order_id', 'product_id', 'category_name'];

    protected array $duplicates = [
        // When relation returns a collection, the first item is used
        'category_name' => ['name', 'product'],
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

class Product extends Model
{
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

### Example 4: Preventing Attribute Copying

Sometimes you may want to prevent automatic copying for a specific save operation:

```php
// Normal save - attributes will be copied
$slip = CustomerSlip::create([
    'customer_id' => $customer->id,
    'slip_no' => 'SLIP001',
]);
// customer_name and customer_code are automatically copied

// Save without copying - attributes will NOT be copied
$slip2 = new CustomerSlip([
    'customer_id' => $customer->id,
    'slip_no' => 'SLIP002',
    'customer_name' => 'Manual Name', // This will be preserved
]);
$slip2->withoutCopyingRelatedAttributes()->save();
// customer_name = 'Manual Name' (not copied from customer)
```

## How It Works

The trait hooks into Laravel's Eloquent model events:

1. **`creating` event**: Copies attributes when a new model is being created
2. **`updating` event**: Copies attributes when an existing model is being updated
3. **`saving` event**: Copies attributes on both create and update operations

When any of these events fire, the trait:

1. Checks if the `$duplicates` array is defined
2. For each configured duplicate:
   - Loads the related model (if not already loaded)
   - Checks the override flag
   - Copies the attribute value accordingly
3. The model is then saved with the copied attributes

## Advanced Usage

### Working with Eager Loading

The trait is smart enough to use already loaded relations:

```php
// Eager load the relation
$slips = CustomerSlip::with('customer')->get();

// When updating, the trait will use the already loaded relation
// No additional database queries needed
foreach ($slips as $slip) {
    $slip->update(['total' => 1000]);
    // customer relation is already loaded, no extra query
}
```

### Updating Related Models

When you update a related model and then update the child model, the new values are copied:

```php
$customer = Customer::find(1);
$slip = CustomerSlip::where('customer_id', $customer->id)->first();

// Update customer
$customer->update(['name' => 'Updated Name']);

// Update slip - new customer name will be copied
$slip->update(['total' => 1500]);
// $slip->customer_name is now 'Updated Name'
```

## Testing

Run the test suite:

```bash
composer test
```

Or run PHPUnit directly:

```bash
vendor/bin/phpunit
```

## Requirements

- PHP >= 8.1
- Laravel >= 9.0

## Supported Laravel Versions

- Laravel 9.x
- Laravel 10.x
- Laravel 11.x

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Author

**Deniz Golbas**

---

Made with ❤️ for the Laravel community
