<?php

declare(strict_types=1);

namespace App\VariableSubstitution;

use App\Contracts\VariableSubstituter;

class DoubleParenthesisVariableSubstituter implements VariableSubstituter
{
    /**
     * @inheritDoc
     */
    public function substitute(string $content, array $variables): ?string
    {
        $this->validateVariables($variables);

        foreach (array_keys($variables) as $supportedVariable) {
            $content = str_replace(
                "(({$supportedVariable}))",
                $variables[$supportedVariable],
                $content
            );
        }

        return $content;
    }

    /**
     * @param array $variables
     */
    protected function validateVariables(array $variables): void
    {
        foreach ($variables as $key => $value) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException('The variable keys must be strings.');
            }

            if (!is_scalar($value)) {
                throw new \InvalidArgumentException('The variable values must be scalars.');
            }
        }
    }
}
