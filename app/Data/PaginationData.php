<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Illuminate\Pagination\LengthAwarePaginator;

#[TypeScript]
class PaginationData extends Data
{
    public function __construct(
        public int $total,
        public int $perPage,
        public int $currentPage,
        public int $lastPage,
    ){}

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
