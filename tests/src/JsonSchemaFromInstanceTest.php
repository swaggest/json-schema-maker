<?php

namespace Swaggest\JsonSchemaMaker\Tests;

use PHPUnit\Framework\TestCase;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchemaMaker\JsonSchemaFromInstance;

class JsonSchemaFromInstanceTest extends TestCase
{
    public function testSimple()
    {
        $instanceValue = (object)[
            'name' => 'Jane',
            'age' => 25,
            'orders' => [
                (object)[
                    'id' => 123,
                    'value' => 100,
                ],
                (object)[
                    'id' => 456,
                    'value' => 100.5,
                    'extra' => [1, 2, "abc"]
                ],

            ],
        ];

        $schema = new Schema();
        $f = new JsonSchemaFromInstance($schema);
        $f->addInstanceValue($instanceValue);

        $this->assertEquals(<<<'JSON'
{
    "properties": {
        "name": {
            "type": "string"
        },
        "age": {
            "type": "integer"
        },
        "orders": {
            "items": {
                "$ref": "#/definitions/orders.element"
            },
            "type": "array"
        }
    },
    "type": "object",
    "definitions": {
        "orders.element": {
            "properties": {
                "id": {
                    "type": "integer"
                },
                "value": {
                    "type": "number"
                },
                "extra": {
                    "items": {
                        "type": [
                            "integer",
                            "string"
                        ]
                    },
                    "type": "array"
                }
            },
            "type": "object"
        }
    }
}
JSON
            , json_encode(Schema::export($schema), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));

    }

    public function testGithubExample()
    {
        $instanceValue = json_decode(file_get_contents(__DIR__ . '/../resources/github-example.json'));
        $schema = new Schema();
        $f = new JsonSchemaFromInstance($schema);
        $f->addInstanceValue($instanceValue);

        $schemaJson = Schema::export($schema);

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../resources/github-example-schema.json'),
            json_encode($schemaJson, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES)
        );
//        file_put_contents(__DIR__ . '/../resources/github-example-schema.json', json_encode($schemaJson, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../resources/github-example-schema-inline.json'),
            json_encode($schema, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES)
        );
//        file_put_contents(__DIR__ . '/../resources/github-example-schema-inline.json', json_encode($schema, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
    }
}