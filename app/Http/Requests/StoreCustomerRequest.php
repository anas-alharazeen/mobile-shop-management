<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:customers,phone'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:customers,email'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم العميل مطلوب',
            'name.min' => 'اسم العميل يجب أن يكون حرفين على الأقل',
            'name.max' => 'اسم العميل لا يتجاوز 255 حرف',
            'phone.unique' => 'رقم الهاتف موجود بالفعل',
            'phone.max' => 'رقم الهاتف لا يتجاوز 20 رقم',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني موجود بالفعل',
        ];
    }
}
