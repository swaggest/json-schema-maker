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

### Generating JSON schema based on instance values

```php
$instanceValue = json_decode(file_get_contents(__DIR__ . '/../resources/github-example.json'));
$schema = new \Swaggest\JsonSchema\Schema();
$f = new \Swaggest\JsonSchemaMaker\SchemaMaker($schema);
$f->addInstanceValue($instanceValue);

$schemaJson = json_encode(\Swaggest\JsonSchema\Schema::export($schema));     // With object schemas extracted as definitions.
$schemaJsonInline = json_encode($schema);               // With inline object schemas.
```

See [example schemas](./tests/resources).

### Generating fake instance value based on JSON schema

```php
mt_srand(1); // Optionally seed random generator for reproducible results.
$schema = \Swaggest\JsonSchema\Schema::import(json_decode(file_get_contents(__DIR__ . '/../resources/github-example-schema-with-examples.json')));

$instanceFaker = new \Swaggest\JsonSchemaMaker\InstanceFaker($schema);
$value = $instanceFaker->makeValue();
$anotherValue = $instanceFaker->makeValue();
```

See [example value](./tests/resources/github-example-fake-instance.json).


## See also

* [Quicktype](https://app.quicktype.io/).