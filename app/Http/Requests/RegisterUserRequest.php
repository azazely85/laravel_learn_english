<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
        return [
            'firstname' => 'required|max:80',
            'lastname' => 'required|max:80',
            'phone' => 'required|max:20',
            'age' => 'required|max:2',
            'email' => 'required|unique:users|max:255',
            'password' => 'required|confirmed|min:6',
        ];
    }


    public function messages(): array
    {
        return [
            'firstname.required' => 'Firstname field is required',
            'lastname.required' => 'Lastname field is required',
            'email.required' => 'Email field is required',
            'password.required' => 'Password field is required',
            'firstname.max' => "Firstname field can't be more than 80 chars",
            'lastname.max' => "Lastname field can't be more than 80 chars",
            'email.max' => "Email field can't be more than 255 chars",
            'email.unique' => "Email field must be unique",
            'password.min' => "Password field can't be less than 6 chars",
            'password.confirmed' => "Password fields must match",
        ];
    }
}
