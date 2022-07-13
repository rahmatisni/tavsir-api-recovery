<?php
namespace App\Models\Traits;

use App\Models\User;
use Carbon\Carbon;

trait RaidModel
{
    public static function boot()
    {
        parent::boot();
        if (auth()->check()) {
            if (\Schema::hasColumn(with(new static )->getTable(), 'updated_by')) {
                static::saving(function ($table) {
                    $table->updated_by = auth()->user()->id;
                });
            }

            if (\Schema::hasColumn(with(new static )->getTable(), 'created_by')) {
                static::creating(function ($table) {
                    $table->updated_by = null;
                    $table->updated_at = null;
                    $table->created_by = auth()->user()->id;
                });
            }
        }
    }

    
    /*-----------*/
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creatorBy()
    {
        return isset($this->creator) ? $this->creator->name : '[System]';
    }

    public function creatorDate()
    {
        return $this->created_at->diffForHumans();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function updaterBy()
    {
        return isset($this->updater) ? $this->updater->name : '[System]';
    }

    public function updaterDate()
    {
        return $this->created_at->diffForHumans();
    }

   
    /* save data */
    public static function saveData($request, $identifier = 'id')
    {
        $record = static::prepare($request, $identifier);
        $record->fill($request);
        $record->save();

        // return $record;
    }


    public static function prepare($request, $identifier = 'id')
    {
        $record = new static;

        if ($request->has($identifier) && $request->get($identifier) != null && $request->get($identifier) != 0) {
            $record = static::find($request->get($identifier));
        }

        return $record;
    }

}
