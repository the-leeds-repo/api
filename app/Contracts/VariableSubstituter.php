<?php

namespace App\Contracts;

interface VariableSubstituter
{
    /**
     * @param string $content The entire content including variables that need substituting
     * @param array $variables
     * @return string|null
     */
    public function substitute(string $content, array $variables): ?string;
}
