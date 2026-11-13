<?php

namespace App\Exceptions;

use CodeIgniter\HTTP\ResponsableInterface;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Ends the request with a ready response (CodeIgniter 3 show_error(): the
 * code after the call never runs). CodeIgniter 4 sends the response of any
 * ResponsableInterface it catches while handling the request.
 */
class HaltException extends RuntimeException implements ResponsableInterface
{
    public function __construct(private readonly ResponseInterface $response)
    {
        parent::__construct('Request halted with HTTP ' . $response->getStatusCode());
    }

    /**
     * Error page in the CodeIgniter 3 error_general layout.
     */
    public static function error(string $message, int $status_code = 500, string $heading = 'An Error Was Encountered'): self
    {
        $response = service('response')
            ->setStatusCode($status_code)
            ->setBody(view('errors/html/error_general', [
                'heading' => $heading,
                'message' => '<p>' . $message . '</p>',
            ], ['saveData' => false]));

        return new self($response);
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
