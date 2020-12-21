<?php

namespace Evirma\Bundle\CoreBundle\Service;

use DateTime;
use Doctrine\Common\Annotations\AnnotationReader;
use Evirma\Bundle\CoreBundle\Form\Transformer\Mapping\MappingInterface;
use LogicException;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ObjectTransformer
{
    /**
     * @var array
     */
    private array $transformers = [];
    /**
     * @var array
     */
    private array $metadata = [];
    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container, CacheInterface $systemCache)
    {
        $this->cache = $systemCache;
        $this->container = $container;
    }

    /**
     * @param string $className
     * @return mixed|null|DataTransformerInterface
     */
    public function getTransformer(string $className)
    {
        if (!isset($this->transformers[$className])) {
            if (class_exists($className)) {
                $this->transformers[$className] = $this->container->get($className);
            } else {
                return null;
            }
        }

        return $this->transformers[$className];
    }

    /**
     * @param object      $class
     * @param string|null $field
     * @param array       $groups
     * @return object
     */
    public function transform(object $class, string $field = null, array $groups = [])
    {
        if (!$className = get_class($class)) {
            return $class;
        }
        $this->loadClassFetchers($className);

        if (empty($groups)) {
            $groups = ['Default'];
        }

        if ($field) {
            $field = Str::asCamelCase($field);
        }

        if (isset($this->metadata[$className])) {
            foreach ($this->metadata[$className] as $metaField => $meta) {
                if ($field && ($field != $metaField)) {
                    continue;
                }

                if (!array_intersect($groups, $meta['groups'])) {
                    continue;
                }

                if (isset($meta['getter'])) {
                    $oldData = $data = $class->{$meta['getter']}();
                } elseif (isset($meta['property'])) {
                    $oldData = $data = $class->{$meta['property']};
                } else {
                    throw new LogicException("Transform Meta data in class {$className} is corrupted");
                }

                if (isset($meta['transformers'])) {
                    foreach ($meta['transformers'] as $transformerClass) {
                        $transformer = $this->getTransformer($transformerClass);
                        $data = $transformer->transform($data);
                    }
                }

                if ($oldData != $data) {
                    if (isset($meta['setter'])) {
                        $class->{$meta['setter']}($data);
                    } else {
                        $class->{$meta['property']} = $data;
                    }
                }
            }
        }

        return $class;
    }

    private function loadClassFetchers($class)
    {
        try {
            $data = $this->cache->get(
                'loadClassTransformers__'.md5($class),
                function (ItemInterface $item) use ($class) {
                    if (!$data = $item->get()) {
                        $classRef = new ReflectionClass($class);
                        $props = $classRef->getProperties();
                        $methods = $classRef->getMethods(ReflectionMethod::IS_PUBLIC);
                        $setters = [];
                        $getters = [];
                        foreach ($methods as $method) {
                            if (substr($method->getName(), 0, 3) == 'set') {
                                $setters[substr($method->getName(), 3)] = $method->getName();
                            } elseif (substr($method->getName(), 0, 3) == 'get') {
                                $getters[substr($method->getName(), 3)] = $method->getName();
                            } elseif (substr($method->getName(), 0, 2) == 'is') {
                                $getters[substr($method->getName(), 2)] = $method->getName();
                            } elseif (substr($method->getName(), 0, 3) == 'has') {
                                $getters[substr($method->getName(), 3)] = $method->getName();
                            }
                        }

                        $data = [];
                        foreach ($props as $prop) {
                            $propAnnotations = (new AnnotationReader())->getPropertyAnnotations($prop);

                            foreach ($propAnnotations as $ann) {
                                if (!$ann instanceof MappingInterface) {
                                    continue;
                                }
                                $ucfirsted = Str::asCamelCase($prop->getName());

                                $setter = isset($setters[$ucfirsted]) ? $setters[$ucfirsted] : null;
                                $getter = isset($getters[$ucfirsted]) ? $getters[$ucfirsted] : null;

                                if (!isset($data[$prop->getName()])) {
                                    $groups = $ann->getGroups();
                                    if (empty($groups)) {
                                        $groups = ['Default'];
                                    }

                                    $data[$ucfirsted] = [
                                        'transformers' => [],
                                        'setter' => $setter,
                                        'getter' => $getter,
                                        'property' => $prop->isPublic() ? $prop->getName() : null,
                                        'groups' => $groups,
                                    ];
                                }

                                $transformerClass = get_class($ann);
                                if ($this->getTransformer($transformerClass)) {
                                    $data[$ucfirsted]['transformers'][] = $transformerClass;
                                }
                            }
                        }
                    }

                    $item->expiresAt(new DateTime("+1 hour"))
                        ->set($data);

                    return $data;
                }
            );
        } catch (InvalidArgumentException $e) {
            $data = [];
        }

        $this->metadata[$class] = $data;
    }
}
