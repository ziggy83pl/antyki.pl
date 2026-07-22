<?php
declare(strict_types=1);

namespace App\Core;

use Exception;

class ValidationException extends Exception {
    public function __construct(string $message = "", protected array $errors = []) {
        parent::__construct($message);
    }

    public function getErrors(): array {
        return $this->errors;
    }
}
