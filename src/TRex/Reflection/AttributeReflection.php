<?php
namespace TRex\Reflection;

use TRex\Annotation\AnnotationParser;
use TRex\Annotation\Annotations;

/**
 * Class AttributeReflection
 * Main class for reflection of the different parts of a class.
 *
 * @package TRex\Reflection
 * @transient
 */
abstract class AttributeReflection extends Reflection
{

    /**
     * No filter.
     * Filter constant.
     */
    const NO_FILTER = 65535;

    /**
     * Filter only public visibility.
     * Filter constant.
     */
    const PUBLIC_FILTER = \ReflectionProperty::IS_PUBLIC;

    /**
     * Filter only protected visibility.
     * Filter constant.
     */
    const PROTECTED_FILTER = \ReflectionProperty::IS_PROTECTED;

    /**
     * Filter only private visibility.
     * Filter constant.
     */
    const PRIVATE_FILTER = \ReflectionProperty::IS_PRIVATE;

    /**
     * Filter only static.
     * Filter constant.
     */
    const STATIC_FILTER = \ReflectionProperty::IS_STATIC;

    /**
     * @var Annotations
     */
    private $annotations;

    /**
     * Returns the name of the PHP reflector to associate with this class.
     *
     * @return string
     */
    abstract protected function getReflectorClassName();

    /**
     * Details of the name of the reflected attribute
     * and the name of the class from which we can access to the attribute declaration.
     * You can recover the class reflector with self::getClassReflection().
     *
     * @param string $className
     * @param string $propertyName
     */
    public function __construct($className, $propertyName)
    {
        $reflectorClassName = $this->getReflectorClassName();
        parent::__construct(new $reflectorClassName($className, $propertyName));
    }

    /**
     * {@inheritDoc}
     *
     * If $isFullName uis true, the name will consist of the name of the class and the name of the attribute.
     * ex: Class::attribute
     *
     * @param bool $isFullName
     * @return string
     */
    public function getName($isFullName = false)
    {
        if ($isFullName) {
            return $this->getClassReflection()->getName() . '::' . parent::getName();
        }
        return parent::getName();
    }

    /**
     * {@inheritDoc}
     *
     * An attribute is also transient if its class is.
     *
     * @return bool
     */
    public function isTransient()
    {
        return parent::isTransient() || $this->getClassReflection()->isTransient();
    }

    /**
     * Return the reflector of the class where the attribute is declaring.
     *
     * @warning
     * The classReflection is not necessarily the reflector of a class from which current attribute has been extracted.
     * For example, if you get this class form an object and if this class reflect an attribute declared in the parent,
     * self::getClassReflection() will return the reflector for the parent.
     *
     * @return ClassReflection
     */
    public function getClassReflection()
    {
        return new ClassReflection($this->getReflector()->getDeclaringClass()->getName());
    }

    /**
     * @return Annotations
     */
    public function getAnnotations()
    {
        if (null == $this->annotations) {
            $this->setAnnotations($this->buildAnnotations());
        }
        return $this->annotations;
    }

    /**
     * @return Annotations
     */
    private function buildAnnotations()
    {
        $annotationParser = new AnnotationParser();
        return $annotationParser->getAnnotations($this->getReflector()->getDocComment());
    }

    /**
     * Setter of $annotations
     *
     * @param Annotations $annotations
     */
    private function setAnnotations(Annotations $annotations)
    {
        $this->annotations = $annotations;
    }
}
