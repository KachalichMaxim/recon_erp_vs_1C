# Алгоритм матрицы акта сверки ERP ↔ 1С

Этот документ фиксирует developer-level правила, которые должны быть реализованы в текущей PHP ERP. Источник production-расчета: MariaDB ERP + существующий PHP/SOAP-обмен с 1С. Внешние таблицы и аналитические XLSX не участвуют в production-логике.

## Артефакты

- Финальное ТЗ: `docs/TZ_ERP_1C_Akt_Sverki_Matrix_LIVE_MARIADB_2026-06-24.docx`.
- PDF для просмотра: `docs/TZ_ERP_1C_Akt_Sverki_Matrix_LIVE_MARIADB_2026-06-24.pdf`.
- Проверочная XLSX-выгрузка: `exports/AERO_TRADE_660-1_live_mariadb_reconciliation_20260624.xlsx`.
- Машинный протокол live-проверки: `reference/aero_trade_660_1_live_mariadb_report_20260624.json`.

## 1. Базовая иерархия

Матрица строится деревом:

1. Клиент: `veda_contacts`.
2. ЮЛ клиента: `veda_clients`, связь `veda_clients.f_contactid = veda_contacts.f_id`.
3. Договор: `veda_dogs`, связь `veda_dogs.f_contrid = veda_clients.f_id`.
4. Поставка / спецификация: `veda_specs`, связь `veda_specs.f_dogid = veda_dogs.f_id`.
5. Документы внутри поставки: счета, оплаты, акты, СФ/УПД.

На уровнях клиент / ЮЛ / договор показываются только агрегаты дочерних поставок. Документы раскрываются только внутри поставки.

```sql
SELECT
    c.f_id  AS contact_id,
    cl.f_id AS legal_id,
    d.f_id  AS dog_id,
    s.f_id  AS spec_id,
    s.f_num AS spec_num
FROM veda_contacts c
JOIN veda_clients cl ON cl.f_contactid = c.f_id
JOIN veda_dogs d     ON d.f_contrid = cl.f_id
JOIN veda_specs s    ON s.f_dogid = d.f_id
WHERE c.f_id = :contact_id
  AND (:legal_id IS NULL OR cl.f_id = :legal_id)
  AND (:dog_id IS NULL OR d.f_id = :dog_id);
```

## 2. Операции поставки

В поставку входят операции, привязанные напрямую к спецификации, и операции, привязанные через `f_parenttype=4`.

```sql
SELECT
    si.f_id,
    si.f_specid,
    si.f_parenttype,
    si.f_isvozm,
    si.f_nds,
    si.f_name
FROM veda_spec_invoices si
LEFT JOIN veda_categs cg
       ON cg.f_objectid = si.f_id
      AND cg.f_ctgtype = 24
      AND cg.f_objecttype = 5
WHERE (si.f_parenttype = 2 AND si.f_specid = :spec_id)
   OR (si.f_parenttype = 4 AND CAST(cg.f_valstr AS SIGNED) = :spec_id);
```

Для каждой операции нужно получить существующие ERP-функции/процедуры:

- `get_paidsum(operation_id)` — распределенная оплата операции;
- `get_realizsum(operation_id)` — сумма реализации по операции;
- `get_expensessum(operation_id)` — расходы по операции для диагностики;
- `get_profit(operation_id)` — прибыль/разница для диагностики.

Правила классификации:

- `veda_spec_invoices.f_isvozm = 1` — возмещаемые расходы;
- `veda_spec_invoices.f_isvozm = 2` — невозмещаемые операции;
- подрядные расходы без клиентской реализации не подтягиваются в взаиморасчет клиента и логируются отдельно.

## 3. Счета покупателю

В колонку `Счет` и `Сумма по счету` попадают только счета покупателю.

```sql
SELECT DISTINCT
    main.f_id,
    main.f_num,
    main.f_dt,
    main.f_sum,
    main.f_val,
    main.f_kod1c
FROM veda_schets child
JOIN veda_schets main
  ON main.f_id = IF(child.f_maininv > 0, child.f_maininv, child.f_id)
WHERE child.f_operid IN (:operation_ids)
  AND main.f_type = 1;
```

Правила:

- `veda_schets.f_type = 1` — счет покупателю;
- `veda_schets.f_type = 2` — счет от поставщика, в матрицу взаиморасчетов с клиентом не попадает;
- если есть дочерние строки счета, в XLSX показывается основной счет один раз.

## 4. Оплаты

Сумма оплаты поставки берется не из общей суммы банковского документа, а из распределенной суммы строки классификации.

```sql
SELECT
    ahd.f_docid AS operation_id,
    SUM(ahd.f_clssum) AS paid_sum
FROM veda_acchist_docs ahd
JOIN veda_acchist ah ON ah.f_id = ahd.f_acchistid
WHERE ahd.f_doctype = 3
  AND ahd.f_docid IN (:operation_ids)
  AND ah.f_type = 0
GROUP BY ahd.f_docid;
```

Контроль:

- `paid_total_acchist_docs = SUM(veda_acchist_docs.f_clssum)`;
- `paid_total_get_paidsum = SUM(get_paidsum(operation_id))`;
- если суммы отличаются больше чем на 0,01, ставится статус `PAYMENT_ROUTINE_VS_DOCS_MISMATCH`.

## 5. Возмещаемые и невозмещаемые расходы

Суммы колонок строятся по `get_realizsum`, а не по платежам и не по общей сумме актов.

