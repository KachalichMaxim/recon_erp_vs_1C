# Проверка свежего фрагмента АЭРО-ТРЕЙД, договор 660/1

## Назначение

Документ фиксирует проверку нижнего свежего фрагмента бухгалтерского референса по договору `660/1`. Название листа `2020 договор 660/1` не используется как фильтр по году: проверены строки листа `10401-10450`, соответствующие поставкам ERP 2025 года.

Проверка нужна как контрольная модель для PHP-страницы ERP: экран и XLSX должны строиться из MariaDB и существующего обмена с 1С, а не из Google Sheets.

## Контекст ERP

| Объект | Значение |
|---|---|
| Клиент | `veda_contacts.f_id = 115`, АЭРО-ТРЕЙД |
| ЮЛ клиента | `veda_clients.f_id = 221`, `f_contactid = 115` |
| Договор | `veda_dogs.f_id = 88`, номер `660/1`, код 1С `БП-003453` |
| Проверенные заявки | `1063`, `1064`, `1065`, `1068`, `1072`, `1073`, `1074`, `1076` |
| Платежи | 8 распределений `veda_acchist_docs.f_clssum` |
| Счета покупателю | 25 основных счетов `veda_schets.f_type = 1` |

## Итоговые значения фрагмента

| Показатель | Значение |
|---|---:|
| Сумма по счетам, RUB-часть | `306 527,42` |
| Сумма оплат | `2 268 353,17` |
| Возмещаемые расходы | `2 152 392,27` |
| Невозмещаемые расходы | `196 372,85` |
| (+/-) | `-80 411,95` |

Формула блока поставки: `(+/-) = сумма оплат - возмещаемые расходы - невозмещаемые расходы`.

## Маппинг колонок

| Колонка | ERP-источник | Правило |
|---|---|---|
| Иерархия | `veda_contacts -> veda_clients -> veda_dogs -> veda_specs` | Клиент -> ЮЛ -> договор -> поставка. У клиента может быть несколько ЮЛ и договоров. |
| № спецификации | `veda_specs.f_num` | Номер заявки показывается в разрезе `spec_id`, не как глобальный уникальный ключ. |
| Счет | `veda_schets` | Показывать основные счета покупателю `f_type = 1`; дочерние строки через `f_maininv` используются для связи, но сумма основного счета не дублируется. |
| Сумма по счету | `veda_schets.f_sum`, `f_val` | Суммы разных валют не складывать в один денежный итог без утвержденного пересчета. |
| Сумма оплаты | `veda_acchist_docs.f_clssum` | Использовать распределенную сумму, а не полную сумму банковского документа. |
| Возмещаемые расходы | `veda_akts.f_operid` и `veda_akts_details_opers` | Брать прямые акты и распределенные строки актов по операциям с `f_isvozm = 1`. |
| Невозмещаемые расходы | `veda_akts.f_operid` | Для клиентской матрицы брать прямые клиентские акты по операциям с `f_isvozm = 2`. |
| Исключенные подрядные расходы | `veda_akts_details_opers` | Распределенные подрядные строки с `f_isvozm = 2` не включать в клиентский взаиморасчет; писать в журнал расхождений/ограничений. |
| № счф/УПД | `veda_akts`, SOAP 1С | Показывать все бухгалтерские номера документов через перенос строки; не заменять на `+N`. |
| Статус | серверный результат сверки | UI показывает код/бейдж, но не определяет причину по тексту операции. |

## Проверенные SQL-связи

Иерархия:

```sql
SELECT c.f_id contact_id, cl.f_id legal_id, d.f_id dog_id, d.f_dogname, d.f_kod1c
FROM veda_contacts c
JOIN veda_clients cl ON cl.f_contactid = c.f_id
JOIN veda_dogs d ON d.f_contrid = cl.f_id
WHERE d.f_id = 88;
```

Операции поставки:

```sql
SELECT si.*
FROM veda_spec_invoices si
LEFT JOIN veda_categs cg
       ON cg.f_objectid = si.f_id
      AND cg.f_ctgtype = 24
      AND cg.f_objecttype = 5
WHERE (si.f_parenttype = 2 AND si.f_specid = :spec_id)
   OR (si.f_parenttype = 4 AND CAST(cg.f_valstr AS SIGNED) = :spec_id);
```

Счета покупателю:

```sql
SELECT main.f_id, main.f_num, main.f_sum, main.f_val
FROM veda_schets child
JOIN veda_schets main
  ON main.f_id = IF(child.f_maininv > 0, child.f_maininv, child.f_id)
WHERE child.f_operid IN (:operation_ids)
  AND main.f_type = 1;
```

Оплаты:

