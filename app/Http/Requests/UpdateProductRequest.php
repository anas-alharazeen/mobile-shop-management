<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
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

            'code' =>
            mb_strtoupper(
                trim(
                    (string) $this->input(
                        'code',
                        ''
                    )
                )
            ),

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
        $product =
            $this->route(
                'product'
            );

        $productId =
            is_object($product)
            ? $product->id
            : $product;

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
            ],

            'code' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'products',
                    'code'
                )->ignore(
                    $productId
                ),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'products',
                    'barcode'
                )->ignore(
                    $productId
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

    public function messages(): array
    {
        return [
            'name.required' =>
            'اسم المنتج مطلوب.',

            'name.min' =>
            'اسم المنتج يجب أن يكون حرفين على الأقل.',

            'code.required' =>
            'كود المنتج مطلوب عند التعديل.',

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

            'selling_price.required' =>
            'سعر البيع مطلوب.',

            'minimum_selling_price.lte' =>
            'أقل سعر بيع لا يمكن أن يكون أكبر من سعر البيع الأساسي.',

            'image.image' =>
            'الملف المرفوع يجب أن يكون صورة.',

            'image.mimes' =>
            'الصورة يجب أن تكون JPG أو PNG أو WebP.',

            'image.max' =>
            'حجم الصورة لا يتجاوز 2MB.',
        ];
    }
}
