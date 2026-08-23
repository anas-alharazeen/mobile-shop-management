<?php

namespace App\Http\Requests;

use App\Models\ProductStock;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransferStockRequest extends FormRequest
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

            'source_warehouse_id' =>
                $this->filled(
                    'source_warehouse_id'
                )
                    ? (int) $this->input(
                        'source_warehouse_id'
                    )
                    : null,

            'destination_warehouse_id' =>
                $this->filled(
                    'destination_warehouse_id'
                )
                    ? (int) $this->input(
                        'destination_warehouse_id'
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

            'source_warehouse_id' => [
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

            'destination_warehouse_id' => [
                'required',
                'integer',
                'different:source_warehouse_id',

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
                            'source_warehouse_id',
                            'destination_warehouse_id',
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
                                'source_warehouse_id'
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
                            'source_warehouse_id',
                            'المنتج لا يملك رصيداً متاحاً في المخزن المصدر.'
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
                'اختر المنتج المراد نقله.',

            'product_id.exists' =>
                'المنتج غير موجود أو غير نشط.',

            'source_warehouse_id.required' =>
                'اختر المخزن المصدر.',

            'source_warehouse_id.exists' =>
                'المخزن المصدر غير موجود أو غير نشط.',

            'destination_warehouse_id.required' =>
                'اختر المخزن المستلم.',

            'destination_warehouse_id.exists' =>
                'المخزن المستلم غير موجود أو غير نشط.',

            'destination_warehouse_id.different' =>
                'يجب أن يكون المخزن المستلم مختلفاً عن المخزن المصدر.',

            'quantity.required' =>
                'أدخل الكمية المراد نقلها.',

            'quantity.integer' =>
                'الكمية يجب أن تكون رقماً صحيحاً.',

            'quantity.min' =>
                'الكمية يجب أن تكون قطعة واحدة على الأقل.',

            'reason.required' =>
                'اكتب سبب نقل المخزون.',

            'reason.max' =>
                'سبب النقل لا يتجاوز 500 حرف.',

            'notes.max' =>
                'الملاحظات لا تتجاوز 500 حرف.',
        ];
    }
}
