<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;

    const VALIDATE_ONLY_INPUT_KEY = 'validate_only';

    /**
     * @param \Illuminate\Http\Request $request
     */
    protected function validateOnlyResponse(Request $request): void
    {
        abort_if(
            $request->input(static::VALIDATE_ONLY_INPUT_KEY) === true,
            Response::HTTP_OK,
            'The given data was valid.'
        );
    }
}
