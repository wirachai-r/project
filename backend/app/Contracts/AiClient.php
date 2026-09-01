<?php

namespace App\Contracts;

interface AiClient
{
    /** @return array<string, mixed> */
    public function generateStructured(string $instructions, array $input, array $schema): array;
}
