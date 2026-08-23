<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                Rule::unique('categories', 'name')->ignore($categoryId),
            ],
            'type' => [
                'required',
                'string',
                Rule::in(CategoryType::values()),
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الفئة مطلوب',
            'name.unique' => 'هذا الاسم موجود بالفعل',
            'name.min' => 'اسم الفئة يجب أن يكون حرفين على الأقل',
            'name.max' => 'اسم الفئة لا يتجاوز 255 حرف',
            'type.required' => 'نوع الفئة مطلوب',
            'type.in' => 'نوع الفئة غير معتمد',
            'description.max' => 'الوصف لا يتجاوز 1000 حرف',
        ];
    }
}
