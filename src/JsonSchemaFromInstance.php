<?php

namespace Swaggest\JsonSchemaMaker;

use Swaggest\JsonDiff\JsonPointer;
use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;

class JsonSchemaFromInstance
{
    /** @var Schema */
    private $schema;

    /**
     * JsonSchemaFromInstance constructor.
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function addInstanceValue($instanceValue, $path = '')
    {
        if (is_object($instanceValue)) {
            $this->addType(Schema::OBJECT);
            $this->addObject($instanceValue, $path);
        } elseif (is_array($instanceValue)) {
            $this->addType(Schema::_ARRAY);
            $this->addArray($instanceValue, $path);
        } elseif (is_integer($instanceValue)) {
            $this->addType(Schema::INTEGER);
        } elseif (is_string($instanceValue)) {
            $this->addType(Schema::STRING);
        } elseif (is_bool($instanceValue)) {
            $this->addType(Schema::BOOLEAN);
        } elseif (null === $instanceValue) {
            $this->addType(Schema::NULL);
        } elseif (is_float($instanceValue)) {
            $this->addType(Schema::NUMBER);
        }
    }

    private function addArray($instanceValue, $path)
    {
        if (!empty($instanceValue)) {
            if ($this->schema->items === null) {
                $this->schema->items = new Schema();
            }
        }

        foreach ($instanceValue as $item) {
            if ($this->schema->items instanceof Schema) {
                $f = new JsonSchemaFromInstance($this->schema->items);
                $f->addInstanceValue($item, $path . '.element');
            }
        }
    }

    private function addObject($instanceValue, $path)
    {
        if (null === $this->schema->properties) {
            $this->schema->setFromRef('#/definitions/' . JsonPointer::escapeSegment(ltrim($path, '.')));
            $this->schema->properties = new Properties();
        }
        foreach (get_object_vars($instanceValue) as $propertyName => $propertyValue) {
            $property = $this->schema->properties->__get($propertyName);
            if (empty($property)) {
                $property = new Schema();
                $this->schema->setProperty($propertyName, $property);
            }
            if ($property instanceof Schema) {
                $f = new JsonSchemaFromInstance($property);
                $f->addInstanceValue($propertyValue, $path . '.' . JsonPointer::escapeSegment($propertyName));
            }
        }
    }

    private function addType($type)
    {
        if ($this->schema->type === null) {
            $this->schema->type = $type;
        } elseif (is_string($this->schema->type)) {
            if ($this->schema->type !== $type) {
                $this->schema->type = [$this->schema->type, $type];
            }
        } elseif (is_array($this->schema->type)) {
            if (!in_array($type, $this->schema->type)) {
                $this->schema->type[] = $type;
            }
        }
    }

}