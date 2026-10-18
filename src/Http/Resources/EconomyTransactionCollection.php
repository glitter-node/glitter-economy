<?php

namespace Modules\Glitter\Economy\Http\Resources;

use App\Http\Resources\BaseApiCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class EconomyTransactionCollection extends BaseApiCollection
{
    protected function abilityMap(): array
    {
        return [
            'can_view' => 'glitter-economy.transactions.view',
            'can_export' => 'glitter-economy.transactions.export',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result = [
            'data' => $this->mapWithRowNumber(function ($transaction) use ($request) {
                return (new EconomyTransactionResource($transaction))->resolve($request);
            }),
            'abilities' => $this->resolveCollectionAbilities($request),
        ];

        if ($this->resource instanceof LengthAwarePaginator) {
            $result['pagination'] = [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
                'has_more_pages' => $this->resource->hasMorePages(),
            ];
        }

        return $result;
    }
}
