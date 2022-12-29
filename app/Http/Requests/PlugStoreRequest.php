<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlugStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "serial_number" => "required|max:80|unique:plugs,serial_number",
            "pin" => "required|max:6",
        ];
    }
}
