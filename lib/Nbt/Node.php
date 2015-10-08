<?php

namespace Nbt;

/**
 * We're going to rejig the node to accept key->value pairs instead of just values;
 * this is much more suited to our needs, where we're storing more than one bit
 * of information per node.
 */
class Node implements \Tree\Node\NodeInterface
{
    use \Tree\Node\NodeTrait {
        __construct as private __traitConstruct;
    }

    /**
     * Create a node, optionally passing an array of children.
     *
     * @param array $children
     */
    public function __construct($children = [])
    {
        $this->__traitConstruct([], $children);
    }

    /**
     * Override the setValue method to do nothing.
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        ;
    }

    /**
     * Set a key for the current node.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setKey($key, $value)
    {
        $this->value[$key] = $value;
    }

    /**
     * Get a key for the current node.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getKey($key)
    {
        return $this->value[$key];
    }
}
