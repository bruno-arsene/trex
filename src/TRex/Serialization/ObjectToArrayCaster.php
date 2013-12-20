<?php
namespace TRex\Serialization;

use TRex\Core\Object;
use TRex\Reflection\AttributeReflection;
use TRex\Reflection\ObjectReflection;

/**
 * Class ObjectToArrayCaster.
 * Object conversion handler.
 *
 * @package TRex\Serialization
 * @transient
 */
class ObjectToArrayCaster extends Object
{

    /**
     * Value of casted recursion.
     */
    const RECURSION_VALUE = '*_recursion_*';

    /**
     * Indicate the type of value for casted recursion.
     * If is true, casted recursion will be self::RECURSION_VALUE.
     * If is false, casted recursion will not appears.
     *
     * @var bool
     */
    private $isExplicitRecursion = false;

    /**
     * List of object already casted.
     *
     * @var array
     */
    private $castedObjects = array();

    /**
     * Setter of $isExplicitRecursion.
     *
     * @param boolean $isExplicitRecursion
     */
    public function setIsExplicitRecursion($isExplicitRecursion)
    {
        $this->isExplicitRecursion = $isExplicitRecursion;
    }

    /**
     * Getter of $isExplicitRecursion.
     *
     * @return boolean
     */
    public function isExplicitRecursion()
    {
        return $this->isExplicitRecursion;
    }

    /**
     * Convert an object to an array.
     * The exported array contains all property values of the class and its parents, which are not transient.
     *
     * $filter allows you to filter by visibility properties.
     *
     * If $isFullName is true, array keys are composed by the class name dans the property name. If is false, only property name.
     *
     * If $isRecursive, the conversion also applies to objects in the properties and to values of arrays.
     *
     * @param object $object
     * @param int $filter
     * @param bool $isFullName
     * @param bool $isRecursive
     * @return array
     */
    public function castToArray(
        $object,
        $filter = AttributeReflection::NO_FILTER,
        $isFullName = false,
        $isRecursive = true
    ) {
        return $this->extractValues(
            new ObjectReflection($object),
            new ObjectToArrayCasterParam($filter, $isFullName, $isRecursive)
        );
    }

    /**
     * Extract properties from a reflector.
     *
     * @param ObjectReflection $reflectedObject
     * @param ObjectToArrayCasterParam $param
     * @return array
     */
    private function extractValues(ObjectReflection $reflectedObject, ObjectToArrayCasterParam $param)
    {
        $result = array();
        $this->addCastedObject($reflectedObject->getObject());

        foreach ($reflectedObject->getReflectionProperties($param->getFilter()) as $reflectedProperty) {
            if (
                !isset($result[$reflectedProperty->getName($param->isFullName())])
                && !$reflectedProperty->isTransient()
                && !$reflectedProperty->getClassReflection()->isTransient()
            ) {
                $result = $this->addValue(
                    $result,
                    $reflectedProperty->getName($param->isFullName()),
                    $reflectedProperty->getValue($reflectedObject->getObject()),
                    $param
                );
            }
        }
        return $result;
    }

    /**
     * Filter value to added to $values according to the recursion.
     *
     * @param array $values
     * @param mixed $key
     * @param mixed $value
     * @param ObjectToArrayCasterParam $param
     * @return array
     */
    private function addValue(array $values, $key, $value, ObjectToArrayCasterParam $param)
    {
        $value = $this->handleValue($value, $param);
        if ($this->isExplicitRecursion() || $value !== self::RECURSION_VALUE) {
            $values[$key] = $value;
        }
        return $values;
    }

    /**
     * Apply recursion of the conversion.
     *
     * @param mixed $value
     * @param ObjectToArrayCasterParam $param
     * @return array
     */
    private function handleValue($value, ObjectToArrayCasterParam $param)
    {
        if ($param->isRecursive()) {
            if (is_object($value)) {
                return $this->handleObjectValue($value, $param);
            } elseif (is_array($value)) {
                return $this->handleArrayValue($value, $param);
            }
        }
        return $value;
    }

    /**
     * Convert recursively an object.
     *
     * @param $object
     * @param ObjectToArrayCasterParam $param
     * @return array|string
     */
    private function handleObjectValue($object, ObjectToArrayCasterParam $param)
    {
        if ($this->isAlreadyCasted($object)) {
            return self::RECURSION_VALUE;
        }
        return $this->extractValues(new ObjectReflection($object), $param);
    }

    /**
     * Convert recursively an array.
     *
     * @param array $data
     * @param ObjectToArrayCasterParam $param
     * @return array
     */
    private function handleArrayValue(array $data, ObjectToArrayCasterParam $param)
    {
        $result = array();
        foreach ($data as $key => $value) {
            $result = $this->addValue($result, $key, $value, $param);
        }
        return $result;
    }

    /**
     * Getter of $castedObjects.
     *
     * @return array
     */
    private function getCastedObjects()
    {
        return $this->castedObjects;
    }

    /**
     * Indicate if a object has been already casted.
     *
     * @param $object
     * @return bool
     */
    private function isAlreadyCasted($object)
    {
        return in_array($object, $this->getCastedObjects());
    }

    /**
     * Setter of $castedObjects.
     *
     * @param array $castedObjects
     */
    private function setCastedObjects($castedObjects)
    {
        $this->castedObjects = $castedObjects;
    }

    /**
     * Adder of $castedObjects.
     *
     * @param $object
     */
    private function addCastedObject($object)
    {
        $this->castedObjects[] = $object;
    }
}
