<?php

namespace Tests\Unit;

use App\Models\CastMember;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\Uuid;

class CastMemberUnitTest extends TestCase
{
    public function testIfUseTraits()
    {
        $traits = [SoftDeletes::class, Uuid::class];
        $castMemberTraits = array_keys(class_uses(CastMember::class));
        $this->assertEquals($traits, $castMemberTraits);
    }

    public function testIncrementing()
    {
        $castMember = new CastMember();
        $this->assertFalse($castMember->getIncrementing());
    }

    public function testKeyType()
    {
        $castMember = new CastMember();
        $this->assertEquals('string', $castMember->getKeyType());
    }

    public function testDates()
    {
        $castMember = new CastMember();
        $dates = ['deleted_at', 'created_at', 'updated_at'];
        $this->assertEqualsCanonicalizing($dates, $castMember->getDates());
        $this->assertCount(count($dates), $castMember->getDates());
    }

    public function testFillable()
    {
        $castMember = new CastMember();
        $this->assertEquals(['name', 'type'], $castMember->getFillable());
    }

    public function testCastType()
    {
        $castMember = new CastMember();
        $this->assertEquals(['type' => 'integer'], $castMember->getCasts());
    }
}
