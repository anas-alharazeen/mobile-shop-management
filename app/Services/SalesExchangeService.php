<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\PaymentMethod;
use App\Enums\SalesInvoiceStatus;
use App\Enums\TransactionType;
use App\Models\FinancialAccount;
use App\Models\Product;
use App\Models\SalesExchange;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesExchangeService
{
    public function __construct(
        private readonly SalesReturnService $salesReturnService,
        private readonly SalesInvoiceService $salesInvoiceService,
        private readonly FinancialAccountService $financialService
    ) {}

    public function create(
        array $data
    ): SalesExchange {
        return DB::transaction(
            function () use (
                $data
            ): SalesExchange {
                $originalInvoice =
                    SalesInvoice::query()
                    ->with([
                        'customer',
                        'items',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['original_invoice_id']
                    );

                if (
                    $originalInvoice->status
                    !== SalesInvoiceStatus::APPROVED
                ) {
                    throw new \RuntimeException(
                        'يمكن تنفيذ الاستبدال على فاتورة بيع معتمدة فقط.'
                    );
                }

                $exchangeDate =
                    Carbon::parse(
                        $data['exchange_date'] ?? now()
                    )->startOfDay();

                $invoiceDate =
                    Carbon::parse(
                        $originalInvoice
                            ->sale_date
                            ?? $originalInvoice
                            ->created_at
                    )->startOfDay();

                if (
                    $exchangeDate
                    ->lt(
                        $invoiceDate
                    )
                ) {
                    throw new \RuntimeException(
                        'تاريخ الاستبدال لا يمكن أن يسبق تاريخ الفاتورة الأصلية.'
                    );
                }

                if (
                    $exchangeDate
                    ->gt(
                        today()
                    )
                ) {
                    throw new \RuntimeException(
                        'تاريخ الاستبدال لا يمكن أن يكون في المستقبل.'
                    );
                }

                /*
                 * العميل في الاستبدال يأتي دائماً من الفاتورة الأصلية.
                 * لا نسمح للواجهة بتغييره إلى عميل مختلف.
                 */
                $returnItems =
                    collect(
                        $data['return_items'] ?? []
                    )
                    ->map(
                        function (
                            array $item
                        ) use (
                            $originalInvoice
                        ): array {
                            $invoiceItem =
                                $originalInvoice
                                ->items
                                ->firstWhere(
                                    'id',
                                    (int) $item['sales_invoice_item_id']
                                );

                            if (! $invoiceItem) {
                                throw new \RuntimeException(
                                    'أحد المنتجات المرتجعة لا ينتمي إلى الفاتورة الأصلية.'
                                );
                            }

                            $item['warehouse_id'] =
                                $item['warehouse_id']
                                ?? $invoiceItem
                                ->warehouse_id;

                            return $item;
                        }
                    )
                    ->all();

                if (empty($returnItems)) {
                    throw new \RuntimeException(
                        'اختر منتجاً واحداً على الأقل من الفاتورة الأصلية للاستبدال.'
                    );
                }

                if (
                    empty($data['new_items']
                        ?? [])
                ) {
                    throw new \RuntimeException(
                        'اختر منتجاً بديلاً واحداً على الأقل.'
                    );
                }

                /*
                 * أولاً: إنشاء مرتجع المبيعات واعتماده بوضع exchange.
                 * هذه الخطوة تقلل دين الفاتورة الأصلية أولاً.
                 * الزيادة فقط تصبح exchange_credit.
                 */
                $salesReturn =
                    $this
                    ->salesReturnService
                    ->create([
                        'sales_invoice_id' =>
                        $originalInvoice
                            ->id,

                        'return_date' =>
                        $exchangeDate
                            ->toDateString(),

                        'reason' =>
                        trim(
                            (string) (
                                $data['reason']
                                ?? 'استبدال منتج'
                            )
                        ),

                        'notes' =>
                        $data['notes'] ?? null,

                        'items' =>
                        $returnItems,
                    ]);

                $salesReturn =
                    $this
                    ->salesReturnService
                    ->approve(
                        $salesReturn,
                        'exchange'
                    );

                /*
                 * ثانياً: إنشاء فاتورة البدائل باسم نفس العميل
                 * وببيانات العميل الأصلية دون تعديل.
                 */
                $newInvoice =
                    $this
                    ->salesInvoiceService
                    ->create([
                        'customer_id' =>
                        $originalInvoice
                            ->customer_id,

                        'customer_name' =>
                        $originalInvoice
                            ->customer_name
                            ?? $originalInvoice
                            ->customer
                            ?->name
                            ?? 'عميل نقدي',

                        'customer_phone' =>
                        $originalInvoice
                            ->customer_phone
                            ?? $originalInvoice
                            ->customer
                            ?->phone,

                        'sale_date' =>
                        $exchangeDate
                            ->toDateString(),

                        'items' =>
                        $data['new_items'],

                        'invoice_discount' =>
                        (float) (
                            $data['invoice_discount'] ?? 0
                        ),

                        'notes' =>
                        "استبدال مرتبط بالفاتورة {$originalInvoice->invoice_number}"
                            . (
                                ! empty($data['notes']
                                    ?? null)
                                ? ' - '
                                . $data['notes']
                                : ''
                            ),
                    ]);

                /*
                 * أقل سعر بيع يُفحص بعد إنشاء الفاتورة الجديدة،
                 * لأن SalesInvoiceService يكون قد وزع خصم
                 * الفاتورة على البنود وأصبح line_total هو
                 * صافي السعر الحقيقي لكل بند.
                 */
                $minimumPriceCheck =
                    $this
                        ->minimumPriceCheck(
                            $newInvoice
                        );

                if (
                    $minimumPriceCheck[
                        'requires_approval'
                    ]
                ) {
                    if (
                        ! (bool) (
                            $data[
                                'minimum_price_approved'
                            ]
                            ?? false
                        )
                    ) {
                        throw new \RuntimeException(
                            $this
                                ->minimumPriceApprovalMessage(
                                    $minimumPriceCheck
                                )
                        );
                    }

                    $approvalReason =
                        trim(
                            (string) (
                                $data[
                                    'minimum_price_reason'
                                ]
                                ?? ''
                            )
                        );

                    if (
                        mb_strlen(
                            $approvalReason
                        ) < 3
                    ) {
                        throw new \RuntimeException(
                            'اكتب سبباً واضحاً لموافقة صاحب المحل على الاستبدال بسعر أقل من الحد الأدنى.'
                        );
                    }

                    $approvalNote =
                        $this
                            ->minimumPriceApprovalNote(
                                $approvalReason,
                                $minimumPriceCheck
                            );

                    $newInvoice->update([
                        'notes' =>
                            $this
                                ->appendNote(
                                    $newInvoice
                                        ->notes,
                                    $approvalNote
                                ),
                    ]);

                    $data['notes'] =
                        $this
                            ->appendNote(
                                $data[
                                    'notes'
                                ] ?? null,
                                $approvalNote
                            );
                }

                $returnValue =
                    (float) $salesReturn
                        ->total_amount;

                /*
                 * مهم:
                 * exchange_credit ليس دائماً return_value.
                 * إذا كان على الفاتورة الأصلية دين،
                 * يتم تخفيض الدين أولاً.
                 */
                $exchangeCredit =
                    (float) $salesReturn
                        ->amount_refunded;

                $newItemsValue =
                    (float) $newInvoice
                        ->total_amount;

                $creditApplied =
                    min(
                        $exchangeCredit,
                        $newItemsValue
                    );

                if (
                    $creditApplied
                    > 0.00001
                ) {
                    $this
                        ->salesInvoiceService
                        ->addPayment(
                            $newInvoice,
                            [
                                'amount' =>
                                $creditApplied,

                                'payment_method' =>
                                PaymentMethod::EXCHANGE_CREDIT
                                    ->value,

                                'paid_at' =>
                                $exchangeDate
                                    ->toDateString(),

                                'notes' =>
                                "رصيد من المرتجع {$salesReturn->return_number}",
                            ]
                        );
                }

                $priceDifference =
                    $newItemsValue
                    - $exchangeCredit;

                $settlementType =
                    match (true) {
                        $priceDifference
                            > 0.00001 =>
                        'customer_pays',

                        $priceDifference
                            < -0.00001 =>
                        'customer_gets_refund',

                        default =>
                        'no_difference',
                    };

                $account = null;

                if (
                    $settlementType
                    !== 'no_difference'
                ) {
                    if (
                        empty($data['financial_account_id'])
                    ) {
                        throw new \RuntimeException(
                            'اختر الحساب المالي لتسوية فرق الاستبدال.'
                        );
                    }

                    $account =
                        FinancialAccount::query()
                        ->active()
                        ->lockForUpdate()
                        ->findOrFail(
                            $data['financial_account_id']
                        );
                }

                /*
                 * إذا كان الفرق لصالح المعرض:
                 * نسجله كدفعة على الفاتورة الجديدة.
                 */
                if (
                    $settlementType
                    === 'customer_pays'
                ) {
                    $this
                        ->salesInvoiceService
                        ->addPayment(
                            $newInvoice,
                            [
                                'amount' =>
                                $priceDifference,

                                'payment_method' =>
                                $this
                                    ->paymentMethodForAccount(
                                        $account
                                    )
                                    ->value,

                                'financial_account_id' =>
                                $account->id,

                                'paid_at' =>
                                $exchangeDate
                                    ->toDateString(),

                                'notes' =>
                                "فرق استبدال لصالح المعرض من المرتجع {$salesReturn->return_number}",
                            ]
                        );
                }

                /*
                 * اعتماد الفاتورة الجديدة بعد اكتمال الدفعات.
                 * SalesInvoiceService يتولى التحقق من المخزون
                 * والقفل والتسجيل المالي للدفعات.
                 */
                $newInvoice =
                    $this
                    ->salesInvoiceService
                    ->approve(
                        $newInvoice
                    );

                $exchange =
                    SalesExchange::create([
                        'exchange_number' =>
                        SalesExchange::generateNumber(),

                        'sales_return_id' =>
                        $salesReturn->id,

                        'new_sales_invoice_id' =>
                        $newInvoice->id,

                        'return_value' =>
                        round(
                            $returnValue,
                            2
                        ),

                        'exchange_credit' =>
                        round(
                            $exchangeCredit,
                            2
                        ),

                        'new_items_value' =>
                        round(
                            $newItemsValue,
                            2
                        ),

                        'price_difference' =>
                        round(
                            $priceDifference,
                            2
                        ),

                        'settlement_type' =>
                        $settlementType,

                        'financial_account_id' =>
                        $account?->id,

                        'notes' =>
                        $data['notes']
                            ?? null,

                        'exchanged_at' =>
                        $exchangeDate,
                    ]);

                /*
                 * إذا كان الفرق لصالح العميل:
                 * الفاتورة الجديدة غُطيت برصيد الاستبدال،
                 * والزيادة تُدفع للعميل من الحساب المختار.
                 */
                if (
                    $settlementType
                    === 'customer_gets_refund'
                ) {
                    $this
                        ->financialService
                        ->addOutflow(
                            $account,
                            TransactionType::EXCHANGE_DIFFERENCE,
                            abs(
                                $priceDifference
                            ),
                            "رد فرق استبدال - {$exchange->exchange_number}",
                            $exchange
                        );
                }

                return $exchange
                    ->fresh()
                    ->load([
                        'salesReturn.customer',
                        'salesReturn.items.product',
                        'newInvoice.customer',
                        'newInvoice.items.product',
                        'account',
                    ]);
            }
        );
    }

    /**
     * فحص صافي سعر المنتجات البديلة بعد كل الخصومات.
     */
    private function minimumPriceCheck(
        SalesInvoice $invoice
    ): array {
        $invoice->loadMissing(
            'items.product'
        );

        $violations = [];

        foreach (
            $invoice->items
            as $item
        ) {
            $product =
                $item->product;

            if (! $product) {
                continue;
            }

            $minimum =
                max(
                    0,
                    (float) (
                        $product
                            ->minimum_selling_price
                        ?? 0
                    )
                );

            if ($minimum <= 0) {
                continue;
            }

            $quantity =
                max(
                    1,
                    (int) $item
                        ->quantity
                );

            $actualUnit =
                round(
                    (float) $item
                        ->line_total
                    / $quantity,
                    4
                );

            if (
                $actualUnit
                + 0.00001
                < $minimum
            ) {
                $violations[] = [
                    'product_name' =>
                        $product->name,

                    'minimum_price' =>
                        round(
                            $minimum,
                            2
                        ),

                    'actual_price' =>
                        round(
                            $actualUnit,
                            2
                        ),

                    'difference' =>
                        round(
                            $minimum
                            - $actualUnit,
                            2
                        ),
                ];
            }
        }

        return [
            'requires_approval' =>
                ! empty(
                    $violations
                ),

            'violations' =>
                $violations,
        ];
    }

    private function minimumPriceApprovalMessage(
        array $check
    ): string {
        $first =
            $check[
                'violations'
            ][0] ?? null;

        if (! $first) {
            return 'الاستبدال يحتاج موافقة صاحب المحل بسبب انخفاض سعر أحد المنتجات عن الحد الأدنى.';
        }

        return
            'المنتج «'
            .$first[
                'product_name'
            ]
            .'» أصبح صافي سعره '
            .number_format(
                (float) $first[
                    'actual_price'
                ],
                2,
                '.',
                ','
            )
            .' شيكل، بينما أقل سعر بيع هو '
            .number_format(
                (float) $first[
                    'minimum_price'
                ],
                2,
                '.',
                ','
            )
            .' شيكل. يلزم موافقة صاحب المحل لإتمام الاستبدال.';
    }

    private function minimumPriceApprovalNote(
        string $reason,
        array $check
    ): string {
        $products =
            collect(
                $check[
                    'violations'
                ] ?? []
            )
                ->pluck(
                    'product_name'
                )
                ->filter()
                ->unique()
                ->implode(
                    '، '
                );

        $note =
            '[موافقة صاحب المحل على استبدال تحت أقل سعر] '
            .'السبب: '
            .$reason;

        if ($products !== '') {
            $note .=
                ' | المنتجات: '
                .$products;
        }

        return $note;
    }

    private function appendNote(
        ?string $existing,
        string $note
    ): string {
        $existing =
            trim(
                (string) $existing
            );

        return $existing !== ''
            ? $existing
                .PHP_EOL
                .$note
            : $note;
    }

    private function paymentMethodForAccount(
        FinancialAccount $account
    ): PaymentMethod {
        return match ($account->type) {
            AccountType::CASH =>
            PaymentMethod::CASH,

            AccountType::BANK =>
            PaymentMethod::BANK_TRANSFER,

            AccountType::BANKING_APP =>
            PaymentMethod::BANKING_APP,
        };
    }
}
