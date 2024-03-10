<?php

namespace SlashId\Php\Exception;

use GuzzleHttp\Exception\BadResponseException;

/**
 * Base class for exceptions thrown based on the API response.
 */
class ApiExceptionBase extends BadResponseException {
    public function __construct(
        ?string $message,
        BadResponseException $previous,
    ) {
        parent::__construct(
            $message,
            $previous->getRequest(),
            $previous->getResponse(),
            $previous,
            $previous->getHandlerContext(),
        );
    }
}
