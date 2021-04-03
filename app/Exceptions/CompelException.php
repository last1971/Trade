<?php

namespace App\Exceptions;

use Exception;
use Log;
use Throwable;

class CompelException extends Exception
{
    /**
     * @var mixed $request
     */
    private $request;
    /**
     * @var mixed $response
     */
    private $response;

    /**
     * CompelExeption constructor.
     * @param mixed $request
     * @param mixed $response
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($request, $response, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->request = $request;
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        //
        Log::alert('CompelApiExeption');
        Log::alert($this->request);
        Log::alert($this->response);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render()
    {
        return response()->json([
            'message' => $this->getMessage(),
            'exception' => 'CompelApiException',
            'errors' => [],
        ], 500);
    }
}
