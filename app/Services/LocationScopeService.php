<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LocationScopeService
{
    public function descendantIds(Location $location): Collection
    {
        $rows = DB::select(
            '
            with recursive location_tree as (
                select id
                from locations
                where id = ?

                union all

                select child.id
                from locations child
                inner join location_tree parent
                    on child.parent_id = parent.id
            )

            select id
            from location_tree
            ',
            [$location->id]
        );

        return collect($rows)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    public function descendants(Location $location): Collection
    {
        return Location::query()
            ->whereIn(
                'id',
                $this->descendantIds($location)
            )
            ->get();
    }
}