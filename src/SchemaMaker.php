<?php

namespace Swaggest\JsonSchemaMaker;

use Swaggest\JsonDiff\JsonPointer;
use Swaggest\JsonSchema\Constraint\Properties;
use Swaggest\JsonSchema\Schema;

class SchemaMaker
{
    /** @var Schema */
    private $schema;

    /** @var Options */
    public $options;

    /**
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
        $passes = 1;
        if ($this->options->heuristicRequired) {
            // Two passes are needed for heuristic required population to ensure missing properties in first samples
            // are honored.
            $passes = 2;
        }

        for ($i = 0; $i < $passes; $i++) {
            if (is_object($instanceValue)) {
                $this->addType(Schema::OBJECT);
                $this->addObject($instanceValue, $path);
            } elseif (is_array($instanceValue)) {
                $this->addType(Schema::_ARRAY);
                $this->addArray($instanceValue, $path);
            } elseif (is_integer($instanceValue)) {
                $this->addExample($instanceValue);
                $this->addType(Schema::INTEGER);
            } elseif (is_string($instanceValue)) {
                $this->addExample($instanceValue);
                $this->addType(Schema::STRING);
            } elseif (is_bool($instanceValue)) {
                $this->addType(Schema::BOOLEAN);
            } elseif (null === $instanceValue) {
                $this->addType(Schema::NULL);
            } elseif (is_float($instanceValue)) {
                $this->addExample($instanceValue);
                $this->addType(Schema::NUMBER);
            }
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
                $f = new SchemaMaker($this->schema->items, $this->options);
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

        // Required State is a map reflecting state of property existence across all samples.
        // If all samples contain property, the value is `true`.
        // If any of samples does not contain property, the value is `false`.
        // If value is not set, the property was not found in any of samples.
        $requiredState = $this->schema->getMeta('requiredState');
        if (null === $requiredState) {
            $requiredState = [];
        }

        $objVars = get_object_vars($instanceValue);
        foreach ($requiredState as $propertyName => $state) {
            if (!array_key_exists($propertyName, $objVars)) {
                $requiredState[$propertyName] = false;
            }
        }
        foreach ($objVars as $propertyName => $propertyValue) {
            $property = $this->schema->properties->__get($propertyName);
            if (empty($property)) {
                $property = new Schema();
                $this->schema->setProperty($propertyName, $property);
            }
            if ($property instanceof Schema) {
                $f = new SchemaMaker($property, $this->options);
                $f->addInstanceValue($propertyValue, $path . '.' . JsonPointer::escapeSegment($propertyName));
            }

            if (!array_key_exists($propertyName, $requiredState)) {
                $requiredState[$propertyName] = true;
            }
        }

        if (!empty($this->schema->required)) {
            $removed = false;

            foreach ($this->schema->required as $i => $key) {
                if (empty($requiredState[$key])) {
                    $removed = true;
                    unset($this->schema->required[$i]);
                }
            }

            if ($removed) {
                $this->schema->required = array_values($this->schema->required);
                if (empty($this->schema->required)) {
                    unset($this->schema->required);
                }
            }
        }

        if ($this->options->heuristicRequired) {
            foreach ($requiredState as $propertyName => $state) {
                if ($state === true) {
                    $this->schema->required [] = $propertyName;
                }
            }

            $this->schema->required = array_values(array_unique($this->schema->required));
        }


        $this->schema->addMeta($requiredState, 'requiredState');
    }

    private function addExample($value)
    {
        // Skip zero values.
        if ($value === 0 || $value === 0.0 || $value === '') {
            return;
        }

        if ($this->options->collectExamples && !isset($this->schema->{'example'}) && !isset($this->schema->{'examples'})) {
            $this->schema->{'example'} = $value;
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