<?php

namespace Denizgolbas\EloquentHasduplicateAttirbutes;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait for duplicating attributes from related models.
 *
 * Usage in model:
 * ```
 * protected array $duplicates = [
 *     // Default: override = true (her zaman source'dan kopyala)
 *     'local_field' => ['related_field', 'relation'],
 *
 *     // Override = false (sadece boş ise source'dan kopyala, dolu ise dokunma)
 *     'slip_no' => ['slip_no', 'source', false],
 * ];
 * ```
 *
 * @mixin Model
 */
trait HasDuplicateAttributes
{
    /**
     * Determines whether attributes should be copied on events or not.
     *
     * Resets after every event cycle.
     *
     * @var bool
     */
    protected bool $shouldCopyRelatedAttributes = true;

    protected static function bootHasDuplicateAttributes(): void
    {
        /** @var Model $this */
        static::creating(fn (self $model) => $model->performCopyRelatedAttributes());
        static::updating(fn (self $model) => $model->performCopyRelatedAttributes());
        static::saving(fn (self $model) => $model->performCopyRelatedAttributes());
        
        // Reset flag after save operation completes
        static::saved(fn (self $model) => $model->shouldCopyRelatedAttributes = true);
    }

    protected function performCopyRelatedAttributes(): void
    {
        if (!$this->shouldCopyRelatedAttributes)
        {
            // Don't reset flag here - keep it false for the entire save cycle
            // It will be reset after the save operation completes
            return;
        }

        if (!isset($this->duplicates))
        {
            return;
        }

        $methods = [];

        foreach ($this->duplicates as $local_field => $config)
        {
            if (is_array($config) && count($config) >= 2 && method_exists($this, $config[1]))
            {
                $related_field = $config[0];
                $relation = $config[1];
                // Üçüncü parametre override: true (default) = her zaman kopyala, false = boş ise kopyala
                $override = $config[2] ?? true;

                $methods[$relation][$local_field] = [
                    'related_field' => $related_field,
                    'override'      => $override,
                ];
            }
        }

        foreach ($methods as $method => $bag)
        {
            $model = null;

            // Always reload relation to get fresh data, especially on updates
            // Unset the relation to force reload
            if ($this->relationLoaded($method))
            {
                unset($this->relations[$method]);
            }

            // Load the relation (fresh data)
            $this->load($method);
            $relation = $this->getRelation($method);

            // If relation is a Model, use it directly
            if ($relation instanceof Model)
            {
                $model = $relation;
            }
            // If relation is a Collection, get first item
            elseif ($relation instanceof Collection)
            {
                $model = $relation->first();
            }

            if ($model)
            {
                foreach ($bag as $local_field => $fieldConfig)
                {
                    $related_field = $fieldConfig['related_field'];
                    $override = $fieldConfig['override'];

                    // Override = true: her zaman kopyala
                    // Override = false: sadece boş ise kopyala
                    if ($override || empty($this->{$local_field}))
                    {
                        $this->{$local_field} = $model->{$related_field};
                    }
                }
            }
        }

        // Reset flag after performing copy operation
        $this->shouldCopyRelatedAttributes = true;
    }

    public function withoutCopyingRelatedAttributes(): self
    {
        $this->shouldCopyRelatedAttributes = false;

        return $this;
    }
}

