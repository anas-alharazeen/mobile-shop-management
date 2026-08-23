<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' =>
            trim(
                (string) $this->input(
                    'name',
                    ''
                )
            ),

            /*
             * الكود يمكن تركه فارغاً ليولده الـBackend.
             */
            'code' =>
            $this->filled('code')
                ? mb_strtoupper(
                    trim(
                        (string) $this
                            ->input('code')
                    )
                )
                : null,

            'barcode' =>
            $this->filled('barcode')
                ? trim(
                    (string) $this
                        ->input('barcode')
                )
                : null,

            'brand' =>
            $this->filled('brand')
                ? trim(
                    (string) $this
                        ->input('brand')
                )
                : null,

            'model' =>
            $this->filled('model')
                ? trim(
                    (string) $this
                        ->input('model')
                )
                : null,

            'location' =>
            $this->filled('location')
                ? trim(
                    (string) $this
                        ->input('location')
                )
                : null,

            'description' =>
            $this->filled('description')
                ? trim(
                    (string) $this
                        ->input('description')
                )
                : null,

            'notes' =>
            $this->filled('notes')
                ? trim(
                    (string) $this
                        ->input('notes')
                )
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(
                    'products',
                    'code'
                ),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(
                    'products',
                    'barcode'
                ),
            ],

            'category_id' => [
                'required',
                'integer',

                Rule::exists(
                    'categories',
                    'id'
                )
                    ->where(
                        'is_active',
                        true
                    )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],

            'brand' => [
                'nullable',
                'string',
                'max:100',
            ],

            'model' => [
                'nullable',
                'string',
                'max:100',
            ],

            'purchase_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'minimum_selling_price' => [
                'nullable',
                'numeric',
                'min:0',
                'lte:selling_price',
            ],

            'low_stock_threshold' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
             * الرصيد الافتتاحي اختياري.
             * للمشتريات الجديدة يفضل تركه فارغاً وإنشاء فاتورة شراء.
             */
            'opening_stocks' => [
                'nullable',
                'array',
                'max:50',
            ],

            'opening_stocks.*.warehouse_id' => [
                'required',
                'integer',
                'distinct',

                Rule::exists(
                    'warehouses',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'opening_stocks.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100000000',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
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
                    ->isNotEmpty()
                ) {
                    return;
                }

                $openingQuantity =
                    collect(
                        $this->input(
                            'opening_stocks',
                            []
                        )
                    )
                    ->sum(
                        fn(array $row): int =>
                        (int) (
                            $row['quantity']
                            ?? 0
                        )
                    );

                /*
                 * وجود مخزون افتتاحي بقيمة تكلفة صفر
                 * يجعل قيمة المخزون ومتوسط التكلفة غير منطقيين.
                 */
                if (
                    $openingQuantity > 0
                    && (float) $this
                        ->input(
                            'purchase_price',
                            0
                        ) <= 0
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'purchase_price',
                            'عند إدخال رصيد افتتاحي يجب أن يكون سعر الشراء أكبر من صفر.'
                        );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' =>
            'اسم المنتج مطلوب.',

            'name.min' =>
            'اسم المنتج يجب أن يكون حرفين على الأقل.',

            'code.unique' =>
            'كود المنتج مستخدم بالفعل.',

            'barcode.unique' =>
            'الباركود مستخدم بالفعل.',

            'category_id.required' =>
            'اختر فئة المنتج.',

            'category_id.exists' =>
            'الفئة المحددة غير موجودة أو غير نشطة.',

            'purchase_price.required' =>
            'سعر الشراء مطلوب.',

            'purchase_price.min' =>
            'سعر الشراء لا يمكن أن يكون سالباً.',

            'selling_price.required' =>
            'سعر البيع مطلوب.',

            'selling_price.min' =>
            'سعر البيع لا يمكن أن يكون سالباً.',

            'minimum_selling_price.lte' =>
            'أقل سعر بيع لا يمكن أن يكون أكبر من سعر البيع الأساسي.',

            'low_stock_threshold.required' =>
            'حدد حد تنبيه المخزون.',

            'opening_stocks.*.warehouse_id.required' =>
            'اختر المخزن للرصيد الافتتاحي.',

            'opening_stocks.*.warehouse_id.distinct' =>
            'لا يمكن تكرار نفس المخزن في الرصيد الافتتاحي.',

            'opening_stocks.*.warehouse_id.exists' =>
            'أحد المخازن المحددة غير موجود أو غير نشط.',

            'opening_stocks.*.quantity.required' =>
            'أدخل الكمية الافتتاحية.',

            'opening_stocks.*.quantity.min' =>
            'الكمية الافتتاحية يجب أن تكون قطعة واحدة على الأقل.',

            'image.image' =>
            'الملف المرفوع يجب أن يكون صورة.',

            'image.mimes' =>
            'الصورة يجب أن تكون JPG أو PNG أو WebP.',

            'image.max' =>
            'حجم الصورة لا يتجاوز 2MB.',
        ];
    }
}
