<?php

namespace App\Http\Requests;

use App\Models\ProductStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DeductStockRequest extends FormRequest
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

            'type' => [
                'required',
                'string',

                Rule::in([
                    'manual_deduction',
                    'damaged',
                    'lost',
                ]),
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

    public function after(): array
    {
        return [
            function (
                Validator $validator
            ): void {
                if (
                    $validator
                        ->errors()
                        ->hasAny([
                            'product_id',
                            'warehouse_id',
                            'quantity',
                        ])
                ) {
                    return;
                }

                $available =
                    (int) ProductStock::query()
                        ->where(
                            'product_id',
                            (int) $this->input(
                                'product_id'
                            )
                        )
                        ->where(
                            'warehouse_id',
                            (int) $this->input(
                                'warehouse_id'
                            )
                        )
                        ->value(
                            'quantity'
                        );

                $quantity =
                    (int) $this->input(
                        'quantity'
                    );

                if ($available <= 0) {
                    $validator
                        ->errors()
                        ->add(
                            'warehouse_id',
                            'لا يوجد رصيد لهذا المنتج في المخزن المحدد.'
                        );

                    return;
                }

                if (
                    $quantity
                    > $available
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'quantity',
                            "الكمية المطلوبة ({$quantity}) أكبر من الرصيد المتوفر ({$available})."
                        );
                }
            },
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

            'type.required' =>
                'اختر نوع الخصم.',

            'type.in' =>
                'نوع الخصم غير صحيح.',

            'reason.required' =>
                'اكتب سبب الخصم.',

            'reason.max' =>
                'سبب الخصم لا يتجاوز 500 حرف.',

            'notes.max' =>
                'الملاحظات لا تتجاوز 500 حرف.',
        ];
    }
}
