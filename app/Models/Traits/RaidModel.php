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

    public function getCreatedAtAttribute($value)
    {
       $return = str_replace([
          'second',
          'seconds',
          'minute',
          'minutes',
          'hour',
          'hours',
          'day',
          'days',
          'week',
          'weeks',
          'month',
          'months',
          'year',
          'years',
          'ago'
        ], [
          'detik',
          'detik',
          'menit',
          'menit',
          'jam',
          'jam',
          'hari',
          'hari',
          'minggu',
          'minggu',
          'bulan',
          'bulan',
          'tahun',
          'tahun',
          'yang lalu'
        ], Carbon::parse($value)->diffForHumans());

        $return = str_replace(['detiks','menits','jams','haris','minggus','bulans','tahuns'], ['detik', 'menit','jam','hari','minggu','bulan','tahun'], $return);

        return $return;
    }


    public function getUpdatedAtAttribute($value)
    {
        if(!is_null($value)){
            $return = str_replace([
              'second',
              'seconds',
              'minute',
              'minutes',
              'hour',
              'hours',
              'day',
              'days',
              'week',
              'weeks',
              'month',
              'months',
              'year',
              'years',
              'ago'
            ], [
              'detik',
              'detik',
              'menit',
              'menit',
              'jam',
              'jam',
              'hari',
              'hari',
              'minggu',
              'minggu',
              'bulan',
              'bulan',
              'tahun',
              'tahun',
              'yang lalu'
            ], Carbon::parse($value)->diffForHumans());

            $return = str_replace(['detiks','menits','jams','haris','minggus','bulans','tahuns'], ['detik', 'menit','jam','hari','minggu','bulan','tahun'], $return);
        }else{
            return $this->created_at;
        }

        return $return;
    }

}
