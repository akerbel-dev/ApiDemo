<?php

namespace App\Dto;

use Symfony\Component\HttpFoundation\Request;

final class PaginationDto
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $offset,
    ) {
    }

    public static function fromRequest(Request $request, int $maxLimit = 100, int $defaultLimit = 20): self
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min($maxLimit, max(1, (int) $request->query->get('limit', $defaultLimit)));
        $offset = ($page - 1) * $limit;

        return new self($page, $limit, $offset);
    }
}
