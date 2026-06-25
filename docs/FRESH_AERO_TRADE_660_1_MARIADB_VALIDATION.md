# Live-проверка MariaDB: АЭРО-ТРЕЙД, договор 660/1

## Назначение

Документ фиксирует фактическую проверку расчетов матрицы сверки на действующей MariaDB ERP. Проверка выполнена с сервера `86.110.194.109`, то есть через тот же сетевой контур, где работает сервис `/var/www/print/reconciliation_api_server.py`.

Это не внешний аналитический источник. Все суммы ниже получены запросами к `veda25` и процедурами ERP.

## Как запускалась проверка

На сервере был восстановлен штатный VPN-маршрут до ERP:

```bash
systemctl restart elt-vpn.service
```

После этого `erp.vedagent` резолвился как `10.54.1.28`, появился маршрут через `ppp0`, и SQL-запросы к `veda25` начали выполняться.

Проверочный скрипт:

```bash
/var/www/print/server_mariadb_reconciliation_report.py \
  --out-dir /var/www/print/akt_sverki/live_validation \
  --label aero_trade_660_1_live_20260624
```

Скрипт импортирует настройки подключения из серверного `reconciliation_api_server.py`. Секрет подключения не копируется в репозиторий и не выводится в артефакты.

## Контекст ERP

| Объект | Значение |
|---|---|
| Клиент | `veda_contacts.f_id = 115`, АЭРО-ТРЕЙД |
| ЮЛ клиента | `veda_clients.f_id = 221`, `f_contactid = 115`, ИНН `7811451960` |
| Договор | `veda_dogs.f_id = 88`, номер `660/1`, код 1С `БП-003453` |
| Проверенные поставки | `1063`, `1064`, `1065`, `1068`, `1072`, `1073`, `1074`, `1076` |
| Период поставок | май 2025 |
| Проверка оплат | `get_paidsum(oper.f_id)` сверен с `SUM(veda_acchist_docs.f_clssum)` |
| Проверка реализации | `get_realizsum(oper.f_id)` в разрезе `veda_spec_invoices.f_isvozm` |

## Итоги live-прогона

| Показатель | Значение |
|---|---:|
| Сумма по счетам покупателю | `288 812,42` |
| Сумма оплат, `get_paidsum` | `2 331 111,33` |
| Сумма оплат, `veda_acchist_docs.f_clssum` | `2 331 111,33` |
| Возмещаемые расходы, `get_realizsum` при `f_isvozm = 1` | `2 152 392,27` |
| Невозмещаемые расходы, `get_realizsum` при `f_isvozm = 2` | `263 133,46` |
| Реализация всего, `get_realizsum` | `2 415 525,73` |
| `(+/-) = get_paidsum - get_realizsum` | `-84 414,40` |

По всем 8 поставкам `get_paidsum` совпал с распределениями `veda_acchist_docs.f_clssum`. По всем 8 поставкам есть ненулевая дельта `SETTLEMENT_BALANCE_NONZERO`; это не ошибка SQL, а расчетный долг или переплата.

## Что сверяется

| Колонка матрицы | ERP-источник | Правило |
|---|---|---|
| Иерархия | `veda_contacts -> veda_clients -> veda_dogs -> veda_specs` | Клиент -> ЮЛ -> договор -> поставка. Один клиент может иметь несколько ЮЛ и договоров. |
| № спецификации | `veda_specs.f_num` | Показывать номер поставки/заявки в рамках договора. |
| Счет | `veda_schets` | Показывать основные счета покупателю по поставке. Строки агрегирующего счета и счета поставщиков не суммировать повторно в клиентскую колонку. |
| Сумма по счету | `veda_schets.f_sum`, `veda_schets.f_val` | Валюту показывать рядом с суммой. Разные валюты не складывать в один рублевый итог без отдельного утвержденного курса. |
| Сумма оплаты | `get_paidsum(oper.f_id)` и контроль `veda_acchist_docs.f_clssum` | Использовать распределенную сумму по операции, а не полную сумму банковского документа. |
| Возмещаемые расходы | `get_realizsum(oper.f_id)`, `veda_spec_invoices.f_isvozm = 1` | Это клиентская реализация/отчет агента по возмещаемым операциям. |
| Невозмещаемые расходы | `get_realizsum(oper.f_id)`, `veda_spec_invoices.f_isvozm = 2` | Это клиентская реализация/отчет агента по невозмещаемым операциям. Не путать с подрядными расходами агента. |
| № счф/УПД | `veda_akts` через `fetch_erp_docs` серверного API | Показывать все закрывающие документы через перенос строки внутри объединенной ячейки, без `+N документов`. |
| (+/-) | `SUM(get_paidsum) - SUM(get_realizsum)` | Сальдо взаиморасчетов по поставке. |
| Статус | журнал сверки ERP/1С | Статус строится из кодов расхождений, а не из текста операции. |

## Проверенные SQL-связи

Иерархия:

```sql
SELECT c.f_id contact_id, cl.f_id legal_id, d.f_id dog_id, d.f_dogname, d.f_kod1c
FROM veda_contacts c
JOIN veda_clients cl ON cl.f_contactid = c.f_id
JOIN veda_dogs d ON d.f_contrid = cl.f_id
WHERE c.f_id = 115
  AND cl.f_id = 221
  AND d.f_id = 88;
```

Поставки контрольного набора:

