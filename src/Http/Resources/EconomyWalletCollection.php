<?php

namespace Modules\Glitter\Economy\Http\Resources;

use App\Http\Resources\BaseApiCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class EconomyWalletCollection extends BaseApiCollection
{
    /**
     * @param  array<string, mixed>  $summary
     */
    public function __construct($resource, private array $summary = [])
    {
        parent::__construct($resource);
    }

    protected function abilityMap(): array
    {
        return [
            'can_view' => 'glitter-economy.admin.access',
            'can_adjust' => 'glitter-economy.adjust.access',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result = [
            'data' => $this->mapWithRowNumber(function ($wallet) use ($request) {
                return (new EconomyWalletResource($wallet))->resolve($request);
            }),
            'summary' => $this->summary,
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
