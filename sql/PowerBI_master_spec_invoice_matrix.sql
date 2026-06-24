/* 
  Master query for Power BI matrix:
  Specification -> income operation -> invoice -> payment status.

  Important:
  - Base grain starts from view_pd_oved specifications.
  - Income operations are LEFT JOINed from specifications.
  - Invoices are LEFT JOINed from operations, so operations without invoices are preserved.
  - Client invoices are limited to veda_schets.f_type = 1.
  - Do not add ORDER BY here. Sort in Power BI.
*/

WITH
specs AS (
    SELECT
        pd.spec_id,
        pd.spec_num,
        pd.spec_url,
        pd.spec_client_name,
        pd.spec_manager,
        pd.manager_department,
        pd.spec_status_groupes,
        pd.f_postid AS spec_postid,
        pd.custom_route_arrive_min_date,
        pd.spec_date
    FROM view_pd_oved pd
    WHERE pd.spec_id > 0
      /* Adjust/remove period filter for production refresh */
      AND pd.spec_date >= '2026-01-01'
      AND pd.spec_date <  '2027-01-01'
),
route_dates AS (
    SELECT
        sp.spec_id,
        MIN(CASE
            WHEN r.f_p2dt IS NOT NULL AND r.f_p2dt <> '0000-00-00 00:00:00' AND r.f_p2iscustom = 1
                THEN DATE(r.f_p2dt)
            ELSE NULL
        END) AS route_border_arrival_date,
        MIN(CASE
            WHEN r.f_p2dt IS NOT NULL AND r.f_p2dt <> '0000-00-00 00:00:00' AND r.f_routetype = 1
                THEN DATE(r.f_p2dt)
            ELSE NULL
        END) AS route_port_arrival_date,
        MIN(CASE
            WHEN r.f_p2dt IS NOT NULL AND r.f_p2dt <> '0000-00-00 00:00:00'
                THEN DATE(r.f_p2dt)
            ELSE NULL
        END) AS route_any_arrival_date
    FROM specs sp
    LEFT JOIN veda_routes r
      ON (sp.spec_postid > 0 AND r.f_postid = sp.spec_postid)
      OR r.f_specid = sp.spec_id
    GROUP BY sp.spec_id
),
income_ops AS (
    SELECT
        si.f_id AS oper_id,
        si.f_specid AS spec_id,
        si.f_parentid AS parent_oper_id,
        si.f_idoper,
        t.f_name AS operation_name,
        si.f_bdrarticle,
        s86.f_name AS bdrarticle_name,
        CONCAT(si.f_idoper, '|', si.f_bdrarticle) AS operation_group_key,
        CASE
            WHEN si.f_idoper IN (1,2,53,54,93,320,355,374,375,376,377,385,386,387,393,447,448,522)
              OR LOWER(CONCAT_WS(' ', t.f_name, s86.f_name)) REGEXP 'товар|груз|инвойс|invoice'
                THEN 'Товары'
            WHEN LOWER(CONCAT_WS(' ', t.f_name, s86.f_name)) REGEXP 'тамож|деклар'
                THEN 'Услуги таможни'
            WHEN LOWER(CONCAT_WS(' ', t.f_name, s86.f_name)) REGEXP 'логист|перевоз|экспед|достав|фрахт|транспорт|контейн|свх|хранен|тамож|деклар|страх|погруз|разгруз|жд|авто'
                THEN 'Логистика'
            ELSE 'Прочее'
        END AS operation_calc_auto,
        CASE
            WHEN si.f_idoper IN (1,2,53,54,93,320,355,374,375,376,377,385,386,387,393,447,448,522)
              OR LOWER(CONCAT_WS(' ', t.f_name, s86.f_name)) REGEXP 'товар|груз|инвойс|invoice'
                THEN 0
            ELSE 1
        END AS include_in_control_auto,
        si.f_sum AS operation_sum,
        si.f_val AS operation_val,
        si.f_status AS operation_status_id,
        si.f_addnds AS operation_addnds,
        IFNULL(si.f_wloans, 0) AS operation_financing_flag,
        CASE WHEN IFNULL(si.f_wloans, 0) = 1 THEN 'Финансируемая' ELSE 'Не финансируемая' END AS operation_financing_status,
        si.f_dttmcr AS operation_created_at,
        CASE
            WHEN si.f_dttmsrok IS NULL OR si.f_dttmsrok = '0000-00-00 00:00:00' THEN NULL
            ELSE si.f_dttmsrok
        END AS operation_due_datetime,
        si.f_com AS operation_billing_rule_comment,
        si.f_invcom AS operation_invoice_comment
    FROM specs sp
    JOIN veda_spec_invoices si ON si.f_specid = sp.spec_id
    JOIN veda_typeopers t ON t.f_id = si.f_idoper
    JOIN veda_spr s86
      ON s86.f_type = 86
     AND s86.f_num = si.f_bdrarticle
     AND s86.f_dopprint = 1
     AND s86.f_isuse = 1
    JOIN veda_spr s85 ON s85.f_type = 85 AND s85.f_num = s86.f_uslint
    JOIN veda_spr s84 ON s84.f_type = 84 AND s84.f_num = s85.f_dopprint AND s84.f_num = 1
),
invoice_operation_links_raw AS (
    SELECT
        io.oper_id,
        s.f_id AS schet_id,
        s.f_id AS linked_schet_id,
        'direct' AS link_type,
        NULL AS link_operation_sum,
        s.f_sum AS link_schet_sum,
        s.f_val AS link_schet_val,
        s.f_dt AS link_schet_date
    FROM income_ops io
    JOIN veda_schets s ON s.f_type = 1 AND s.f_operid = io.oper_id AND IFNULL(s.f_maininv, 0) = 0

    UNION

    SELECT
        io.oper_id,
        child.f_maininv AS schet_id,
        child.f_id AS linked_schet_id,
        'maininv_child' AS link_type,
        NULL AS link_operation_sum,
        child.f_sum AS link_schet_sum,
        child.f_val AS link_schet_val,
        child.f_dt AS link_schet_date
    FROM income_ops io
    JOIN veda_schets child ON child.f_operid = io.oper_id AND child.f_maininv > 0
    JOIN veda_schets parent ON parent.f_id = child.f_maininv AND parent.f_type = 1

    UNION

    SELECT
        io.oper_id,
        sd.f_schetid AS schet_id,
        sd.f_schetid AS linked_schet_id,
        'details' AS link_type,
        sdo.f_sum AS link_operation_sum,
        CASE WHEN IFNULL(sdo.f_sum, 0) <> 0 THEN sdo.f_sum ELSE sd.f_sum END AS link_schet_sum,
        sd.f_val AS link_schet_val,
        s.f_dt AS link_schet_date
    FROM income_ops io
    JOIN veda_schets_details_opers sdo ON sdo.f_operid = io.oper_id
    JOIN veda_schets_details sd ON sd.f_id = sdo.f_schets_detailsid
    JOIN veda_schets s ON s.f_id = sd.f_schetid AND s.f_type = 1 AND IFNULL(s.f_maininv, 0) = 0

    UNION

    SELECT
        io.oper_id,
        child.f_maininv AS schet_id,
        child.f_id AS linked_schet_id,
        'details_maininv_child' AS link_type,
        sdo.f_sum AS link_operation_sum,
        CASE WHEN IFNULL(sdo.f_sum, 0) <> 0 THEN sdo.f_sum ELSE sd.f_sum END AS link_schet_sum,
        sd.f_val AS link_schet_val,
        child.f_dt AS link_schet_date
    FROM income_ops io
    JOIN veda_schets_details_opers sdo ON sdo.f_operid = io.oper_id
    JOIN veda_schets_details sd ON sd.f_id = sdo.f_schets_detailsid
    JOIN veda_schets child ON child.f_id = sd.f_schetid AND child.f_maininv > 0
    JOIN veda_schets parent ON parent.f_id = child.f_maininv AND parent.f_type = 1
),
invoice_operation_links AS (
    SELECT
        oper_id,
        schet_id,
        GROUP_CONCAT(DISTINCT linked_schet_id ORDER BY linked_schet_id SEPARATOR ',') AS linked_schet_ids,
        GROUP_CONCAT(DISTINCT link_type ORDER BY link_type SEPARATOR ', ') AS invoice_link_types,
        SUM(IFNULL(link_operation_sum, 0)) AS link_operation_sum,
        ROUND(SUM(IFNULL(link_schet_sum, 0)), 2) AS operation_invoice_sum_raw,
        ROUND(SUM(
            CASE
                WHEN link_schet_val = 643 THEN IFNULL(link_schet_sum, 0)
                WHEN link_schet_date IS NULL OR link_schet_date = '0000-00-00' THEN 0
                ELSE IFNULL(link_schet_sum, 0) * getcbrate(link_schet_val, link_schet_date)
            END
        ), 2) AS operation_invoice_sum_rub
    FROM invoice_operation_links_raw
    GROUP BY oper_id, schet_id
),
schet_link_counts AS (
    SELECT schet_id, COUNT(DISTINCT oper_id) AS linked_operation_count
    FROM invoice_operation_links
    GROUP BY schet_id
),
operation_link_counts AS (
    SELECT oper_id, COUNT(DISTINCT schet_id) AS linked_schet_count
    FROM invoice_operation_links
    GROUP BY oper_id
),
linked_schets AS (
    SELECT DISTINCT schet_id
    FROM invoice_operation_links
),
payment_invoice_direct AS (
    SELECT
        ls.schet_id,
        COUNT(DISTINCT d.f_acchistid) AS bank_statement_cnt,
        ROUND(SUM(d.f_clssum), 2) AS paid_sum_rub,
        ROUND(SUM(d.f_clssum / IFNULL(NULLIF(d.f_curs, 0), 1)), 2) AS paid_sum_invoice_currency,
        MIN(ah.f_ppdt) AS first_payment_date,
        MAX(ah.f_ppdt) AS last_payment_date
    FROM linked_schets ls
    JOIN veda_acchist_docs d ON d.f_docid = ls.schet_id AND d.f_doctype = 1
    JOIN veda_acchist ah ON ah.f_id = d.f_acchistid
    GROUP BY ls.schet_id
),
payment_operation_direct AS (
    SELECT
        l.schet_id,
        l.oper_id,
        COUNT(DISTINCT d.f_acchistid) AS bank_statement_cnt,
        ROUND(SUM(d.f_clssum), 2) AS paid_sum_rub,
        ROUND(SUM(d.f_clssum / IFNULL(NULLIF(d.f_curs, 0), 1)), 2) AS paid_sum_invoice_currency,
        MIN(ah.f_ppdt) AS first_payment_date,
        MAX(ah.f_ppdt) AS last_payment_date
    FROM invoice_operation_links l
    JOIN veda_acchist_docs d ON d.f_docid = l.oper_id AND d.f_doctype = 3
    JOIN veda_acchist ah ON ah.f_id = d.f_acchistid
    GROUP BY l.schet_id, l.oper_id
),
payment_by_link AS (
    SELECT
        l.schet_id,
        l.oper_id,
        CASE
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(o.bank_statement_cnt, 0) > 0 THEN o.bank_statement_cnt
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(d.bank_statement_cnt, 0) > 0 THEN 0
            WHEN IFNULL(d.bank_statement_cnt, 0) > 0 THEN d.bank_statement_cnt
            WHEN IFNULL(o.bank_statement_cnt, 0) > 0 AND IFNULL(op_cnt.linked_schet_count, 0) = 1 THEN o.bank_statement_cnt
            ELSE 0
        END AS bank_statement_cnt,
        CASE
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(o.bank_statement_cnt, 0) > 0 THEN o.paid_sum_rub
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(d.bank_statement_cnt, 0) > 0 THEN 0
            WHEN IFNULL(d.bank_statement_cnt, 0) > 0 THEN d.paid_sum_rub
            WHEN IFNULL(o.bank_statement_cnt, 0) > 0 AND IFNULL(op_cnt.linked_schet_count, 0) = 1 THEN o.paid_sum_rub
            ELSE 0
        END AS paid_sum_rub,
        CASE
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(o.bank_statement_cnt, 0) > 0 THEN o.paid_sum_invoice_currency
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(d.bank_statement_cnt, 0) > 0 THEN 0
            WHEN IFNULL(d.bank_statement_cnt, 0) > 0 THEN d.paid_sum_invoice_currency
            WHEN IFNULL(o.bank_statement_cnt, 0) > 0 AND IFNULL(op_cnt.linked_schet_count, 0) = 1 THEN o.paid_sum_invoice_currency
            ELSE 0
        END AS paid_sum_invoice_currency,
        CASE
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(o.bank_statement_cnt, 0) > 0 THEN o.first_payment_date
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(d.bank_statement_cnt, 0) > 0 THEN NULL
            WHEN IFNULL(d.bank_statement_cnt, 0) > 0 THEN d.first_payment_date
            WHEN IFNULL(o.bank_statement_cnt, 0) > 0 AND IFNULL(op_cnt.linked_schet_count, 0) = 1 THEN o.first_payment_date
            ELSE NULL
        END AS first_payment_date,
        CASE
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(o.bank_statement_cnt, 0) > 0 THEN o.last_payment_date
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(d.bank_statement_cnt, 0) > 0 THEN NULL
            WHEN IFNULL(d.bank_statement_cnt, 0) > 0 THEN d.last_payment_date
            WHEN IFNULL(o.bank_statement_cnt, 0) > 0 AND IFNULL(op_cnt.linked_schet_count, 0) = 1 THEN o.last_payment_date
            ELSE NULL
        END AS last_payment_date,
        CASE
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(o.bank_statement_cnt, 0) > 0 THEN 'operation'
            WHEN IFNULL(cnt.linked_operation_count, 0) > 1 AND IFNULL(d.bank_statement_cnt, 0) > 0 THEN 'invoice_unallocated_multi_operation'
            WHEN IFNULL(d.bank_statement_cnt, 0) > 0 THEN 'invoice'
            WHEN IFNULL(o.bank_statement_cnt, 0) > 0 AND IFNULL(op_cnt.linked_schet_count, 0) = 1 THEN 'operation'
            WHEN IFNULL(o.bank_statement_cnt, 0) > 0 AND IFNULL(op_cnt.linked_schet_count, 0) > 1 THEN 'operation_unallocated_multi_invoice'
            ELSE NULL
        END AS payment_link_types
    FROM invoice_operation_links l
    LEFT JOIN schet_link_counts cnt ON cnt.schet_id = l.schet_id
    LEFT JOIN operation_link_counts op_cnt ON op_cnt.oper_id = l.oper_id
    LEFT JOIN payment_invoice_direct d ON d.schet_id = l.schet_id
    LEFT JOIN payment_operation_direct o ON o.schet_id = l.schet_id AND o.oper_id = l.oper_id
),
transfer_by_operation AS (
    SELECT
        l.oper_id,
        l.schet_id,
        COUNT(DISTINCT CASE WHEN p.f_objid = l.oper_id THEN p.f_id END) AS transfer_in_cnt,
        COUNT(DISTINCT CASE WHEN p.f_objidist = l.oper_id THEN p.f_id END) AS transfer_out_cnt,
        ROUND(SUM(CASE WHEN p.f_objid = l.oper_id THEN p.f_sum ELSE 0 END), 2) AS transfer_in_sum_rub,
        ROUND(SUM(CASE WHEN p.f_objidist = l.oper_id THEN p.f_sum ELSE 0 END), 2) AS transfer_out_sum_rub,
        ROUND(
            SUM(CASE WHEN p.f_objid = l.oper_id THEN p.f_sum ELSE 0 END) -
            SUM(CASE WHEN p.f_objidist = l.oper_id THEN p.f_sum ELSE 0 END),
            2
        ) AS transfer_net_sum_rub,
        GROUP_CONCAT(DISTINCT CASE WHEN p.f_objid = l.oper_id THEN p.f_objidist END ORDER BY p.f_objidist SEPARATOR ',') AS transfer_source_oper_ids,
        GROUP_CONCAT(DISTINCT CASE WHEN p.f_objidist = l.oper_id THEN p.f_objid END ORDER BY p.f_objid SEPARATOR ',') AS transfer_dest_oper_ids,
        GROUP_CONCAT(DISTINCT p.f_acchistlink ORDER BY p.f_acchistlink SEPARATOR ',') AS transfer_acchist_ids
    FROM invoice_operation_links l
    LEFT JOIN veda_pays p
      ON p.f_objtype = 35
     AND p.f_objtypeist = 35
     AND (p.f_objid = l.oper_id OR p.f_objidist = l.oper_id)
    GROUP BY l.oper_id, l.schet_id
),
rows_base AS (
    SELECT
        sp.spec_status_groupes,
        sp.custom_route_arrive_min_date AS route_arrive_to_date,
        COALESCE(rd.route_port_arrival_date, rd.route_border_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) AS port_arrival_date,
        COALESCE(rd.route_border_arrival_date, rd.route_port_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) AS border_arrival_date,
        rd.route_any_arrival_date,
        sp.spec_id,
        sp.spec_num,
        sp.spec_url,
        sp.spec_client_name,
        sp.spec_manager,
        sp.manager_department,
        io.operation_calc_auto,
        io.operation_financing_flag,
        io.operation_financing_status,
        CASE
            WHEN io.oper_id IS NULL THEN 0
            WHEN io.operation_financing_flag = 1 THEN 0
            WHEN io.include_in_control_auto = 0 THEN 0
            ELSE 1
        END AS invoice_expected_flag,
        CASE
            WHEN io.oper_id IS NULL THEN NULL
            WHEN io.operation_financing_flag = 1 THEN NULL
            WHEN io.include_in_control_auto = 0 THEN NULL
            WHEN LOWER(CONCAT_WS(' ', io.operation_name, io.bdrarticle_name)) REGEXP 'морфрахт|тамож|деклар|пошлин'
                THEN 'За 3 дня до прибытия на границу'
            ELSE 'Не позже 3 дней после прибытия в порт'
        END AS route_due_rule_name,
        CASE
            WHEN io.oper_id IS NULL THEN NULL
            WHEN io.operation_financing_flag = 1 THEN NULL
            WHEN io.include_in_control_auto = 0 THEN NULL
            WHEN LOWER(CONCAT_WS(' ', io.operation_name, io.bdrarticle_name)) REGEXP 'морфрахт|тамож|деклар|пошлин'
             AND COALESCE(rd.route_border_arrival_date, rd.route_port_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) IS NOT NULL
                THEN DATE_SUB(COALESCE(rd.route_border_arrival_date, rd.route_port_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date), INTERVAL 3 DAY)
            WHEN COALESCE(rd.route_port_arrival_date, rd.route_border_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) IS NOT NULL
                THEN DATE_ADD(COALESCE(rd.route_port_arrival_date, rd.route_border_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date), INTERVAL 3 DAY)
            ELSE NULL
        END AS route_rule_due_date,
        CASE
            WHEN sp.custom_route_arrive_min_date IS NULL THEN NULL
            WHEN io.operation_billing_rule_comment IS NULL OR TRIM(io.operation_billing_rule_comment) = '' THEN NULL
            WHEN io.operation_billing_rule_comment NOT REGEXP '[0-9]+' THEN NULL
            WHEN LOWER(io.operation_billing_rule_comment) REGEXP 'до.*прибыт|за.*до' THEN
                DATE_SUB(sp.custom_route_arrive_min_date, INTERVAL CAST(REGEXP_SUBSTR(io.operation_billing_rule_comment, '[0-9]+') AS UNSIGNED) DAY)
            WHEN LOWER(io.operation_billing_rule_comment) REGEXP 'после.*прибыт|после' THEN
                DATE_ADD(sp.custom_route_arrive_min_date, INTERVAL CAST(REGEXP_SUBSTR(io.operation_billing_rule_comment, '[0-9]+') AS UNSIGNED) DAY)
            ELSE NULL
        END AS billing_comment_due_date,
        COALESCE(
            CASE
                WHEN io.operation_financing_flag = 1 THEN NULL
                WHEN io.include_in_control_auto = 0 THEN NULL
                ELSE DATE(io.operation_due_datetime)
            END,
            CASE
                WHEN io.oper_id IS NULL THEN NULL
                WHEN io.operation_financing_flag = 1 THEN NULL
                WHEN io.include_in_control_auto = 0 THEN NULL
                WHEN LOWER(CONCAT_WS(' ', io.operation_name, io.bdrarticle_name)) REGEXP 'морфрахт|тамож|деклар|пошлин'
                 AND COALESCE(rd.route_border_arrival_date, rd.route_port_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) IS NOT NULL
                    THEN DATE_SUB(COALESCE(rd.route_border_arrival_date, rd.route_port_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date), INTERVAL 3 DAY)
                WHEN COALESCE(rd.route_port_arrival_date, rd.route_border_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) IS NOT NULL
                    THEN DATE_ADD(COALESCE(rd.route_port_arrival_date, rd.route_border_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date), INTERVAL 3 DAY)
                ELSE NULL
            END
        ) AS invoice_control_due_date,
        CASE
            WHEN io.oper_id IS NULL THEN 'Нет доходных операций'
            WHEN io.operation_financing_flag = 1 THEN 'Финансирование - счет не ждем'
            WHEN io.include_in_control_auto = 0 THEN 'Не контролируем'
            WHEN COALESCE(
                CASE
                    WHEN io.operation_financing_flag = 1 THEN NULL
                    WHEN io.include_in_control_auto = 0 THEN NULL
                    ELSE DATE(io.operation_due_datetime)
                END,
                CASE
                    WHEN io.oper_id IS NULL THEN NULL
                    WHEN io.operation_financing_flag = 1 THEN NULL
                    WHEN io.include_in_control_auto = 0 THEN NULL
                    WHEN LOWER(CONCAT_WS(' ', io.operation_name, io.bdrarticle_name)) REGEXP 'морфрахт|тамож|деклар|пошлин'
                     AND COALESCE(rd.route_border_arrival_date, rd.route_port_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) IS NOT NULL
                        THEN DATE_SUB(COALESCE(rd.route_border_arrival_date, rd.route_port_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date), INTERVAL 3 DAY)
                    WHEN COALESCE(rd.route_port_arrival_date, rd.route_border_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) IS NOT NULL
                        THEN DATE_ADD(COALESCE(rd.route_port_arrival_date, rd.route_border_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date), INTERVAL 3 DAY)
                    ELSE NULL
                END
            ) IS NULL THEN 'Нет срока'
            WHEN COALESCE(
                CASE
                    WHEN io.operation_financing_flag = 1 THEN NULL
                    WHEN io.include_in_control_auto = 0 THEN NULL
                    ELSE DATE(io.operation_due_datetime)
                END,
                CASE
                    WHEN io.oper_id IS NULL THEN NULL
                    WHEN io.operation_financing_flag = 1 THEN NULL
                    WHEN io.include_in_control_auto = 0 THEN NULL
                    WHEN LOWER(CONCAT_WS(' ', io.operation_name, io.bdrarticle_name)) REGEXP 'морфрахт|тамож|деклар|пошлин'
                     AND COALESCE(rd.route_border_arrival_date, rd.route_port_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) IS NOT NULL
                        THEN DATE_SUB(COALESCE(rd.route_border_arrival_date, rd.route_port_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date), INTERVAL 3 DAY)
                    WHEN COALESCE(rd.route_port_arrival_date, rd.route_border_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date) IS NOT NULL
                        THEN DATE_ADD(COALESCE(rd.route_port_arrival_date, rd.route_border_arrival_date, rd.route_any_arrival_date, sp.custom_route_arrive_min_date), INTERVAL 3 DAY)
                    ELSE NULL
                END
            ) <= CURDATE() THEN 'Пора выставить счет'
            ELSE 'Не пора'
        END AS invoice_timing_status,
        CASE
            WHEN io.oper_id IS NULL THEN 'Нет доходных операций'
            WHEN l.schet_id IS NULL THEN 'Счет не выставлен'
            ELSE 'Счет выставлен'
        END AS invoice_issue_status,
        CASE
            WHEN l.schet_id IS NULL AND io.oper_id IS NULL THEN 'Нет доходных операций'
            WHEN l.schet_id IS NULL THEN 'Нет счета'
            WHEN s.f_status = 9 THEN 'Аннулирован'
            WHEN IFNULL(l.operation_invoice_sum_rub, s.f_sum) = 0 THEN 'Нулевая сумма'
            WHEN IFNULL(p.paid_sum_rub, 0) >= IFNULL(l.operation_invoice_sum_rub, s.f_sum) - 0.01 THEN 'Оплачен'
            WHEN IFNULL(p.paid_sum_rub, 0) > 0 THEN 'Оплачен частично'
            ELSE 'Не оплачен'
        END AS invoice_payment_status,
        io.oper_id,
        io.f_idoper,
        io.operation_name,
        io.f_bdrarticle,
        io.bdrarticle_name,
        io.operation_group_key,
        io.include_in_control_auto,
        io.operation_due_datetime,
        io.operation_billing_rule_comment,
        io.operation_invoice_comment,
        l.schet_id,
        l.linked_schet_ids,
        l.invoice_link_types,
        l.link_operation_sum,
        l.operation_invoice_sum_raw,
        l.operation_invoice_sum_rub,
        s.f_num AS schet_num,
        s.f_grnd AS schet_grnd,
        s.f_com AS schet_comment,
        s.f_dt AS schet_date,
        CASE WHEN s.f_srok IS NULL OR s.f_srok = '0000-00-00' THEN NULL ELSE s.f_srok END AS schet_due_date,
        s.f_status AS schet_erp_status_id,
        st.f_name AS schet_erp_status_name,
        s.f_val AS schet_val,
        val_spr.f_name AS schet_currency_name,
        s.f_sum AS schet_total_sum,
        CASE
            WHEN s.f_id IS NULL THEN NULL
            WHEN s.f_val = 643 THEN ROUND(s.f_sum, 2)
            WHEN IFNULL(s.f_ismaininv, 0) = 1 THEN ROUND(s.f_sum, 2)
            WHEN s.f_dt IS NULL OR s.f_dt = '0000-00-00' THEN NULL
            ELSE ROUND(s.f_sum * getcbrate(s.f_val, s.f_dt), 2)
        END AS schet_total_sum_rub,
        IFNULL(l.operation_invoice_sum_raw, s.f_sum) AS schet_sum,
        IFNULL(
            l.operation_invoice_sum_rub,
            CASE
                WHEN s.f_id IS NULL THEN NULL
                WHEN s.f_val = 643 THEN ROUND(s.f_sum, 2)
                WHEN IFNULL(s.f_ismaininv, 0) = 1 THEN ROUND(s.f_sum, 2)
                WHEN s.f_dt IS NULL OR s.f_dt = '0000-00-00' THEN NULL
                ELSE ROUND(s.f_sum * getcbrate(s.f_val, s.f_dt), 2)
            END
        ) AS schet_sum_rub,
        IFNULL(p.paid_sum_invoice_currency, 0) AS paid_sum_invoice_currency,
        IFNULL(p.paid_sum_rub, 0) AS paid_sum_rub,
        p.bank_statement_cnt,
        CASE WHEN IFNULL(p.bank_statement_cnt, 0) > 0 THEN 1 ELSE 0 END AS has_bank_statement,
        p.payment_link_types,
        CASE WHEN IFNULL(tr.transfer_in_cnt, 0) + IFNULL(tr.transfer_out_cnt, 0) > 0 THEN 1 ELSE 0 END AS has_transfer,
        IFNULL(tr.transfer_in_cnt, 0) AS transfer_in_cnt,
        IFNULL(tr.transfer_out_cnt, 0) AS transfer_out_cnt,
        IFNULL(tr.transfer_in_sum_rub, 0) AS transfer_in_sum_rub,
        IFNULL(tr.transfer_out_sum_rub, 0) AS transfer_out_sum_rub,
        IFNULL(tr.transfer_net_sum_rub, 0) AS transfer_net_sum_rub,
        tr.transfer_source_oper_ids,
        tr.transfer_dest_oper_ids,
        tr.transfer_acchist_ids,
        p.first_payment_date,
        p.last_payment_date
    FROM specs sp
    LEFT JOIN route_dates rd ON rd.spec_id = sp.spec_id
    LEFT JOIN income_ops io ON io.spec_id = sp.spec_id
    LEFT JOIN invoice_operation_links l ON l.oper_id = io.oper_id
    LEFT JOIN veda_schets s ON s.f_id = l.schet_id AND s.f_type = 1
    LEFT JOIN payment_by_link p ON p.schet_id = l.schet_id AND p.oper_id = io.oper_id
    LEFT JOIN transfer_by_operation tr ON tr.oper_id = io.oper_id AND tr.schet_id = l.schet_id
    LEFT JOIN veda_spr st ON st.f_type = 12 AND st.f_num = s.f_status
    LEFT JOIN veda_spr val_spr ON val_spr.f_type = 4 AND val_spr.f_num = s.f_val
),
rows_enriched AS (
    SELECT
        rb.*,
        COALESCE(rb.schet_due_date, rb.invoice_control_due_date) AS effective_due_date,
        CASE
            WHEN rb.schet_due_date IS NOT NULL THEN 'Из счета'
            WHEN rb.operation_due_datetime IS NOT NULL THEN 'Дата операции'
            WHEN rb.route_rule_due_date IS NOT NULL THEN 'Правило операции + маршрут'
            WHEN rb.invoice_control_due_date IS NOT NULL THEN 'Расчетный срок'
            ELSE NULL
        END AS effective_due_date_source
    FROM rows_base rb
)
SELECT
    spec_status_groupes,
    route_arrive_to_date,
    port_arrival_date,
    border_arrival_date,
    route_any_arrival_date,
    spec_id,
    spec_num,
    spec_url,
    spec_client_name,
    spec_manager,
    manager_department,
    operation_calc_auto,
    operation_financing_flag,
    operation_financing_status,
    invoice_expected_flag,
    route_due_rule_name,
    route_rule_due_date,
    billing_comment_due_date,
    invoice_control_due_date,
    invoice_timing_status,
    invoice_issue_status,
    invoice_payment_status,
    invoice_payment_status AS calc_payment_status,
    oper_id,
    f_idoper,
    operation_name,
    f_bdrarticle,
    bdrarticle_name,
    operation_group_key,
    include_in_control_auto,
    operation_due_datetime,
    operation_billing_rule_comment,
    operation_invoice_comment,
    schet_id,
    linked_schet_ids,
    invoice_link_types,
    link_operation_sum,
    operation_invoice_sum_raw,
    operation_invoice_sum_rub,
    schet_num,
    schet_grnd,
    schet_comment,
    schet_date,
    schet_due_date,
    effective_due_date,
    effective_due_date_source,
    schet_erp_status_id,
    schet_erp_status_name,
    schet_val,
    schet_currency_name,
    schet_total_sum,
    schet_total_sum_rub,
    schet_sum,
    schet_sum_rub,
    paid_sum_invoice_currency,
    paid_sum_rub,
    ROUND(IFNULL(schet_sum, 0) - IFNULL(paid_sum_invoice_currency, 0), 2) AS unpaid_sum_invoice_currency,
    ROUND(IFNULL(schet_sum_rub, 0) - IFNULL(paid_sum_rub, 0), 2) AS unpaid_sum_rub,
    ROUND(IFNULL(paid_sum_rub, 0) + IFNULL(transfer_in_sum_rub, 0) - IFNULL(transfer_out_sum_rub, 0), 2) AS effective_paid_sum_rub,
    ROUND(
        IFNULL(schet_sum_rub, 0) -
        (IFNULL(paid_sum_rub, 0) + IFNULL(transfer_in_sum_rub, 0) - IFNULL(transfer_out_sum_rub, 0)),
        2
    ) AS effective_unpaid_sum_rub,
    CASE
        WHEN schet_id IS NULL THEN 'Нет счета'
        WHEN schet_sum_rub IS NULL THEN 'Нет рублевой суммы счета'
        WHEN IFNULL(paid_sum_rub, 0) >= schet_sum_rub - 0.01
         AND IFNULL(transfer_out_sum_rub, 0) > 0
         AND (IFNULL(paid_sum_rub, 0) + IFNULL(transfer_in_sum_rub, 0) - IFNULL(transfer_out_sum_rub, 0)) < schet_sum_rub - 0.01
            THEN 'Оплачен, но деньги перенесены'
        WHEN (IFNULL(paid_sum_rub, 0) + IFNULL(transfer_in_sum_rub, 0) - IFNULL(transfer_out_sum_rub, 0)) >= schet_sum_rub - 0.01
            THEN 'Деньги покрывают счет'
        WHEN (IFNULL(paid_sum_rub, 0) + IFNULL(transfer_in_sum_rub, 0) - IFNULL(transfer_out_sum_rub, 0)) > 0
            THEN 'Денег осталось частично'
        ELSE 'Денег нет'
    END AS effective_money_status,
    CASE
        WHEN schet_id IS NULL AND effective_due_date IS NULL THEN 'Нет счета и срока'
        WHEN schet_id IS NULL AND effective_due_date < CURDATE() THEN 'Нет счета, срок прошел'
        WHEN schet_id IS NULL THEN 'Нет счета'
        WHEN schet_sum_rub IS NULL THEN 'Нет рублевой суммы счета'
        WHEN (IFNULL(paid_sum_rub, 0) + IFNULL(transfer_in_sum_rub, 0) - IFNULL(transfer_out_sum_rub, 0)) >= schet_sum_rub - 0.01
            THEN 'Оплачено'
        WHEN effective_due_date IS NULL THEN 'Нет срока оплаты'
        WHEN effective_due_date < CURDATE() THEN 'Просрочено'
        WHEN effective_due_date = CURDATE() THEN 'Срок сегодня'
        ELSE 'Срок не наступил'
    END AS effective_due_status,
    bank_statement_cnt,
    has_bank_statement,
    payment_link_types,
    has_transfer,
    CASE WHEN IFNULL(transfer_in_sum_rub, 0) > 0 THEN 1 ELSE 0 END AS has_transfer_in,
    CASE WHEN IFNULL(transfer_out_sum_rub, 0) > 0 THEN 1 ELSE 0 END AS has_transfer_out,
    transfer_in_cnt,
    transfer_out_cnt,
    transfer_in_sum_rub,
    transfer_out_sum_rub,
    transfer_net_sum_rub,
    transfer_source_oper_ids,
    transfer_dest_oper_ids,
    transfer_acchist_ids,
    first_payment_date,
    last_payment_date
FROM rows_enriched;
