<?php

declare(strict_types=1);

namespace App\Environment;

use App\Exception\InvalidEnvironmentException;

/**
 * @codeCoverageIgnore
 */
class EnvironmentCollection implements \Countable, \Iterator, \ArrayAccess
{
    /** @var int */
    private $position = 0;

    /** @var EnvironmentEntity[] */
    private $values = [];

    /**
     * @throws InvalidEnvironmentException
     */
    public function __construct(array $values = [])
    {
        foreach ($values as $value) {
            $this->offsetSet('', $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function current(): EnvironmentEntity
    {
        return $this->values[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return isset($this->values[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return isset($this->values[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset): EnvironmentEntity
    {
        return $this->values[$offset];
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidEnvironmentException
     */
    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof EnvironmentEntity) {
            throw new InvalidEnvironmentException('Must be an instance of \App\Environment\EnvironmentEntity.');
        }

        foreach ($this->values as $entity) {
            if ($entity->getName() === $value->getName()) {
                throw new InvalidEnvironmentException('An environment with the same name already exists.');
            }

            if ($entity->getLocation() === $value->getLocation()) {
                throw new InvalidEnvironmentException('An environment at the same location already exists.');
            }
        }

        if (empty($offset)) {
            $this->values[] = $value;
        } else {
            $this->values[$offset] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->values[$offset]);
        sort($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->values);
    }
}
