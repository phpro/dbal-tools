<?php

declare(strict_types=1);

namespace Phpro\DbalTools\Pagerfanta\Adapter;

use Pagerfanta\Adapter\AdapterInterface;

use function Psl\Type\positive_int;

/**
 * @template T
 *
 * @template-implements AdapterInterface<T>
 */
final class MappingAdapter implements AdapterInterface
{
    /**
     * @var callable(array): T
     */
    private mixed $mapper;

    /**
     * @param callable(array): T $mapper
     */
    public function __construct(private AdapterInterface $adapter, callable $mapper)
    {
        $this->mapper = $mapper;
    }

    public function getNbResults(): int
    {
        return $this->adapter->getNbResults();
    }

    /**
     * @param int<0, max> $offset
     *
     * @return iterable<array-key, T>
     */
    public function getSlice(int $offset, int $length): iterable
    {
        positive_int()->assert($length);

        /** @var array $item */
        foreach ($this->adapter->getSlice($offset, $length) as $item) {
            yield ($this->mapper)($item);
        }
    }
}
