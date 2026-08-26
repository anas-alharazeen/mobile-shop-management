<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\PurchaseInvoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SavePurchaseInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items =
            collect(
                $this->input(
                    'items',
                    []
                )
            )
            ->map(
                function (
                    mixed $item
                ): array {
                    $row =
                        is_array($item)
                            ? $item
                            : [];

                    $mode =
                        ($row['product_mode'] ?? 'existing')
                        === 'new'
                            ? 'new'
                            : 'existing';

                    $newProduct =
                        is_array(
                            $row['new_product']
                            ?? null
                        )
                            ? $row['new_product']
                            : [];

                    foreach (
                        [
                            'name',
                            'barcode',
                            'brand',
                            'model',
                            'location',
                            'description',
                            'notes',
                        ]
                        as $field
                    ) {
                        if (
                            array_key_exists(
                                $field,
                                $newProduct
                            )
                        ) {
                            $value =
                                trim(
                                    (string) (
                                        $newProduct[$field]
                                        ?? ''
                                    )
                                );

                            $newProduct[$field] =
                                $value !== ''
                                    ? $value
                                    : null;
                        }
                    }

                    if (
                        array_key_exists(
                            'code',
                            $newProduct
                        )
                    ) {
                        $code =
                            mb_strtoupper(
                                trim(
                                    (string) (
                                        $newProduct['code']
                                        ?? ''
                                    )
                                )
                            );

                        $newProduct['code'] =
                            $code !== ''
                                ? $code
                                : null;
                    }

                    if (
                        array_key_exists(
                            'minimum_selling_price',
                            $newProduct
                        )
                        && $newProduct[
                            'minimum_selling_price'
                        ] === ''
                    ) {
                        $newProduct[
                            'minimum_selling_price'
                        ] = null;
                    }

                    $row['product_mode'] =
                        $mode;

                    $row['new_product'] =
                        $newProduct;

                    return $row;
                }
            )
            ->values()
            ->all();

        $this->merge([
            'items' => $items,

            'supplier_invoice_number' =>
            $this->filled(
                'supplier_invoice_number'
            )
                ? trim(
                    (string) $this->input(
                        'supplier_invoice_number'
                    )
                )
                : null,

            'notes' =>
            $this->filled('notes')
                ? trim(
                    (string) $this->input(
                        'notes'
                    )
                )
                : null,

            'bank_or_app_name' =>
            $this->filled(
                'bank_or_app_name'
            )
                ? trim(
                    (string) $this->input(
                        'bank_or_app_name'
                    )
                )
                : null,

            'transaction_reference' =>
            $this->filled(
                'transaction_reference'
            )
                ? trim(
                    (string) $this->input(
                        'transaction_reference'
                    )
                )
                : null,
        ]);
    }

    public function rules(): array
    {
        $invoice =
            $this->route(
                'purchaseInvoice'
            );

        $invoiceId =
            $invoice instanceof
                PurchaseInvoice
                ? $invoice->id
                : null;

        $paymentRequired =
            (float) $this->input(
                'payment_amount',
                0
            ) > 0;

        return [
            'supplier_id' => [
                'required',
                'integer',

                Rule::exists(
                    'suppliers',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'supplier_invoice_number' => [
                'nullable',
                'string',
                'max:255',

                Rule::unique(
                    'purchase_invoices',
                    'supplier_invoice_number'
                )
                    ->where(
                        fn($query) =>
                        $query->where(
                            'supplier_id',
                            $this->input(
                                'supplier_id'
                            )
                        )
                    )
                    ->ignore(
                        $invoiceId
                    ),
            ],

            'purchase_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:purchase_date',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'items.*.product_mode' => [
                'required',
                Rule::in([
                    'existing',
                    'new',
                ]),
            ],

            'items.*.product_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'products',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'items.*.warehouse_id' => [
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

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100000000',
            ],

            'items.*.unit_purchase_price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'items.*.line_discount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            /*
             * بيانات المنتج الجديد.
             *
             * تبقى nullable على مستوى Rules،
             * ويحدد after() الحقول الإلزامية فقط
             * عندما يكون product_mode = new.
             */
            'items.*.new_product' => [
                'nullable',
                'array',
            ],

            'items.*.new_product.category_id' => [
                'nullable',
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

            'items.*.new_product.name' => [
                'nullable',
                'string',
                'min:2',
                'max:255',
            ],

            'items.*.new_product.code' => [
                'nullable',
                'string',
                'max:50',

                /*
                 * لا نضع whereNull(deleted_at)،
                 * لأن قاعدة البيانات نفسها تفرض Unique
                 * حتى لو كان المنتج Soft Deleted.
                 */
                Rule::unique(
                    'products',
                    'code'
                ),
            ],

            'items.*.new_product.barcode' => [
                'nullable',
                'string',
                'max:50',

                Rule::unique(
                    'products',
                    'barcode'
                ),
            ],

            'items.*.new_product.brand' => [
                'nullable',
                'string',
                'max:100',
            ],

            'items.*.new_product.model' => [
                'nullable',
                'string',
                'max:100',
            ],

            'items.*.new_product.selling_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'items.*.new_product.minimum_selling_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'items.*.new_product.low_stock_threshold' => [
                'nullable',
                'integer',
                'min:0',
                'max:1000000',
            ],

            'items.*.new_product.location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.new_product.description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'items.*.new_product.notes' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'shipping_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'additional_expenses' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:500',
            ],

            /*
             * الدفعة الأولية تستخدم عند إنشاء فاتورة جديدة فقط.
             * في شاشة التعديل لا ترسل هذه الحقول عادةً.
             */
            'payment_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'payment_method' => [
                Rule::requiredIf(
                    $paymentRequired
                ),
                'nullable',
                'string',

                Rule::in(
                    array_keys(
                        PaymentMethod
                            ::selectableLabels()
                    )
                ),
            ],

            'financial_account_id' => [
                Rule::requiredIf(
                    $paymentRequired
                ),
                'nullable',
                'integer',

                Rule::exists(
                    'financial_accounts',
                    'id'
                )->where(
                    'is_active',
                    true
                ),
            ],

            'bank_or_app_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:255',
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

                $items =
                    $this->input(
                        'items',
                        []
                    );

                $existingProductIds = [];
                $newCodes = [];
                $newBarcodes = [];

                $subtotal = 0.0;

                foreach (
                    $items
                    as $index => $item
                ) {
                    $mode =
                        $item['product_mode']
                        ?? 'existing';

                    if (
                        $mode
                        === 'existing'
                    ) {
                        $productId =
                            (int) (
                                $item['product_id']
                                ?? 0
                            );

                        if (
                            $productId <= 0
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    "items.{$index}.product_id",
                                    'اختر المنتج الموجود.'
                                );
                        } elseif (
                            in_array(
                                $productId,
                                $existingProductIds,
                                true
                            )
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    "items.{$index}.product_id",
                                    'لا تكرر نفس المنتج في الفاتورة. عدّل الكمية في البند الموجود.'
                                );
                        } else {
                            $existingProductIds[] =
                                $productId;
                        }
                    } else {
                        $newProduct =
                            $item['new_product']
                            ?? [];

                        if (
                            empty(
                                $newProduct[
                                    'category_id'
                                ]
                            )
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    "items.{$index}.new_product.category_id",
                                    'اختر فئة المنتج الجديد.'
                                );
                        }

                        if (
                            mb_strlen(
                                trim(
                                    (string) (
                                        $newProduct[
                                            'name'
                                        ]
                                        ?? ''
                                    )
                                )
                            ) < 2
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    "items.{$index}.new_product.name",
                                    'أدخل اسم المنتج الجديد بوضوح.'
                                );
                        }

                        if (
                            ! array_key_exists(
                                'selling_price',
                                $newProduct
                            )
                            || $newProduct[
                                'selling_price'
                            ] === null
                            || $newProduct[
                                'selling_price'
                            ] === ''
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    "items.{$index}.new_product.selling_price",
                                    'أدخل سعر البيع للمنتج الجديد.'
                                );
                        }

                        $sellingPrice =
                            (float) (
                                $newProduct[
                                    'selling_price'
                                ]
                                ?? 0
                            );

                        $minimumPrice =
                            $newProduct[
                                'minimum_selling_price'
                            ]
                                ?? null;

                        if (
                            $minimumPrice !== null
                            && $minimumPrice !== ''
                            && (float) $minimumPrice
                                > $sellingPrice
                        ) {
                            $validator
                                ->errors()
                                ->add(
                                    "items.{$index}.new_product.minimum_selling_price",
                                    'أقل سعر بيع لا يمكن أن يكون أكبر من سعر البيع الأساسي.'
                                );
                        }

                        $code =
                            mb_strtoupper(
                                trim(
                                    (string) (
                                        $newProduct[
                                            'code'
                                        ]
                                        ?? ''
                                    )
                                )
                            );

                        if ($code !== '') {
                            if (
                                in_array(
                                    $code,
                                    $newCodes,
                                    true
                                )
                            ) {
                                $validator
                                    ->errors()
                                    ->add(
                                        "items.{$index}.new_product.code",
                                        'كود المنتج الجديد مكرر داخل الفاتورة.'
                                    );
                            }

                            $newCodes[] =
                                $code;
                        }

                        $barcode =
                            trim(
                                (string) (
                                    $newProduct[
                                        'barcode'
                                    ]
                                    ?? ''
                                )
                            );

                        if ($barcode !== '') {
                            if (
                                in_array(
                                    $barcode,
                                    $newBarcodes,
                                    true
                                )
                            ) {
                                $validator
                                    ->errors()
                                    ->add(
                                        "items.{$index}.new_product.barcode",
                                        'الباركود مكرر داخل الفاتورة.'
                                    );
                            }

                            $newBarcodes[] =
                                $barcode;
                        }
                    }

                    $quantity =
                        (int) (
                            $item['quantity']
                            ?? 0
                        );

                    $unitPrice =
                        (float) (
                            $item[
                                'unit_purchase_price'
                            ]
                            ?? 0
                        );

                    $lineDiscount =
                        (float) (
                            $item[
                                'line_discount'
                            ]
                            ?? 0
                        );

                    $lineSubtotal =
                        $quantity
                        * $unitPrice;

                    if (
                        $lineDiscount
                        > $lineSubtotal
                        + 0.00001
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                "items.{$index}.line_discount",
                                'خصم البند لا يمكن أن يتجاوز قيمة البند.'
                            );
                    }

                    $subtotal +=
                        max(
                            0,
                            $lineSubtotal
                                - $lineDiscount
                        );
                }

                $invoiceDiscount =
                    (float) $this->input(
                        'discount_amount',
                        0
                    );

                if (
                    $invoiceDiscount
                    > $subtotal
                    + 0.00001
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'discount_amount',
                            'خصم الفاتورة لا يمكن أن يتجاوز مجموع البنود.'
                        );
                }

                $total =
                    max(
                        0,
                        $subtotal
                        - $invoiceDiscount
                        + (float) $this->input(
                            'shipping_cost',
                            0
                        )
                        + (float) $this->input(
                            'additional_expenses',
                            0
                        )
                    );

                $paymentAmount =
                    (float) $this->input(
                        'payment_amount',
                        0
                    );

                if (
                    $paymentAmount
                    > $total
                    + 0.00001
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'payment_amount',
                            'الدفعة الأولية لا يمكن أن تتجاوز إجمالي الفاتورة.'
                        );
                }
            },
        ];
    }
}
