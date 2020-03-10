<?php


namespace Swaggest\JsonSchemaMaker;

use Swaggest\JsonSchema\Constraint\Format;
use Swaggest\JsonSchema\Schema;
use Swaggest\JsonSchema\Wrapper;

class InstanceFaker
{
    /** @var Schema */
    private $schema;

    /** @var Options */
    private $options;

    /** @var integer */
    private $nesting = 0;

    /** @var string */
    private $path = '';

    /**
     * JsonSchemaToInstance constructor.
     * @param Schema|bool $schema
     */
    public function __construct($schema, Options $options = null)
    {
        $schema = Schema::unboolSchema($schema);

        if ($options === null) {
            $options = new Options();
        }
        $this->options = $options;
        $this->schema = $schema;
    }

    public function makeValue()
    {
        if (is_array($this->schema->enum)) {
            return $this->schema->enum[mt_rand(0, count($this->schema->enum) - 1)];
        }

        if ($this->schema->const !== null) {
            return $this->schema->const;
        }

        if (isset($this->schema->properties) || isset($this->schema->additionalProperties)) {
            return $this->makeObject();
        }

        if (isset($this->schema->oneOf)) {
            $i = mt_rand(0, count($this->schema->oneOf) - 1);
            if ($instanceFaker = $this->deeper($this->schema->oneOf[$i], Schema::names()->oneOf . $i)) {
                $val = $instanceFaker->makeValue();
                return $val;
            }
        }

        if (isset($this->schema->allOf)) {
            foreach ($this->schema->allOf as $i => $schema) {
                if ($schema instanceof Wrapper) {
                    $schema = $schema->exportSchema();
                }
                if ($instanceFaker = $this->deeper($schema, Schema::names()->allOf . $i)) {
                    $val = $instanceFaker->makeValue();
                    if ($val !== null) {
                        return $val;
                    }
                }
            }
        }

        if (isset($this->schema->anyOf)) {
            foreach ($this->schema->anyOf as $i => $schema) {
                if ($schema instanceof Wrapper) {
                    $schema = $schema->exportSchema();
                }
                if ($instanceFaker = $this->deeper($schema, Schema::names()->anyOf . $i)) {
                    $val = $instanceFaker->makeValue();
                    if ($val !== null) {
                        return $val;
                    }
                }
            }
        }

        if ($this->schema->type === null) {
            if (isset($this->schema->default)) {
                return $this->schema->default;
            }

            if (isset($this->schema->{'example'})) {
                return $this->schema->{'example'};
            }

            return null;
        }

        $types = $this->schema->type;
        if (!is_array($types)) {
            $types = [$types];
        }

        if (in_array(Schema::INTEGER, $types)) {
            return round($this->makeNumber());
        }

        if (in_array(Schema::NUMBER, $types)) {
            return $this->makeNumber();
        }

        if (in_array(Schema::BOOLEAN, $types)) {
            if (isset($this->schema->{'example'}) && (is_bool($this->schema->{'example'}))) {
                return $this->schema->{'example'};
            }
            return true;
        }

        if (in_array(Schema::STRING, $types)) {
            return $this->makeString();
        }

        if (in_array(Schema::_ARRAY, $types)) {
            return $this->makeArray();
        }

        if (in_array(Schema::OBJECT, $types)) {
            return $this->makeObject();
        }

        if (in_array(Schema::NULL, $types)) {
            return null;
        }

        return $this->makeAny();
    }

    private function makeAny()
    {
        switch (mt_rand(0, 3)) {
            case 0:
                return $this->makeNumber();
            case 1:
                return round($this->makeNumber());
            case 2:
                return $this->makeString();
            default:
                return null;
        }
    }

    private function makeNumber()
    {
        if (isset($this->schema->{'example'}) && (is_int($this->schema->{'example'}) || is_float($this->schema->{'example'}))) {
            return $this->schema->{'example'};
        }

        $min = 1;
        if ($this->schema->minimum !== null) {
            $min = $this->schema->minimum;
        }

        $max = 10000;
        if ($this->schema->maximum !== null) {
            $max = $this->schema->maximum;
        }

        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 3);
    }

    private function makeString()
    {
        if ($this->schema->format === Format::DATE_TIME) {
            return "2006-01-02T15:04:05Z";
        }

        if ($this->schema->format === Format::DATE) {
            return "2006-01-02";
        }

        if ($this->schema->format === 'uuid') {
            return '123e4567-e89b-12d3-a456-426655440000';
        }

        if (isset($this->schema->{'example'}) && is_string($this->schema->{'example'})) {
            return $this->schema->{'example'};
        }

        $length = mt_rand(2, 6);
        if ($this->schema->minLength !== null && $length < $this->schema->minLength) {
            $length = $this->schema->minLength;
        }

        if ($this->schema->maxLength !== null && $length > $this->schema->maxLength) {
            $length = $this->schema->maxLength;
        }

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= 'abcdef'[mt_rand(0, 5)];
        }

        return $result;
    }

    private function makeArray()
    {
        $numItems = 1;
        if ($this->schema->minItems !== null && $numItems < $this->schema->minItems) {
            $numItems = $this->schema->minItems;
        }

        if ($this->schema->maxItems !== null && $numItems > $this->schema->maxItems) {
            $numItems = $this->schema->maxItems;
        }

        $result = [];
        if ($this->schema->items instanceof Schema) {
            if ($instanceFaker = $this->deeper($this->schema->items, Schema::names()->items)) {
                for ($i = 0; $i < $numItems; $i++) {
                    $result [$i] = $instanceFaker->makeValue();
                }
            }
        } elseif (is_array($this->schema->items)) {
            foreach ($this->schema->items as $i => $itemSchema) {
                if ($instanceFaker = $this->deeper($itemSchema, Schema::names()->items . $i)) {
                    $result[] = $instanceFaker->makeValue();
                }
            }
        } else {
            $result[] = $this->makeString();
        }

        return $result;
    }

    private function makeObject()
    {
        $result = new \stdClass();
        if ($this->schema->properties !== null) {
            foreach ($this->schema->getProperties() as $propertyName => $property) {
                if ($instanceFaker = $this->deeper($property, $propertyName)) {
                    $val = $instanceFaker->makeValue();
                    if ($val !== null) {
                        $result->$propertyName = $val;
                    }
                }
            }
        }

        if (!empty($this->schema->additionalProperties) ||
            ($this->options->defaultAdditionalProperties && !isset($this->schema->additionalProperties))) {

            if ($this->schema->additionalProperties instanceof Schema) {
                if ($instanceFaker = $this->deeper($this->schema->additionalProperties, Schema::names()->additionalProperties)) {
                    $result->{$this->makeString()} = $instanceFaker->makeValue();
                }

            } else {
                $result->{$this->makeString()} = (new self(true))->makeString();
            }
        }

        return $result;
    }

    private function deeper($schema, $pathItem)
    {
        if ($this->nesting > $this->options->maxNesting) {
            return false;
        }
        $schema = Schema::unboolSchema($schema);

        $hash = spl_object_hash($schema);

        $newPath = $this->path . '/' . $hash . ':' . $pathItem;

        for ($i = 1; $i < strlen($newPath) / 2; $i++) {
            $tail = substr($newPath, -$i);
            $check = substr($newPath, -2 * $i, $i);

            if ($tail === $check) {
                return false;
            }
        }


        $instanceFaker = new self($schema, $this->options);
        $instanceFaker->nesting = $this->nesting + 1;
        $instanceFaker->path = $newPath;

        return $instanceFaker;
    }

}