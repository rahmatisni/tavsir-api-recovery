<?php

namespace App\Models\Traits;

trait SortOrder
{
   public function scopeMySortOrder($query, $request)
   {    
        $sort = $request->sort_by ?? 'asc';
        $order = $request->order_by ?? '';
        if ($order) {
            if (in_array($sort, ['asc','desc', 'ASC', 'DESC'])) {
                // if(in_array($order,['id', ...$this->getFillable()])){
                    $query->orderBy($order, $sort);
                // }
            }
        }

        return $query;
   }
}
