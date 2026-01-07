<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ResponseHelperTest extends TestCase
{
    public function test_json_response_returns_json_response_instance(): void
    {
        $response = ResponseHelper::jsonResponse(true, 'Test message', ['key' => 'value'], 200);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function test_json_response_with_success_true(): void
    {
        $response = ResponseHelper::jsonResponse(true, 'Success message', ['data' => 'test'], 200);

        $content = json_decode($response->getContent(), true);

        $this->assertTrue($content['success']);
        $this->assertEquals('Success message', $content['message']);
        $this->assertEquals(['data' => 'test'], $content['data']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_json_response_with_success_false(): void
    {
        $response = ResponseHelper::jsonResponse(false, 'Error message', null, 422);

        $content = json_decode($response->getContent(), true);

        $this->assertFalse($content['success']);
        $this->assertEquals('Error message', $content['message']);
        $this->assertNull($content['data']);
        $this->assertEquals(422, $response->getStatusCode());
    }

    public function test_json_response_with_different_status_codes(): void
    {
        $testCases = [
            ['status' => 200, 'success' => true],
            ['status' => 201, 'success' => true],
            ['status' => 400, 'success' => false],
            ['status' => 404, 'success' => false],
            ['status' => 500, 'success' => false],
        ];

        foreach ($testCases as $case) {
            $response = ResponseHelper::jsonResponse(
                $case['success'],
                'Test',
                null,
                $case['status']
            );

            $this->assertEquals($case['status'], $response->getStatusCode());
        }
    }

    public function test_json_response_with_array_data(): void
    {
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'John'],
                ['id' => 2, 'name' => 'Jane'],
            ],
            'total' => 2,
        ];

        $response = ResponseHelper::jsonResponse(true, 'Users retrieved', $data, 200);

        $content = json_decode($response->getContent(), true);

        $this->assertEquals($data, $content['data']);
    }

    public function test_json_response_with_empty_data(): void
    {
        $response = ResponseHelper::jsonResponse(true, 'No data', [], 200);

        $content = json_decode($response->getContent(), true);

        $this->assertEquals([], $content['data']);
    }

    public function test_json_response_with_null_data(): void
    {
        $response = ResponseHelper::jsonResponse(true, 'Null data', null, 200);

        $content = json_decode($response->getContent(), true);

        $this->assertNull($content['data']);
    }

    public function test_json_response_structure(): void
    {
        $response = ResponseHelper::jsonResponse(true, 'Test', ['key' => 'value'], 200);

        $content = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('success', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('data', $content);
    }
}
