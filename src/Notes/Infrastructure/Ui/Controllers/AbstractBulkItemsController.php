<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Ui\Controllers;

use App\Core\Infrastructure\Framework\Symfony\Controller\BaseApiController;
use App\Notes\Domain\ValueObject\ItemRef;
use App\Notes\Domain\ValueObject\ItemType;

/** Shared by the bulk endpoints: the list of selected items (1 to 100 of them). */
abstract class AbstractBulkItemsController extends BaseApiController
{
    protected const string ITEMS_SCHEMA = <<<'JSON'
        {
          "type": "array",
          "minItems": 1,
          "maxItems": 100,
          "items": {
            "type": "object",
            "properties": {
              "type": {"type": "string", "enum": ["folder", "note", "link", "attachment"]},
              "id": {"type": "string", "format": "uuid"}
            },
            "required": ["type", "id"]
          }
        }
        JSON;

    /**
     * @param array<int, array{type: string, id: string}> $items
     *
     * @return ItemRef[] repeated items are collapsed
     */
    protected function itemRefs(array $items): array
    {
        $refs = [];
        foreach ($items as $item) {
            $ref = new ItemRef(ItemType::from($item['type']), $item['id']);
            $refs[$ref->key()] = $ref;
        }

        return array_values($refs);
    }
}
