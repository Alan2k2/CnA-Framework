<?php

declare(strict_types=1);

namespace Tests\Unit;

use CnA\Contracts\Exceptions\ValidationException;
use CnA\Testing\TestCase;
use CnA\Validation\Validator;

class ValidatorTest extends TestCase
{
    public function testValidatorPassesWithValidData(): void
    {
        $data = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'age' => 28,
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|min:2',
            'email' => 'required|email',
            'age' => 'required|integer|min:18',
        ]);

        $this->assertTrue($validator->passes());
        $this->assertEquals($data, $validator->validated());
    }

    public function testValidatorFailsWithInvalidData(): void
    {
        $data = [
            'email' => 'not-an-email',
            'age' => 15,
        ];

        $validator = Validator::make($data, [
            'name' => 'required',
            'email' => 'required|email',
            'age' => 'required|integer|min:18',
        ]);

        $this->assertTrue($validator->fails());
        $errors = $validator->errors();

        $this->assertTrue(isset($errors['name']));
        $this->assertTrue(isset($errors['email']));
        $this->assertTrue(isset($errors['age']));
    }

    public function testValidateThrowsValidationException(): void
    {
        $validator = Validator::make([], ['name' => 'required']);

        $threw = false;
        try {
            $validator->validate();
        } catch (ValidationException $e) {
            $threw = true;
            $this->assertTrue(isset($e->errors()['name']));
        }

        $this->assertTrue($threw);
    }
}

