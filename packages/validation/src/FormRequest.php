<?php

declare(strict_types=1);

namespace CnA\Validation;

use CnA\Contracts\Exceptions\AuthorizationException;
use CnA\Contracts\Http\RequestInterface;

abstract class FormRequest
{
    public function __construct(protected RequestInterface $request)
    {
    }

    public function authorize(): bool
    {
        return true;
    }

    abstract public function rules(): array;

    public function messages(): array
    {
        return [];
    }

    public function validate(): array
    {
        if (!$this->authorize()) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $validator = Validator::make(
            $this->request->all(),
            $this->rules(),
            $this->messages()
        );

        return $validator->validate();
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}

