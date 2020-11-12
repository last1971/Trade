<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception
{
    //
    protected $errors;

    public function __construct(
        string $message = null,
        int $code = null,
        $errors = null,
        \Throwable $previous = null
    )
    {
        $this->errors = is_array($errors) ? $errors : $this->getDefaultErrors($errors);

        parent::__construct(
            $message ?? $this->getDefaultMessage(),
            $code ?? $this->getDefaultCode(),
            $previous
        );
    }

    public function render()
    {
        return response([
            'message' => $this->getMessage(),
            'errors' => $this->errors,
        ], $this->getCode());
    }

    protected function getDefaultErrors($error)
    {
        return ['_other' => $error ?? $this->getDefaultMessage()];
    }

    protected function getDefaultCode()
    {
        return 400;
    }

    protected function getDefaultMessage()
    {
        return 'Invalid request.';
    }
}
