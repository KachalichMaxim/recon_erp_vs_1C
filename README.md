# ERP vs 1C reconciliation audit package

Пакет для аудита ТЗ по матрице акта сверки ERP и 1С.

## Основные артефакты

- `docs/TZ_ERP_1C_Akt_Sverki_Matrix.docx` - итоговое ТЗ.
- `docs/TZ_ERP_1C_Akt_Sverki_Matrix.pdf` - PDF-рендер ТЗ для быстрого просмотра.
- `exports/AERO_TRADE_660-1_reference_style_export.xlsx` - пример Excel-выгрузки в бухгалтерском формате.
- `screenshots/docx/` - постраничный PNG-рендер ТЗ.
- `screenshots/excel/` - PNG-рендер листов Excel.
- `screenshots/ui/` - иллюстрации целевого интерфейса матрицы и экрана ошибок.

## Исходники для трассировки

- `tools/build_reconciliation_tz_docx.py` - генератор DOCX и иллюстраций.
- `tools/build_aero_trade_reference_export.mjs` - генератор XLSX-выгрузки.
- `reference/legacy_erp_to_1c_import_module.bsl` - фрагмент legacy 1C-сервиса, учтенный при описании требований.
- `ui/index.html` - текущий HTML-макет матрицы.

## Что проверять в аудите

- Маппинг ERP и 1C сущностей в разрезе поставки.
- Формулы колонок `Сумма оплаты`, `Возмещаемые расходы`, `Невозмещаемые расходы`, `(+/-)`.
- Логику статусов `Нет СФ/УПД`, `Вопрос по НДС`, `Есть остаток`, `Сумма расходится`, `Договор расходится`.
- Требования к журналированию расхождений для анализа частоты проблем по типам операций.
