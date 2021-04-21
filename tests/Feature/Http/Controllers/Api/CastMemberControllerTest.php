<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\CastMember;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Tests\Traits\TestValidations;

class CastMemberControllerTest extends TestCase
{
    use DatabaseMigrations, TestValidations;

    public function testIndex()
    {
        $castMember = factory(CastMember::class)->create();
        $response = $this->get(route('cast_members.index'));
        $response->assertStatus(200);
    }

    public function testShow()
    {
        $castMember = factory(CastMember::class)->create();
        $response = $this->get(route('cast_members.show', ['cast_member' => $castMember->id]));
        $response
            ->assertStatus(200)
            ->assertJson($castMember->toArray());
    }

    public function testStore()
    {
        $response = $this->json('POST', route('cast_members.store'), ['name' => 'John Doe', 'type' => CastMember::TYPE_DIRECTOR]);

        $castMember = CastMember::find($response->json('id'));
        $response
            ->assertStatus(201)
            ->assertJson($castMember->toArray());

        $this->assertEquals(CastMember::TYPE_DIRECTOR, $response->json('type'));

        $response = $this->json('POST', route('cast_members.store'), [
            'name' => 'John Doe',
            'type' => CastMember::TYPE_ACTOR
        ]);
        $this->assertEquals(CastMember::TYPE_ACTOR, $response->json('type'));
    }

    public function testUpdate()
    {
        $castMember = factory(CastMember::class)->create(['type' => CastMember::TYPE_ACTOR]);
        $response = $this->json(
            'PUT',
            route('cast_members.update', ['cast_member' => $castMember->id]),
            ['name' => 'Changed name', 'type' => CastMember::TYPE_DIRECTOR]
        );

        $castMember = CastMember::find($response->json('id'));
        $response
            ->assertStatus(200)
            ->assertJson($castMember->toArray())
            ->assertJsonFragment([
                'name' => 'Changed name',
                'type' => CastMember::TYPE_DIRECTOR
            ]);
    }

    public function testDestroy()
    {
        $castMember = factory(CastMember::class)->create();
        $response = $this->json('DELETE', route('cast_members.destroy', ['cast_member' => $castMember->id]));

        $response->assertStatus(204);
        $this->assertNull(CastMember::find($castMember->id));
        $this->assertNotNull(CastMember::withTrashed()->find($castMember->id));
    }

    public function testInvalidationDataPOST()
    {
        $response = $this->json('POST', route('cast_members.store'), []);
        $this->assertInvalidationFields($response, ['name', 'type'], 'required');

        $response = $this->json('POST', route('cast_members.store'), [
            'name' => str_repeat('x', 256),
            'type' => 999
        ]);
        $this->assertInvalidationFields($response, ['name'], 'max.string', ['max' => 255]);
        $this->assertInvalidationFields($response, ['type'], 'in');
    }

    public function testInvalidationDataPUT()
    {
        $castMember = factory(CastMember::class)->create();
        $response = $this->json('PUT', route('cast_members.update', ['cast_member' => $castMember->id]), []);
        $this->assertInvalidationFields($response, ['name', 'type'], 'required');

        $response = $this->json('PUT', route('cast_members.update', ['cast_member' => $castMember->id]), [
            'name' => str_repeat('x', 256),
            'type' => 999
        ]);
        $this->assertInvalidationFields($response, ['name'], 'max.string', ['max' => 255]);
        $this->assertInvalidationFields($response, ['type'], 'in');
    }
}
