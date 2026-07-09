<?php

namespace App\Http\Requests;

use App\Rules\ValidTimezone;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    protected function dateFields(): array
    {
        return [];
    }

    protected function timezoneRules(): array
    {
        return ['timezone' => ['required', 'string', new ValidTimezone]];
    }
}
