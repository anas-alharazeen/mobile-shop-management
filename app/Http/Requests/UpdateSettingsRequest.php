<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // بيانات المعرض
            'store_name' => ['nullable', 'string', 'max:255'],
            'store_phone' => ['nullable', 'string', 'max:20'],
            'store_whatsapp' => ['nullable', 'string', 'max:20'],
            'store_email' => ['nullable', 'email', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:500'],
            'store_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_store_logo' => ['nullable', 'boolean'],
            'footer_text' => ['nullable', 'string', 'max:500'],

            // الفواتير
            'invoice_footer' => ['nullable', 'string', 'max:500'],
            'return_policy' => ['nullable', 'string', 'max:1000'],
            'sales_notes' => ['nullable', 'string', 'max:500'],
            'repair_notes' => ['nullable', 'string', 'max:500'],
            'default_low_stock' => ['nullable', 'integer', 'min:0'],

            // الدفع
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'bank_iban' => ['nullable', 'string', 'max:255'],
            'app_name' => ['nullable', 'string', 'max:255'],
            'app_account' => ['nullable', 'string', 'max:255'],
            'default_cash_account' => ['nullable', 'integer', 'exists:financial_accounts,id'],
            'payment_methods' => ['nullable', 'array'],
            'payment_methods.*' => ['string', 'in:cash,bank_transfer,banking_app'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_logo.image' => 'يجب أن يكون الملف صورة',
            'store_logo.mimes' => 'الصورة يجب أن تكون بصيغة JPG, PNG, أو WebP',
            'store_logo.max' => 'حجم الصورة لا يتجاوز 2MB',
            'store_email.email' => 'البريد الإلكتروني غير صحيح',
            'default_low_stock.integer' => 'الحد الأدنى يجب أن يكون رقماً صحيحاً',
            'default_low_stock.min' => 'الحد الأدنى يجب أن يكون أكبر من أو يساوي صفر',
        ];
    }
}
