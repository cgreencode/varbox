<?php

namespace Varbox\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Varbox\Options\ActivityOptions;
use Varbox\Options\DuplicateOptions;
use Varbox\Options\RevisionOptions;
use Varbox\Traits\HasActivity;
use Varbox\Traits\HasDuplicates;
use Varbox\Traits\HasRevisions;
use Varbox\Traits\HasUploads;
use Varbox\Traits\IsCacheable;
use Varbox\Traits\IsDraftable;
use Varbox\Traits\IsFilterable;
use Varbox\Traits\IsSortable;
use Varbox\Contracts\BlockModelContract;

class Block extends Model implements BlockModelContract
{
    use HasUploads;
    use HasRevisions;
    use HasDuplicates;
    use HasActivity;
    use IsDraftable;
    use IsCacheable;
    use IsFilterable;
    use IsSortable;
    use SoftDeletes;

    /**
     * The database table.
     *
     * @var string
     */
    protected $table = 'blocks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'anchor',
        'data',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'drafted_at',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Boot the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleted(function (BlockModelContract $block) {
            if ($block->forceDeleting === true) {
                DB::table('blockables')->whereBlockId($block->id)->delete();
            }
        });
    }

    /**
     * Get all of the records of a single entity type that are assigned to this block.
     *
     * @param string $related
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function blockables($related)
    {
        return $this->morphedByMany($related, 'blockable')->withPivot([
            'id', 'location', 'ord'
        ])->withTimestamps();
    }

    /**
     * Sort the query alphabetically by name.
     *
     * @param Builder $query
     */
    public function scopeAlphabetically($query)
    {
        $query->orderBy('name', 'asc');
    }

    /**
     * Get the specific upload config parts for this model.
     *
     * @return array
     */
    public function getUploadConfig()
    {
        return config('varbox.blocks.upload', []);
    }

    /**
     * @return RevisionOptions
     */
    public function getRevisionOptions()
    {
        return RevisionOptions::instance()
            ->limitRevisionsTo(100);
    }

    /**
     * Set the options for the HasDuplicates trait.
     *
     * @return DuplicateOptions
     */
    public function getDuplicateOptions()
    {
        return DuplicateOptions::instance()
            ->uniqueColumns('name');
    }

    /**
     * Set the options for the HasActivity trait.
     *
     * @return ActivityOptions
     */
    public function getActivityOptions()
    {
        return ActivityOptions::instance()
            ->withEntityType('block')
            ->withEntityName($this->name)
            ->withEntityUrl(route('admin.blocks.edit', $this->getKey()));
    }
}
