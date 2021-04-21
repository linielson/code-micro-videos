<?php

namespace Tests\Feature\Models;

use App\Models\CastMember;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Ramsey\Uuid\Uuid as UuidValidator;

class CastMemberTest extends TestCase
{
    use DatabaseMigrations;

    public function testList()
    {
        factory(CastMember::class)->create();
        $castMembers = CastMember::all();
        $this->assertCount(1, $castMembers);
        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'type', 'created_at', 'updated_at', 'deleted_at'],
            array_keys($castMembers->first()->getAttributes())
        );
    }

    public function testCreate()
    {
        $castMember = CastMember::create(['name' => 'John Doe', 'type' => CastMember::TYPE_ACTOR]);
        $castMember->refresh();

        $this->assertTrue(UuidValidator::isValid($castMember->id));
        $this->assertEquals('John Doe', $castMember->name);
        $this->assertEquals(CastMember::TYPE_ACTOR, $castMember->type);
    }

    public function testUpdate()
    {
        $castMember = factory(CastMember::class)->create(['name' => 'John Doe', 'type' => CastMember::TYPE_ACTOR]);

        $data = [
            'name' => 'New John Doe',
            'type' => CastMember::TYPE_DIRECTOR
        ];
        $castMember->update($data);

        foreach($data as $key => $value) {
            $this->assertEquals($value, $castMember->{$key});
        }
    }

    public function testDelete()
    {
        $castMember = factory(CastMember::class)->create();
        $castMember->delete();
        $this->assertNull(CastMember::find($castMember->id));

        $castMember->restore();
        $this->assertNotNull(CastMember::find($castMember->id));
    }
}
