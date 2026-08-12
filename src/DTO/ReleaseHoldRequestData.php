<?php

namespace Modules\Glitter\Economy\DTO;

final readonly class ReleaseHoldRequestData
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $holdId,
        public ?int $releasedBy = null,
        public array $context = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'hold_id' => $this->holdId,
            'released_by' => $this->releasedBy,
            'context' => $this->context,
        ];
    }
}
