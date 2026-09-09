<template>
    <!-- طبقة الخلفية للموبايل -->
    <div
        v-if="isMobile && isMobileOpen"
        @click="closeMobile"
        class="fixed inset-0 z-30 bg-black/50 transition-opacity lg:hidden"
    />

    <!-- القائمة الجانبية -->
    <aside
        :class="[
            'fixed right-0 top-0 z-40 h-full w-64 transform border-l border-gray-200 bg-white transition-all duration-300 dark:border-gray-700 dark:bg-gray-800',
            isMobile ? 'shadow-2xl' : 'shadow-sm',
            isMobile && !isMobileOpen ? 'translate-x-full' : 'translate-x-0',
            !isMobile && isCollapsed ? 'w-20' : 'w-64',
        ]"
        style="top: 64px;"
    >
        <nav class="h-full overflow-y-auto p-4">
            <ul class="space-y-1">
                <!-- لوحة التحكم -->
                <li>
                    <Link
                        :href="route('dashboard')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('dashboard')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            لوحة التحكم
                        </span>
                    </Link>
                </li>

                <!-- الفئات -->
                <li>
                    <Link
                        :href="route('categories.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('categories.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            الفئات
                        </span>
                    </Link>
                </li>

                <!-- المنتجات -->
                <li>
                    <Link
                        :href="route('products.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('products.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            المنتجات
                        </span>
                    </Link>
                </li>

                <!-- إدارة المخزون -->
                <li>
                    <Link
                        :href="route('inventory.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('inventory.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            إدارة المخزون
                        </span>
                    </Link>
                </li>

                <!-- الجرد المخزني -->
                <li>
                    <Link
                        :href="route('inventory-counts.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('inventory-counts.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            الجرد المخزني
                        </span>
                    </Link>
                </li>

                <!-- الموردون -->
                <li>
                    <Link
                        :href="route('suppliers.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('suppliers.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            الموردون
                        </span>
                    </Link>
                </li>

                <!-- المشتريات -->
                <li>
                    <Link
                        :href="route('purchases.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('purchases.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            المشتريات
                        </span>
                    </Link>
                </li>

                <!-- العملاء -->
                <li>
                    <Link
                        :href="route('customers.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('customers.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            العملاء
                        </span>
                    </Link>
                </li>

                <hr class="my-2 border-gray-200 dark:border-gray-700" />

                <!-- نقطة البيع -->
                <li>
                    <Link
                        :href="route('pos')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('pos')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            نقطة البيع
                        </span>
                    </Link>
                </li>

                <!-- فواتير البيع -->
                <li>
                    <Link
                        :href="route('sales.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('sales.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            فواتير البيع
                        </span>
                    </Link>
                </li>

                <!-- الصيانة -->
                <li>
                    <Link
                        :href="route('repairs.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('repairs.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            الصيانة
                        </span>
                    </Link>
                </li>

                <!-- الدفعات -->
                <li>
                    <Link
                        :href="route('payments.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('payments.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            الدفعات
                        </span>
                    </Link>
                </li>

                <!-- المركز المالي -->
                <li>
                    <Link
                        :href="route('finance.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('finance.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            المركز المالي
                        </span>
                    </Link>
                </li>

                <!-- التحويلات بين الحسابات -->
                <li>
                    <Link
                        :href="route('finance.transfers')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('finance.transfers')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            تحويلات الحسابات
                        </span>
                    </Link>
                </li>

                <!-- المصروفات -->
                <li>
                    <Link
                        :href="route('finance.expenses')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('finance.expenses')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            المصروفات
                        </span>
                    </Link>
                </li>

                <!-- الإغلاق اليومي -->
                <li>
                    <Link
                        :href="route('finance.closings')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('finance.closings')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            الإغلاق اليومي
                        </span>
                    </Link>
                </li>

                <!-- المرتجعات والاستبدال -->
                <li>
                    <Link
                        :href="route('returns.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('returns.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            المرتجعات والاستبدال
                        </span>
                    </Link>
                </li>

                <hr class="my-2 border-gray-200 dark:border-gray-700" />

                <!-- التقارير -->
                <li>
                    <Link
                        :href="route('reports.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('reports.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            التقارير
                        </span>
                    </Link>
                </li>

                <!-- الإعدادات -->
                <li>
                    <Link
                        :href="route('settings.index')"
                        :class="[
                            'flex items-center rounded-lg px-4 py-3 transition-colors',
                            route().current('settings.*')
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                                : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700',
                            isCollapsed && !isMobile ? 'justify-center' : 'gap-3',
                        ]"
                        @click="closeMobile"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span
                            v-if="!(isCollapsed && !isMobile)"
                            class="text-sm font-medium"
                        >
                            الإعدادات
                        </span>
                    </Link>
                </li>
            </ul>
        </nav>
    </aside>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    isCollapsed: {
        type: Boolean,
        default: false,
    },
    isMobileOpen: {
        type: Boolean,
        default: false,
    },
    isMobile: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close-mobile']);

const closeMobile = () => {
    emit('close-mobile');
};
</script>
