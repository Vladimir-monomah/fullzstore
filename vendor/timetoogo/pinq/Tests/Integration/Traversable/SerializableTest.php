<?php

namespace Pinq\Tests\Integration\Traversable;

use Pinq\Traversable;

class SerializableTest extends TraversableTest
{
    public function testThatTraversableIsSerializable()
    {
        $traversable = new Traversable();
        $unserializedTraversable = unserialize(serialize($traversable));

        $this->assertEquals(
                $traversable->asArray(),
                $unserializedTraversable->asArray());
    }

    public static function whereLessThanSix($value)
    {
        return $value < 6;
    }

    public function testThatCollectionIsSerializableAfterQueries()
    {
        $traversable = new Traversable(range(1, 10));
        $traversable = $traversable
                ->where([__CLASS__, 'whereLessThanSix']);

        $serializedTraversable = serialize($traversable);
        $unserializedTraversable = unserialize($serializedTraversable);

        $this->assertEquals(
                $traversable->asArray(),
                $unserializedTraversable->asArray());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Serialization of 'Closure' is not allowed
     */
    public function testThatTraversableIsNotSerializableAfterQueriesWithClosures()
    {
        $traversable = new Traversable(range(1, 10));
        $traversable = $traversable
                ->where(function ($i) { return $i !== false; });

        serialize($traversable);
    }
}
