# JSON Schema Maker

[![Build Status](https://travis-ci.org/swaggest/json-schema-maker.svg?branch=master)](https://travis-ci.org/swaggest/json-schema-maker)
[![Code Climate](https://codeclimate.com/github/swaggest/json-schema-maker/badges/gpa.svg)](https://codeclimate.com/github/swaggest/json-schema-maker)
[![codecov](https://codecov.io/gh/swaggest/json-schema-maker/branch/master/graph/badge.svg)](https://codecov.io/gh/swaggest/json-schema-maker)

Create JSON Schema from instance values and vice versa.

## Installation

```
composer require swaggest/json-schema-maker
```

## Usage

### CLI

[`json-cli build-schema`](https://github.com/swaggest/json-cli#buildschema)

```
v1.7.8 json-cli build-schema
JSON CLI tool, https://github.com/swaggest/json-cli
Usage: 
   json-cli build-schema <data> [schema]
   data     Path to data (JSON/YAML)
   schema   Path to parent schema   
   
Options: 
   --ptr-in-schema <ptrInSchema>           JSON pointer to structure in root schema, default #                      
   --ptr-in-data <ptrInData>               JSON pointer to structure in data, default #                             
   --jsonl                                 Data is a stream of JSON Lines                                           
   --use-nullable                          Use `nullable: true` instead of `type: null`, OAS 3.0 compatibility      
   --use-xnullable                         Use `x-nullable: true` instead of `type: null`, Swagger 2.0 compatibility
   --defs-ptr <defsPtr>                    Location to put new definitions. default: "#/definitions/"               
   --collect-examples                      Collect scalar values example                                            
   --heuristic-required                    Mark properties that are available in all samples as `required`.         
   --additional-data <additionalData...>   Additional paths to data                                                 
   --pretty                                Pretty-print result JSON                                                 
   --output <output>                       Path to output result, default STDOUT                                    
   --to-yaml                               Output in YAML format                                                    
   --to-serialized                         Output in PHP serialized format                                          
```

Basic example:
```
json-cli build-schema tests/assets/original.json 

{"properties":{"key1":{"items":{"type":"integer"},"type":"array"},"key2":{"type":"integer"},"key3":{"$ref":"#/definitions/key3"},"key4":{"items":{"$ref":"#/definitions/key4.element"},"type":"array"}},"type":"object","definitions":{"key3":{"properties":{"sub0":{"type":"integer"},"sub1":{"type":"string"},"sub2":{"type":"string"}},"type":"object"},"key4.element":{"properties":{"a":{"type":"integer"},"b":{"type":"boolean"}},"type":"object"}}}
```

Advanced example:

```
json-cli build-schema dump-responses.jsonl ./acme-service/swagger.json --ptr-in-schema "#/definitions/Orders" --jsonl --ptr-in-data "#/responseValue" --pretty --output swagger.json
```

Updates `swagger.json` with actual response samples provided in `dump-responses.jsonl`.

### Generating JSON schema based on instance values

```php
$instanceValue = json_decode(file_get_contents(__DIR__ . '/../resources/github-example.json'));
$schema = new \Swaggest\JsonSchema\Schema();
$f = new \Swaggest\JsonSchemaMaker\SchemaMaker($schema);
$f->options->upgradeIntToNumber = true; // Use `type: number` instead of `type: [integer, number]`.

$f->addInstanceValue($instanceValue);

$schemaJson = json_encode(\Swaggest\JsonSchema\Schema::export($schema));     // With object schemas extracted as definitions.
$schemaJsonInline = json_encode($schema);               // With inline object schemas.
```

See available [options](./src/Options.php).

See [example schemas](./tests/resources).

### Generating fake instance value based on JSON schema

```php
mt_srand(1); // Optionally seed random generator for reproducible results.
$schema = \Swaggest\JsonSchema\Schema::import(json_decode(file_get_contents(__DIR__ . '/../resources/github-example-schema-with-examples.json')));

$instanceFaker = new \Swaggest\JsonSchemaMaker\InstanceFaker($schema);
$value = $instanceFaker->makeValue();
$anotherValue = $instanceFaker->makeValue();
```

See available [options](./src/Options.php).

See [example value](./tests/resources/github-example-fake-instance.json).


## See also

* [Quicktype](https://app.quicktype.io/).