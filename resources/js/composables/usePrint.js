export function usePrint() {
    const printPage = () => {
        if (
            typeof window === 'undefined'
            || typeof window.print !== 'function'
        ) {
            console.error(
                'تعذر تشغيل نافذة الطباعة في هذا المتصفح.'
            );

            return;
        }

        window.focus();

        window.setTimeout(() => {
            window.print();
        }, 100);
    };

    return {
        printPage,
    };
}