```sql
SELECT ahd.f_docid operation_id, SUM(ahd.f_clssum) classified_sum
FROM veda_acchist_docs ahd
JOIN veda_acchist ah ON ah.f_id = ahd.f_achid
WHERE ahd.f_doctype = 3
  AND ahd.f_docid IN (:operation_ids)
  AND ah.f_type = 0
GROUP BY ahd.f_docid;
```

Расходы для клиентской матрицы:

```sql
-- Возмещаемые: прямые акты + распределенные строки актов.
SELECT s.f_num spec_num, SUM(src.amount) reimbursable_amount
FROM (
  SELECT si.f_specid, a.f_sum amount
  FROM veda_spec_invoices si
  JOIN veda_akts a ON a.f_operid = si.f_id
  WHERE si.f_isvozm = 1
  UNION ALL
  SELECT si.f_specid, ado.f_sum amount
  FROM veda_spec_invoices si
  JOIN veda_akts_details_opers ado ON ado.f_operid = si.f_id
  WHERE si.f_isvozm = 1
) src
JOIN veda_specs s ON s.f_id = src.f_specid
GROUP BY s.f_num;

-- Невозмещаемые клиентской матрицы: только прямые клиентские акты.
SELECT s.f_num spec_num, SUM(a.f_sum) non_reimbursable_amount
FROM veda_spec_invoices si
JOIN veda_specs s ON s.f_id = si.f_specid
JOIN veda_akts a ON a.f_operid = si.f_id
WHERE si.f_isvozm = 2
GROUP BY s.f_num;
```

Подрядные распределенные строки `veda_akts_details_opers` с `f_isvozm = 2` сохраняются для анализа, но не увеличивают клиентскую колонку `Невозмещаемые расходы`.

## Примеры по свежему фрагменту

| Заявка | Оплата `f_clssum` | Возмещаемые | Невозмещаемые | Исключенные подрядные | (+/-) |
|---:|---:|---:|---:|---:|---:|
| 1063 | `174 585,57` | `185 181,88` | `14 900,65` | `1 980,91` | `-25 496,96` |
| 1064 | `117 446,61` | `124 577,67` | `10 027,64` | `1 333,16` | `-17 158,70` |
| 1065 | `149 336,16` | `158 354,45` | `12 681,23` | `1 685,93` | `-21 699,52` |
| 1068 | `442 296,92` | `416 239,47` | `36 640,04` | `5 000,00` | `-10 582,59` |
| 1072 | `319 193,45` | `287 869,95` | `26 049,03` | `3 543,41` | `5 274,47` |
| 1073 | `131 241,57` | `118 363,63` | `10 708,61` | `1 456,59` | `2 169,33` |
| 1074 | `442 296,92` | `416 239,47` | `36 640,04` | `5 000,00` | `-10 582,59` |
| 1076 | `491 955,97` | `445 565,75` | `48 725,61` | `5 000,00` | `-2 335,39` |

Для 1063 доп. отчет `3 091,01` подтвержден в ERP как `veda_akts_details_opers.f_sum` по операции `378519`, акт `14837`, строка акта `184531`. Аналогично 1064/1065 используют тот же акт с распределениями `2 080,27` и `2 630,72`.

## Ограничения

1. Внешние строки вида `по письму` отсутствуют в ERP и не должны автоматически появляться в промышленной матрице.
2. `veda_corrects.f_num > 0` показывает наличие среза/корректировки, но по проверенным поставкам `veda_corrects_opers` пустой. Нельзя строить матрицу только по `corrects_opers`.
3. `get_paidsum/get_expensessum` дают иной отчетный контур; PHP-разработчик должен повторить подтвержденную логику матрицы, а не просто вызвать одну старую функцию.
4. `f_isvozm = 2` само по себе недостаточно для включения суммы в клиентскую колонку: важен источник суммы. Прямой клиентский акт включается, подрядная распределенная строка логируется отдельно.
5. Статус `Нет СФ/УПД` определяется только по отсутствию закрывающего документа после успешного запроса к источникам. `Вопрос по НДС` определяется только сравнением ставок НДС ERP и 1С.

## Артефакты проверки

- JSON-референс свежего фрагмента: `reference/aero_trade_660_1_fresh_reference.json`.
- Live-audit MariaDB: `reference/aero_trade_660_1_fresh_mariadb_audit.json`.
- UI-fixture: `ui/reference/aero_trade_660_1_fresh.json`.
- XLSX-выгрузка: `exports/AERO_TRADE_660-1_fresh_reference_export.xlsx`.
- Скрин матрицы: `screenshots/ui/matrix_aero_trade_660_1_fresh_fixture.png`.
- Скрины XLSX: `screenshots/excel/aero_trade_660_1_fresh_export.png`, `screenshots/excel/aero_trade_660_1_fresh_rules.png`.
