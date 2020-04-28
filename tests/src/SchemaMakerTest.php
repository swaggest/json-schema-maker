<?php

namespace Swaggest\JsonSchemaMaker\Tests;

use PHPUnit\Framework\TestCase;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchemaMaker\SchemaMaker;
use Swaggest\JsonSchemaMaker\Options;

class SchemaMakerTest extends TestCase
{
    private $instanceValue;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->instanceValue = (object)[
            'name' => 'Jane',
            'age' => 25,
            'orders' => [
                (object)[
                    'id' => 123,
                    'value' => 100,
                ],
                (object)[
                    'id' => 123,
                    'value' => null,
                ],
                (object)[
                    'id' => 456,
                    'value' => 100.5,
                    'extra' => [1, 2, "abc"]
                ],
            ],
        ];
    }


    public function testSimple()
    {
        $schema = new Schema();
        $f = new SchemaMaker($schema);
        $f->addInstanceValue($this->instanceValue);

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
                    "type": [
                        "null",
                        "number"
                    ]
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


    public function testSimpleNullable()
    {
        $schema = new Schema();
        $f = new SchemaMaker($schema);
        $f->options->useNullable = true;
        $f->addInstanceValue($this->instanceValue);

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
                    "type": "number",
                    "nullable": true
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

    public function testSimpleXNullable()
    {
        $schema = new Schema();
        $f = new SchemaMaker($schema);
        $f->options->useXNullable = true;
        $f->addInstanceValue($this->instanceValue);

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
                    "type": "number",
                    "x-nullable": true
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
        $f = new SchemaMaker($schema);
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

        $schema = new Schema();
        $options = new Options();
        $options->collectExamples = true;
        $withExamples = new SchemaMaker($schema, $options);
        $withExamples->addInstanceValue($instanceValue);

        $schemaJson = Schema::export($schema);

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../resources/github-example-schema-with-examples.json'),
            json_encode($schemaJson, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES)
        );
//        file_put_contents(__DIR__ . '/../resources/github-example-schema-with-examples.json', json_encode($schemaJson, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
    }


    public function testJsonPatchTests()
    {
        $instanceValue = json_decode(file_get_contents(__DIR__ . '/../resources/tests.json'));
        $schema = new Schema();
        $b = new SchemaMaker($schema);
        $b->addInstanceValue($instanceValue);

        $s = Schema::export($schema);
        $this->assertEquals(file_get_contents(__DIR__ . '/../resources/tests-schema.json'),
            json_encode($s, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
    }

    public function testObjectOrString()
    {
        $instanceValue = json_decode(<<<'JSON'
[
  "abc",
  {
    "id": "def",
    "val": 1
  }
]
JSON
        );
        $schema = new Schema();
        $b = new SchemaMaker($schema);
        $b->addInstanceValue($instanceValue);

        $expected = <<<'JSON'
{
    "items": {
        "$ref": "#/definitions/element"
    },
    "type": "array",
    "definitions": {
        "element": {
            "properties": {
                "id": {
                    "type": "string"
                },
                "val": {
                    "type": "integer"
                }
            },
            "type": [
                "string",
                "object"
            ]
        }
    }
}
JSON;

        $s = Schema::export($schema);
        $this->assertEquals($expected, json_encode($s, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));

    }

    public function testHeuristicRequired()
    {
        $instanceValue = json_decode(<<<'JSON'
[
  {
    "everywhere": "def"
  },
  {
    "everywhere": "def",
    "somewhere": 1
  },
  {
    "everywhere": "def",
    "somewhere": 1
  }
]
JSON
        );
        $schema = new Schema();
        $b = new SchemaMaker($schema);
        $b->options->heuristicRequired = true;
        $b->addInstanceValue($instanceValue);

        $expected = <<<'JSON'
{
    "items": {
        "$ref": "#/definitions/element"
    },
    "type": "array",
    "definitions": {
        "element": {
            "required": [
                "everywhere"
            ],
            "properties": {
                "everywhere": {
                    "type": "string"
                },
                "somewhere": {
                    "type": "integer"
                }
            },
            "type": "object"
        }
    }
}
JSON;

        $s = Schema::export($schema);
        $this->assertEquals($expected, json_encode($s, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));

    }
}