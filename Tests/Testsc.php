<?php

use NCache\Core\Files\CacheDirectory;


require __DIR__."/../vendor/autoload.php";

$f = new CacheDirectory([__DIR__."/../cache"]);

foreach($f->iterate() as $file){
    if($file->isFile())
    var_dump($file->getRealPath());
}


/**
 * @template T of object
 */
abstract class AbstractRepository
{
    /**
     * @var array<int, T>
     */
    protected array $entities = [];

    /**
     * @param T $entity
     */
    public function save(object $entity): void
    {
        $this->entities[$this->getId($entity)] = $entity;
    }

    /**
     * @return T|null
     */
    public function find(int $id): ?object
    {
        return $this->entities[$id] ?? null;
    }

    /**
     * @param T $entity
     */
    abstract protected function getId(object $entity): int;
}

/**
 * @extends AbstractRepository<User>
 */
final class UserRepository extends AbstractRepository
{
    protected function getId(object $entity): int
    {
        return $entity->id;
    }
}