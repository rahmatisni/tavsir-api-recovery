<?php

namespace Tests\Feature;

use App\Models\RestArea;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class RestAreaTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    /** @dataProvider dataRestAreaProvider */
    public function testValidasi(array $invalidData,string $filedError)
    {
        $response = $this->postJson('/api/rest-area',$invalidData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([$filedError]);
    }

    public function dataRestAreaProvider()
    {
        return [
            [['name' => null], 'name'],
            [['name' => ''], 'name'],
            [['name' => []], 'name'],
            [['name' => Str::random(21)], 'name'],
            [['address' => null], 'address'],
            [['address' => ''], 'address'],
            [['time_start' => null], 'time_start'],
            [['time_start' => ''], 'time_start'],
            [['time_start' => []], 'time_start'],
            [['time_start' => '24:00'], 'time_start'],
            [['time_end' => null], 'time_end'],
            [['time_end' => ''], 'time_end'],
            [['time_end' => []], 'time_end'],
            [['time_end' => '24:00'], 'time_end'],
            [['is_open' => 'true'], 'is_open'],
        ];
    }

    public function testCreate()
    {
        $data = RestArea::factory()->make()->toArray();
        $response = $this->postJson('/api/rest-area',$data);

        $response->assertStatus(200);
        $response->assertJson($data);
    }

    public function testUpdate()
    {
        $dataCreate = RestArea::factory()->create();
        $dataUpdate = RestArea::factory()->make()->toArray();

        $responseUpdate = $this->putJson('/api/rest-area/'.$dataCreate->id,$dataUpdate);

        $responseUpdate->assertStatus(200);
        $responseUpdate->assertJson($dataUpdate);
    }

    public function testUniqueName()
    {
        $dataCreate = RestArea::factory()->create()->toArray();

        $responseCreate2 = $this->postJson('/api/rest-area',$dataCreate);

        $responseCreate2->assertStatus(422);
    }

    public function testDelete()
    {
        $dataCreate = RestArea::factory()->create();

        $responseDelete = $this->deleteJson('/api/rest-area/'.$dataCreate->id);

        $responseDelete->assertStatus(204);
    }
}
