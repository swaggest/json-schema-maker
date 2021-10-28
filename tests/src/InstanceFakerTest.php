<?php


namespace Swaggest\JsonSchemaMaker\Tests;

use PHPUnit\Framework\TestCase;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchemaMaker\InstanceFaker;

class InstanceFakerTest extends TestCase
{
    public function testGithubExample()
    {
        mt_srand(1);
        $schema = Schema::import(json_decode(file_get_contents(__DIR__ . '/../resources/github-example-schema-with-examples.json')));

        $instanceFaker = new InstanceFaker($schema);

        $val = $instanceFaker->makeValue();

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../resources/github-example-fake-instance.json'),
            json_encode($val, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES)
        );
//        file_put_contents(__DIR__ . '/../resources/github-example-fake-instance.json', json_encode($val, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
    }

    public function testRequiredProp()
    {
        $schemaData = <<<'JSON'
{
  "type": "object",
  "required": [
    "$ref"
  ],
  "additionalProperties": false,
  "properties": {
    "$ref": {
      "type": "string",
      "examples": ["#/components/parameters/foo"],
      "format": "uri-reference",
      "oneOf": [
        {
          "pattern": "^#/components/parameters/"
        },
        {
          "not": {
            "pattern": "^#/"
          }
        }
      ]
    }
  }
}
JSON;
        $schema = Schema::import(json_decode($schemaData));
        $instanceFaker = new InstanceFaker($schema);

        $val = $instanceFaker->makeValue();
        $this->assertEquals('{"$ref":"#\/components\/parameters\/foo"}', json_encode($val));
    }

    public function testSimple() {
        $schema = Schema::import(json_decode(<<<'JSON'
{
  "properties": {
    "foo": {
      "type": "string"
    },
    "bar": {
      "enum": ["baz", "quux"]
    }
  }
}
JSON
));
        mt_srand(1);
        $instanceFaker = new InstanceFaker($schema);

        $val = $instanceFaker->makeValue();
        $this->assertEquals('{"foo":"cbbfff","bar":"baz","defba":"dcea"}', json_encode($val));
    }
}