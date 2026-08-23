<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:suppliers,phone'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', 'unique:suppliers,email'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المورد مطلوب',
            'name.min' => 'اسم المورد يجب أن يكون حرفين على الأقل',
            'name.max' => 'اسم المورد لا يتجاوز 255 حرف',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف موجود بالفعل',
            'phone.max' => 'رقم الهاتف لا يتجاوز 20 رقم',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني موجود بالفعل',
        ];
    }
}
