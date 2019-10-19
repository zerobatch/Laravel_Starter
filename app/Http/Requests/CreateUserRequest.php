<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        if (request()->isMethod('POST')) {
            return [
                'email' => 'required|email',
                'name' => 'required|string|regex: /^[A-Za-z\s_.-]+$/',
                'password' => 'required|string|min:6|confirmed',
                'roles' => 'required|array'
            ];
        }
    }
}
