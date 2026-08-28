<?php

namespace App\Core\Infrastructure\Framework\Symfony\Controller;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;

class RequestParamsJsonValidator
{
    private array $errors;

    public function __construct(
        private string $jsonSchema
    )
    {
        $this->errors = [];
    }

    public function validate(array $params) : void
    {
        $jsonSchemaObject = json_decode($this->jsonSchema);

        $jsonValidator = new Validator();
        $jsonValidator->validate($params, $jsonSchemaObject, Constraint::CHECK_MODE_APPLY_DEFAULTS);

        if(!$jsonValidator->isValid()){
            foreach ($jsonValidator->getErrors() as $error) {
                $this->errors[] = sprintf("[%s] %s", $error['property'], $error['message']);
            }
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}