```text
Возмещаемые расходы     = SUM(get_realizsum(operation_id) WHERE f_isvozm = 1)
Невозмещаемые расходы   = SUM(get_realizsum(operation_id) WHERE f_isvozm = 2)
Реализация поставки     = Возмещаемые + Невозмещаемые + прочая клиентская реализация
```

Если строка в 1С является нашей себестоимостью по подрядчику, а не клиентской реализацией, договор клиента не добавляется искусственно. Такая строка может подтверждать расход, но не должна менять сальдо клиента.

## 6. СФ/УПД и закрывающие документы

В колонку `№ счф` выводятся все закрывающие документы поставки. Нельзя заменять список счетчиком количества документов.

Формат одной строки внутри объединенной ячейки:

```text
{код 1С или номер} от {дата} ({сумма})
```

Если документов несколько, они выводятся через Enter внутри одной объединенной ячейки.

Статус `Нет СФ/УПД` ставится только после загрузки документов из ERP и 1С, если закрывающий документ действительно отсутствует. Статус `Вопрос по НДС` проверяет только расхождение ставки НДС ERP и 1С, а не возмещаемость.

## 7. Формула сальдо

Колонка `(+/-)` — это дельта между распределенными поступлениями и реализацией:

```text
(+/-) = SUM(get_paidsum) - SUM(get_realizsum)
```

Сумма счетов покупателю сверяется отдельно и не входит в формулу `(+/-)`. Поэтому ситуация “оплата 25 000, счет 5 000, дельта 0” недопустима, если реализация по поставке не равна оплате.

## 8. Структура блока поставки для backend

PHP backend должен собирать один нормализованный массив. Из него строятся и экран, и XLSX.

```php
$block = [
    'contact' => ['id' => ..., 'name' => ..., 'inn' => ...],
    'legal'   => ['id' => ..., 'name' => ..., 'inn' => ...],
    'dog'     => ['id' => ..., 'num' => ..., 'code1c' => ...],
    'spec'    => ['id' => ..., 'num' => ..., 'date' => ..., 'code1c' => ...],

    'customer_invoices' => [
        ['number' => ..., 'date' => ..., 'sum' => ..., 'currency' => ..., 'code1c' => ...],
    ],

    'closing_docs' => [
        ['number' => ..., 'date' => ..., 'sum' => ..., 'code1c' => ..., 'nds_rate' => ...],
    ],

    'totals' => [
        'invoice_sum' => ...,
        'paid_total_get_paidsum' => ...,
        'paid_total_acchist_docs' => ...,
        'reimbursable_realization_get_realizsum' => ...,
        'non_reimbursable_realization_get_realizsum' => ...,
        'realization_total_get_realizsum' => ...,
        'settlement_delta_paid_minus_realization' => ...,
    ],

    'statuses' => [...],
];
```

## 9. Раскладка XLSX

XLSX должен быть бухгалтерской формой, а не копией экранной матрицы.

| Колонка | Что кладем | Формат |
| --- | --- | --- |
| `№ спецификации` | `Спецификация {veda_specs.f_num}` | объединить по числу строк счетов |
| `Счет` | каждый счет покупателю отдельной строкой | не объединять |
| `Сумма по счету` | сумма соответствующего счета + валюта | не суммировать разные валюты без курса |
| `Сумма оплаты` | `paid_total_get_paidsum` | объединить по поставке |
| `Возмещаемые расходы` | `reimbursable_realization_get_realizsum` | объединить по поставке |
| `Невозмещаемые расходы` | `non_reimbursable_realization_get_realizsum` | объединить по поставке |
| `№ счф` | все `closing_docs`, каждая строка через Enter | объединить по поставке, включить wrap text |
| `(+/-)` | `settlement_delta_paid_minus_realization` | объединить по поставке, выделить цветом при ненуле |

Итоговая строка называется `ИТОГО`; в ней не должно быть номеров строк внешних таблиц.

## 10. Статусы и логирование

Минимальный набор статусов:

- `OK`;
- `Нет в 1С`;
- `Нет в ERP`;
- `Сумма расходится`;
- `Нет СФ/УПД`;
- `Вопрос по НДС`;
- `Есть остаток`;
- `Договор расходится`;
- `Недостаточно данных`;
- `PAYMENT_ROUTINE_VS_DOCS_MISMATCH`;
- `EXCLUDED_CONTRACTOR_EXPENSE`.

Каждый запуск сверки должен писать журнал расхождений: клиент, ЮЛ, договор, поставка, операция, тип документа, источник ERP/1С, код ошибки, суммы ERP/1С, дельта, ставка НДС ERP/1С, дата проверки. Это нужно для последующего анализа частоты и природы проблем.

## 11. Live-контроль 660/1

Контрольный live-набор собран серверным SQL-скриптом с MariaDB ERP по договору 660/1.

Итоги:

| Показатель | Сумма |
| --- | ---: |
| `SUM(get_paidsum)` | 2 331 111,33 |
| `SUM(veda_acchist_docs.f_clssum)` | 2 331 111,33 |
| `SUM(get_realizsum)` | 2 415 525,73 |
| Возмещаемые через `get_realizsum` | 2 152 392,27 |
| Невозмещаемые через `get_realizsum` | 263 133,46 |
| `(+/-)` | -84 414,40 |

Эти цифры являются проверкой SQL-связей и формул. Production PHP должен воспроизводить тот же принцип расчета на выбранных пользователем клиентах, договорах и поставках.
