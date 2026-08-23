<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_id' =>
                $this->filled(
                    'product_id'
                )
                    ? (int) $this->input(
                        'product_id'
                    )
                    : null,

            'warehouse_id' =>
                $this->filled(
                    'warehouse_id'
                )
                    ? (int) $this->input(
                        'warehouse_id'
                    )
                    : null,

            'quantity' =>
                $this->filled(
                    'quantity'
                )
                    ? (int) $this->input(
                        'quantity'
                    )
                    : null,

            'reason' =>
                trim(
                    (string) $this->input(
                        'reason',
                        ''
                    )
                ),

            'notes' =>
                $this->filled(
                    'notes'
                )
                    ? trim(
                        (string) $this->input(
                            'notes'
                        )
                    )
                    : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'integer',

                Rule::exists(
                    'products',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'warehouse_id' => [
                'required',
                'integer',

                Rule::exists(
                    'warehouses',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],

            'reason' => [
                'required',
                'string',
                'max:500',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' =>
                'اختر المنتج.',

            'product_id.exists' =>
                'المنتج غير موجود أو غير نشط.',

            'warehouse_id.required' =>
                'اختر المخزن.',

            'warehouse_id.exists' =>
                'المخزن غير موجود أو غير نشط.',

            'quantity.required' =>
                'أدخل الكمية.',

            'quantity.integer' =>
                'الكمية يجب أن تكون رقماً صحيحاً.',

            'quantity.min' =>
                'الكمية يجب أن تكون قطعة واحدة على الأقل.',

            'reason.required' =>
                'اكتب سبب الإضافة.',

            'reason.max' =>
                'سبب الإضافة لا يتجاوز 500 حرف.',

            'notes.max' =>
                'الملاحظات لا تتجاوز 500 حرف.',
        ];
    }
}
