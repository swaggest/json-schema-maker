<?php


namespace Swaggest\JsonSchemaMaker\Tests;

use PHPUnit\Framework\TestCase;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchemaMaker\InstanceFaker;

class InstanceFakerTest extends TestCase
{
    public function testGithubExample()
    {
        $schema = Schema::import(json_decode(file_get_contents(__DIR__ . '/../resources/github-example-schema-with-examples.json')));

        mt_srand(1);

        $ss = new InstanceFaker($schema);

        $val = $ss->makeValue();

        $this->assertEquals(
            file_get_contents(__DIR__ . '/../resources/github-example-fake-instance.json'),
            json_encode($val, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES)
        );
//        file_put_contents(__DIR__ . '/../resources/github-example-fake-instance.json', json_encode($val, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES));
    }
}