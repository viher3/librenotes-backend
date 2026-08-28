<?php

namespace App\Security\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Security\Application\Logout\LogoutCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class LogoutController extends BaseApiController
{
    /**
     * @throws \Throwable
     */
    public function __invoke(Request $request) : JsonResponse
    {
        if($badResponse = $this->validateInputData($this->getValidationSchema(), $request)){
            return $badResponse;
        }

        $body = $this->getParams($request);
        $this->dispatch(
            new LogoutCommand(
                token: $body['token']
            )
        );

        return new JsonResponse([]);
    }

    private function getValidationSchema(): string
    {
        return <<<'JSON'
            {
              "type": "array",
              "properties": {
                "token": {
                  "type": "string",
                  "minLength": 1
                }
              },
              "required": ["token"]
            }
            JSON;
    }

    protected function exceptions(): array
    {
        return [];
    }
}
