<?php

namespace App\Tests\Acceptance\Shared;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

/**
 * Guards the published OpenAPI document (clients generate their API types from it): it must be valid
 * and must describe every endpoint the application serves.
 */
class OpenApiDocumentationTest extends WebTestCase
{
    private const array HTTP_METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    private function document(): array
    {
        $client = static::createClient();
        $client->request('GET', '/api/doc.json');

        $this->assertResponseIsSuccessful();
        $document = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($document);
        $this->assertArrayHasKey('paths', $document);

        return $document;
    }

    public function testEveryApplicationRouteIsDocumented(): void
    {
        $document = $this->document();
        $documented = [];
        foreach ($document['paths'] as $path => $operations) {
            foreach (array_keys($operations) as $method) {
                if (in_array($method, self::HTTP_METHODS, true)) {
                    $documented[] = strtoupper($method) . ' ' . $this->normalize($path);
                }
            }
        }

        /** @var RouterInterface $router */
        $router = static::getContainer()->get('router');
        $missing = [];
        foreach ($router->getRouteCollection() as $name => $route) {
            $controller = (string) $route->getDefault('_controller');
            if (!str_starts_with($controller, 'App\\') || $route->getPath() === '/') {
                continue; // framework routes and the landing page are not part of the API
            }
            foreach ($route->getMethods() ?: ['GET'] as $method) {
                $key = $method . ' ' . $this->normalize($route->getPath());
                if (!in_array($key, $documented, true)) {
                    $missing[] = $key . " ($name)";
                }
            }
        }

        $this->assertSame([], $missing, "Routes missing from the OpenAPI document:\n" . implode("\n", $missing));
    }

    public function testTheDocumentHasNoEmptyListsWhereASchemaIsExpected(): void
    {
        // PHP serializes an empty schema ({}) as [], which makes the document invalid for code generators.
        $defects = [];
        $walk = function (mixed $node, string $path) use (&$walk, &$defects): void {
            if (!is_array($node)) {
                return;
            }
            foreach ($node as $key => $value) {
                $here = $path . '/' . $key;
                if ($value === [] && in_array($key, ['_result', 'items', 'properties', 'additionalProperties', 'schema'], true)) {
                    $defects[] = $here;
                }
                $walk($value, $here);
            }
        };
        $walk($this->document(), '');

        $this->assertSame([], $defects);
    }

    private function normalize(string $path): string
    {
        return rtrim((string) preg_replace('/\{[^}]+}/', '{}', $path), '/') ?: '/';
    }
}
