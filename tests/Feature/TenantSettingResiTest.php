<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TenantSettingResiTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    public function test_access_and_crud()
    {
        $dataset = [
            [
                User::factory()->tenant()->create(),
                200,
                [
                    'additional_information' => $this->faker()->name,
                    'instagram' => $this->faker()->name,
                    'facebook' => $this->faker()->name,
                    'website' => $this->faker()->name,
                    'note' => $this->faker()->name,
                    'logo' => UploadedFile::fake()->image('logo.png')->size(5000),
                ]
            ],
            [
                User::factory()->tenant()->create(),
                200,
                [
                    'additional_information' => $this->faker()->name,
                    'instagram' => $this->faker()->name,
                    'facebook' => $this->faker()->name,
                    'website' => $this->faker()->name,
                    'note' => $this->faker()->name,
                    'logo' => UploadedFile::fake()->image('logo.png')->size(5000),
                    'is_delete_logo' => true,
                ]
            ],
            [
                User::factory()->tenant()->create(),
                200,
                [
                    'additional_information' => null,
                    'instagram' => null,
                    'facebook' => null,
                    'website' => null,
                    'note' => null,
                    'logo' => null,
                ]
            ],
            [
                User::factory()->tenant()->create(),
                422,
                [
                    'additional_information' => $this->faker()->regexify('[A-Za-z0-9]{51}'),
                    'instagram' => $this->faker()->regexify('[A-Za-z0-9]{51}'),
                    'facebook' => $this->faker()->regexify('[A-Za-z0-9]{51}'),
                    'website' => $this->faker()->regexify('[A-Za-z0-9]{51}'),
                    'note' => $this->faker()->regexify('[A-Za-z0-9]{121}'),
                    'logo' => UploadedFile::fake()->image('logo.xlxs')->size(5001),
                ]
            ],
            [
                User::factory()->admin()->create(),
                403,
                []
            ]
        ];
        foreach ($dataset as [$user, $code, $payload]) {
            $response = $this->actingAs($user,'api')->postJson('/api/tenant/setting-resi',$payload);
            $response->assertStatus($code);
            if($code == 200){
                if(isset($payload['is_delete_logo'])){
                    if($payload['is_delete_logo'] == true){
                        $this->assertNull($user->tenant->logo);
                    }
                    unset($payload['is_delete_logo']);
                }
                unset($payload['logo']);
                $this->assertEquals($user->tenant->only(array_keys($payload)), $payload);
            }
            if($code == 422){
                $response->assertJsonValidationErrors(array_keys($payload));
            }
        }
    }
}
