<?php


namespace Swaggest\JsonSchemaMaker;

use Swaggest\JsonSchema\Constraint\Format;
use Swaggest\JsonSchema\Schema;

class InstanceFaker
{
    /** @var Schema */
    private $schema;


    /**
     * JsonSchemaToInstance constructor.
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
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

        if ($this->schema->type === null) {
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
            return (bool)mt_rand(0, 1);
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
            case 0: return $this->makeNumber();
            case 1: return round($this->makeNumber());
            case 2: return $this->makeString();
            default: return null;
        }
    }

    private function makeNumber()
    {
        if (isset($this->schema->{'example'})) {
            return $this->schema->{'example'};
        }

        $min = 0;
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

        if (isset($this->schema->{'example'})) {
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
            $ss = new self($this->schema->items);
            for ($i = 0; $i < $numItems; $i++) {
                $result [$i] = $ss->makeValue();
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
                $instanceFaker = new self($property);
                $result->$propertyName = $instanceFaker->makeValue();
            }
        }


        if ($this->schema->additionalProperties !== false) {
            if ($this->schema->additionalProperties instanceof Schema) {
                $instanceFaker = new self($this->schema->additionalProperties);
                $result->{$this->makeString()} = $instanceFaker->makeValue();

            } else {
                $result->{$this->makeString()} = $this->makeString();
            }
        }

        return $result;
    }

}