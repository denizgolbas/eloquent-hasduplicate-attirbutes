<?php

namespace Denizgolbas\EloquentHasduplicateAttirbutes\Tests;

use Denizgolbas\EloquentHasduplicateAttirbutes\HasDuplicateAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HasDuplicateAttributesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_copies_attributes_on_creating_with_override_true()
    {
        $source = SourceModel::create([
            'name' => 'Test Source',
            'code' => 'TEST001',
        ]);

        $target = TargetModel::create([
            'source_id' => $source->id,
        ]);

        $this->assertEquals('Test Source', $target->name);
        $this->assertEquals('TEST001', $target->code);
    }

    public function test_it_copies_attributes_on_creating_with_override_false_when_empty()
    {
        $source = SourceModel::create([
            'name' => 'Test Source',
            'code' => 'TEST001',
        ]);

        $target = TargetModel::create([
            'source_id' => $source->id,
            'code' => null,
        ]);

        $this->assertEquals('Test Source', $target->name);
        $this->assertEquals('TEST001', $target->code);
    }

    public function test_it_does_not_override_when_override_false_and_field_is_not_empty()
    {
        $source = SourceModel::create([
            'name' => 'Test Source',
            'code' => 'TEST001',
        ]);

        $target = TargetModel::create([
            'source_id' => $source->id,
            'code' => 'EXISTING',
        ]);

        $this->assertEquals('Test Source', $target->name);
        $this->assertEquals('EXISTING', $target->code);
    }

    public function test_it_copies_attributes_on_updating()
    {
        $source = SourceModel::create([
            'name' => 'Test Source',
            'code' => 'TEST001',
        ]);

        $target = TargetModel::create([
            'source_id' => $source->id,
        ]);

        // Update source
        $source->update(['name' => 'Updated Source']);
        
        // Update target - this should trigger the trait to copy updated source attributes
        $target->update(['source_id' => $source->id]);

        $this->assertEquals('Updated Source', $target->fresh()->name);
    }

    public function test_without_copying_related_attributes_prevents_copying()
    {
        $source = SourceModel::create([
            'name' => 'Test Source',
            'code' => 'TEST001',
        ]);

        $target = new TargetModel([
            'source_id' => $source->id,
            'name' => 'Original Name',
        ]);

        $target->withoutCopyingRelatedAttributes()->save();

        $this->assertEquals('Original Name', $target->fresh()->name);
    }
}

class SourceModel extends Model
{
    protected $table = 'sources';
    protected $fillable = ['name', 'code'];
}

class TargetModel extends Model
{
    use HasDuplicateAttributes;

    protected $table = 'targets';
    protected $fillable = ['source_id', 'name', 'code'];

    protected array $duplicates = [
        'name' => ['name', 'source'],
        'code' => ['code', 'source', false], // override = false
    ];

    public function source()
    {
        return $this->belongsTo(SourceModel::class);
    }
}

