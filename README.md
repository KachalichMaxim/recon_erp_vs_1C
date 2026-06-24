# ERP vs 1C reconciliation audit package

Пакет для аудита ТЗ по матрице акта сверки ERP и 1С.

## Основные артефакты

- `docs/TZ_ERP_1C_Akt_Sverki_Matrix.docx` - итоговое ТЗ.
- `docs/TZ_ERP_1C_Akt_Sverki_Matrix.pdf` - PDF-рендер ТЗ для быстрого просмотра.
- `exports/AERO_TRADE_660-1_reference_style_export.xlsx` - пример Excel-выгрузки в бухгалтерском формате.
- `screenshots/docx/` - постраничный PNG-рендер ТЗ.
- `screenshots/excel/` - PNG-рендер листов Excel.
- `screenshots/ui/` - иллюстрации целевого интерфейса матрицы и экрана ошибок.

## Проверка перед подготовкой финального ТЗ

Перед передачей задачи PHP-разработчику необходимо проверить бизнес-логику на действующих ERP MariaDB и 1С:

- `docs/REQUIREMENTS_VALIDATION_BEFORE_TZ.md` — подтвержденные правила, требования к матрице, статусам, XLSX и перечень решений, которые должны войти в финальное ТЗ;
- `docs/VALIDATION_PROTOCOL_ERP_1C.md` — протокол проверки связей, формул и SOAP-ответов на контрольных поставках.
- `docs/AUTOMATED_MATRIX_REFERENCE_TESTS.md` — автоматические тесты референсной логики интерфейса, которые запускаются без MariaDB и 1С.

Промышленный контур первой версии: PHP ERP + MariaDB + существующий PHP/SOAP-слой 1С. Python, PostgreSQL, Google Drive и аналитические XLSX остаются тестовыми референсами бизнес-аналитика и не являются обязательными компонентами промышленной реализации.

Зафиксированные правила:

- `veda_spec_invoices.f_isvozm = 1` — возмещаемые расходы;
- `veda_spec_invoices.f_isvozm = 2` — невозмещаемые расходы;
- `veda_schets.f_type = 1` — счет покупателю;
- `veda_schets.f_type = 2` — счет от поставщика;
- объединенные ячейки XLSX сохраняются как требование бухгалтерии, а копирование формирует плоский TSV с повторением значений.

## Автоматические тесты

```bash
npm test
```

Тесты проверяют логику матрицы на синтетическом контрольном кейсе: `f_isvozm`, `f_type`, `f_clssum`, агрегирующие счета, `f_parenttype=4`, СФ/УПД, НДС, смешанные валюты, TSV-копирование и XLSX-раскладку. Они не заменяют проверку на живой MariaDB и 1С, но фиксируют правила, которые должен сохранять PHP-интерфейс.

## Исходники для трассировки

- `tools/build_reconciliation_tz_docx.py` - генератор DOCX и иллюстраций.
- `tools/build_aero_trade_reference_export.mjs` - генератор XLSX-выгрузки.
- `reference/legacy_erp_to_1c_import_module.bsl` - фрагмент legacy 1C-сервиса, учтенный при описании требований.
- `ui/index.html` - текущий HTML-макет матрицы.
- `php_legacy/lib_1c_soap_layer.php` - legacy PHP-слой вызовов SOAP 1C (`c1c_getAkts`, `c1c_getAccHist`, `c1c_getcoacsu`, `c1c_getcoacsuinfo1C` и др.).
- `php_legacy/rowsLib.php`, `php_legacy/class.php`, `php_legacy/printShablonFuntions.php` - ERP PHP-код с текущими SQL-связями поставок, счетов, актов, оплат и отчетных форм.
- `api/reconciliation_api_server.redacted.py` - текущий прототип API матрицы сверки с редактированными параметрами подключения.
- `sql/1c_reconciliation_schema.sql` - схема логирования запусков и результатов сверки.
- `sql/PowerBI_master_spec_invoice_matrix.sql` - SQL-референс по матрице поставка/счет/операция.

## Что проверять в аудите

- Маппинг ERP и 1C сущностей в разрезе поставки.
- Формулы колонок `Сумма оплаты`, `Возмещаемые расходы`, `Невозмещаемые расходы`, `(+/-)`.
- Логику статусов `Нет СФ/УПД`, `Вопрос по НДС`, `Есть остаток`, `Сумма расходится`, `Договор расходится`.
- Требования к журналированию расхождений для анализа частоты проблем по типам операций.
