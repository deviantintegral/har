<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Validation;

/**
 * Represents the result of validating a HAR file or object.
 */
final class ValidationResult
{
    /**
     * @param ValidationError[] $errors
     */
    public function __construct(
        private readonly array $errors = [],
    ) {
    }

    /**
     * Check if the validation passed (no errors).
     */
    public function isValid(): bool
    {
        return [] === $this->errors;
    }

    /**
     * Get all validation errors.
     *
     * @return ValidationError[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get the count of validation errors.
     */
    public function getErrorCount(): int
    {
        return \count($this->errors);
    }

    /**
     * Create a valid result with no errors.
     */
    public static function valid(): self
    {
        return new self([]);
    }

    /**
     * Create an invalid result with the given errors.
     *
     * @param ValidationError[] $errors
     */
    public static function invalid(array $errors): self
    {
        return new self($errors);
    }

    /**
     * Merge multiple validation results into one.
     */
    public static function merge(self ...$results): self
    {
        $errors = [];
        foreach ($results as $result) {
            $errors = array_merge($errors, $result->getErrors());
        }

        return new self($errors);
    }
}