```sql
SELECT s.f_id, s.f_num, s.f_dt, s.f_status, s.f_tovar
FROM veda_specs s
WHERE s.f_dogid = 88
  AND CAST(s.f_num AS CHAR) IN ('1063','1064','1065','1068','1072','1073','1074','1076');
```

Операции поставки:

```sql
SELECT oper.f_id
FROM veda_spec_invoices oper
LEFT JOIN veda_categs oper4_specs
       ON oper4_specs.f_objectid = oper.f_id
      AND oper4_specs.f_ctgtype = 24
      AND oper4_specs.f_objecttype = 5
WHERE oper.f_parenttype IN (2, 4)
  AND CASE oper.f_parenttype
        WHEN 2 THEN oper.f_specid
        WHEN 4 THEN CAST(oper4_specs.f_valstr AS SIGNED)
      END = :spec_id;
```

Оплаты:

```sql
SELECT ahd.f_docid operation_id, SUM(ahd.f_clssum) classified_sum
FROM veda_acchist_docs ahd
JOIN veda_acchist ah ON ah.f_id = ahd.f_acchistid
WHERE ahd.f_doctype = 3
  AND ahd.f_docid IN (:operation_ids)
  AND ah.f_type = 0
GROUP BY ahd.f_docid;
```

Реализация:

```sql
SELECT
  si.f_id operation_id,
  si.f_isvozm,
  get_paidsum(si.f_id) paid_sum,
  get_realizsum(si.f_id) realization_sum,
  get_expensessum(si.f_id) expenses_sum,
  get_profit(si.f_id) profit_sum
FROM veda_spec_invoices si
WHERE si.f_id IN (:operation_ids);
```

Счета покупателю:

```sql
-- Практическое правило матрицы: основной счет покупателю по поставке,
-- без повторного суммирования строк агрегирующего счета.
SELECT s.f_id, s.f_num, main.f_id invoice_id, main.f_num invoice_num, main.f_sum, main.f_val
FROM veda_specs s
JOIN veda_schets main
  ON main.f_dogtype = 2
 AND main.f_dogid = s.f_id
 AND main.f_ismaininv = 1
WHERE s.f_id = :spec_id;
```

## Разрез по поставкам

| Поставка | Счета покупателю | Оплата | Возмещаемые | Невозмещаемые | Реализация всего | (+/-) |
|---:|---:|---:|---:|---:|---:|---:|
| 1063 | `23 988,63` | `199 375,04` | `185 181,88` | `41 275,82` | `226 457,70` | `-27 082,66` |
| 1064 | `16 135,94` | `134 234,42` | `124 577,67` | `27 882,63` | `152 460,30` | `-18 225,88` |
| 1065 | `21 191,85` | `170 517,04` | `158 354,45` | `35 211,68` | `193 566,13` | `-23 049,09` |
| 1068 | `56 700,00` | `442 296,92` | `416 239,47` | `36 640,04` | `452 879,51` | `-10 582,59` |
| 1072 | `40 251,00` | `319 193,45` | `287 869,95` | `26 049,03` | `313 918,98` | `5 274,47` |
| 1073 | `16 547,00` | `131 241,57` | `118 363,63` | `10 708,61` | `129 072,24` | `2 169,33` |
| 1074 | `56 700,00` | `442 296,92` | `416 239,47` | `36 640,04` | `452 879,51` | `-10 582,59` |
| 1076 | `57 298,00` | `491 955,97` | `445 565,75` | `48 725,61` | `494 291,36` | `-2 335,39` |
| **ИТОГО** | `288 812,42` | `2 331 111,33` | `2 152 392,27` | `263 133,46` | `2 415 525,73` | `-84 414,40` |

## Ограничения

1. Проверка подтверждает ERP/MariaDB-часть расчета. Полное сравнение с 1С требует отдельного успешного SOAP-запроса и нормализации документов 1С.
2. Статус `Нет СФ/УПД` можно ставить только после успешного запроса к источникам, когда ожидаемый закрывающий документ ERP не найден в 1С.
3. Статус `Вопрос по НДС` можно ставить только при расхождении ставки/суммы НДС ERP и 1С. `f_isvozm` сам по себе не является НДС-ошибкой.
4. Внешние письма и ручные корректировки, которых нет в ERP, не должны автоматически попадать в матрицу.
5. Для поставок с корректировками основной отчет и корректировки должны обрабатываться как единый набор операций. Нельзя строить сверку только по `veda_corrects_opers`, потому что основной отчет хранится отдельно.
6. Серверный доступ к MariaDB зависит от VPN `elt-vpn.service`; если `erp.vedagent` не резолвится или нет маршрута через `ppp0`, live-проверка невозможна.

## Артефакты

| Артефакт | Назначение |
|---|---|
| `tools/server_mariadb_reconciliation_report.py` | Серверный скрипт проверки MariaDB и сборки отчета. |
| `reference/aero_trade_660_1_live_mariadb_report_20260624.json` | Машинный протокол live-прогона с расшифровками операций, счетов, оплат и закрывающих документов. |
| `exports/AERO_TRADE_660-1_live_mariadb_reconciliation_20260624.xlsx` | Бухгалтерская XLSX-выгрузка в форме `№ спецификации / Счет / Оплата / Расходы / № счф / (+/-)`. |
| `/var/www/print/akt_sverki/live_validation/aero_trade_660_1_live_20260624.*` | Серверные оригиналы JSON/CSV/XLSX. |
