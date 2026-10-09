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

        // The request body is decoded to an associative PHP array, which json-schema reads as a JSON
        // *array*: with `"type": "array"` (the convention used by the controllers' schemas) keywords such
        // as `required` are silently ignored. Schemas that describe fields (`properties`) are therefore
        // validated against the body as a JSON *object*, which is what they mean.
        if (is_object($jsonSchemaObject) && isset($jsonSchemaObject->properties)) {
            if (($jsonSchemaObject->type ?? null) === 'array') {
                $jsonSchemaObject->type = 'object';
            }
            $data = $params === [] ? new \stdClass() : json_decode((string) json_encode($params));
        } else {
            $data = $params;
        }

        $jsonValidator = new Validator();
        $jsonValidator->validate($data, $jsonSchemaObject, Constraint::CHECK_MODE_APPLY_DEFAULTS);

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