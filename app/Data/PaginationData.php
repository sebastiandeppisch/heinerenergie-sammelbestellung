<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * @template TValue
 */
#[TypeScript]
class PaginationData extends Data
{
    public function __construct(
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $lastPage,
    ) {}

    /**
     * @param  LengthAwarePaginator<int, TValue>  $paginator
     * @return self<TValue>
     */
    public static function fromPagination(LengthAwarePaginator $paginator): self
    {
        return new self(
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
        );
    }
}
