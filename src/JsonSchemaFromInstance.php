<?php

namespace Swaggest\JsonSchemaMaker;

use Swaggest\JsonDiff\JsonPointer;
use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;

class JsonSchemaFromInstance
{
    /** @var Schema */
    private $schema;

    /** @var Options */
    public $options;

    /**
     * JsonSchemaFromInstance constructor.
     * @param Schema $schema
     * @param Options|null $options
     */
    public function __construct(Schema $schema, Options $options = null)
    {
        $this->schema = $schema;
        if (null === $options) {
            $options = new Options();
        }
        $this->options = $options;
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
                $f = new JsonSchemaFromInstance($this->schema->items, $this->options);
                $f->addInstanceValue($item, $path . '.element');
            }
        }
    }

    private function addObject($instanceValue, $path)
    {
        if (null === $this->schema->properties) {
            $this->schema->setFromRef($this->options->defsPtr . JsonPointer::escapeSegment(ltrim($path, '.')));
            $this->schema->properties = new Properties();
        }
        $objVars = get_object_vars($instanceValue);
        foreach ($objVars as $propertyName => $propertyValue) {
            $property = $this->schema->properties->__get($propertyName);
            if (empty($property)) {
                $property = new Schema();
                $this->schema->setProperty($propertyName, $property);
            }
            if ($property instanceof Schema) {
                $f = new JsonSchemaFromInstance($property, $this->options);
                $f->addInstanceValue($propertyValue, $path . '.' . JsonPointer::escapeSegment($propertyName));
            }
        }
        if (!empty($this->schema->required)) {
            $removed = false;
            foreach ($this->schema->required as $i => $key) {
                if (!array_key_exists($key, $objVars)) {
                    $removed = true;
                    unset($this->schema->required[$i]);
                }
            }
            if ($removed) {
                $this->schema->required = array_values($this->schema->required);
                if (empty($this->schema->required)) {
                    $this->schema->required = null;
                }
            }
        }
    }

    private function addType($type)
    {
        if ($type === Schema::NULL) {
            if ($this->options->useNullable) {
                $this->schema->{'nullable'} = true;
                return;
            }

            if ($this->options->useXNullable) {
                $this->schema->{'x-nullable'} = true;
                return;
            }
        }

        if ($this->schema->type === null) {
            $this->schema->type = $type;
        } elseif (is_string($this->schema->type)) {
            if ($this->schema->type !== $type) {
                if ($this->options->upgradeIntToNumber &&
                    ($type === Schema::NUMBER || $type === Schema::INTEGER) &&
                    ($this->schema->type === Schema::NUMBER || $this->schema->type === Schema::INTEGER)) {
                    $this->schema->type = Schema::NUMBER;
                    return;
                }

                $this->schema->type = [$this->schema->type, $type];
            }
        } elseif (is_array($this->schema->type)) {
            if (!in_array($type, $this->schema->type)) {
                $this->schema->type[] = $type;
            }
            if ($this->options->upgradeIntToNumber && ($type === Schema::NUMBER || $type === Schema::INTEGER)) {
                $ii = $in = -1;
                foreach ($this->schema->type as $i => $t) {
                    if ($t === Schema::NUMBER) {
                        $in = $i;
                    } elseif ($t === Schema::INTEGER) {
                        $ii = $i;
                    }
                }
                if ($ii !== -1 && $in !== -1) {
                    unset($this->schema->type[$ii]);
                    $this->schema->type = array_values($this->schema->type);
                }
            }
        }
    }

}