#!/usr/bin/env python3
"""Extract the accounting reference sheet for AERO-TRADE contract 660/1.

The production ERP must not depend on Google Sheets. This script is an
analytical/reference tool: it turns the accountant-approved sheet into a
normalized JSON fixture and a UI model used to validate the PHP implementation.
"""

from __future__ import annotations

import argparse
import json
import os
import re
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import gspread
from google.oauth2.service_account import Credentials


SPREADSHEET_ID = "1v50v2vM8Yqf7TeGQ4oY3tRZEvsDHs0lGW8_q0oQqfSE"
SHEET_NAME = "2020 договор 660/1"
DEFAULT_CREDENTIALS = os.environ.get("GOOGLE_APPLICATION_CREDENTIALS", "")

COLUMNS = {
    "spec": 1,  # B
    "account": 3,  # D
    "invoice_sum": 4,  # E
    "payment_sum": 5,  # F
    "reimbursable": 7,  # H
    "non_reimbursable": 8,  # I
    "sf": 10,  # K
    "delta": 11,  # L
    "cb_goods": 12,  # M
}


def cell(row: list[str], index: int) -> str:
    if index >= len(row):
        return ""
    return str(row[index] or "").replace("\xa0", " ").strip()


def compact_space(value: str) -> str:
    return re.sub(r"[ \t]+", " ", str(value or "").replace("\xa0", " ")).strip()


def parse_amount(raw: str, default_currency: str = "RUB") -> tuple[float | None, str | None]:
    value = compact_space(raw)
    if not value or not re.search(r"\d", value):
        return None, None

    currency = default_currency
    low = value.lower()
    if "$" in value:
        currency = "USD"
    elif "€" in value:
        currency = "EUR"
    elif "р" in low or "руб" in low:
        currency = "RUB"

    normalized = (
        value.replace("−", "-")
        .replace("–", "-")
        .replace("—", "-")
        .replace(",", ".")
    )
    normalized = re.sub(r"[^0-9.\-]", "", normalized)
    if normalized.count("-") > 1:
        normalized = "-" + normalized.replace("-", "")
    if normalized.count(".") > 1:
        head, *tail = normalized.split(".")
        normalized = head + "." + "".join(tail)
    try:
        return round(float(normalized), 2), currency
    except ValueError:
        return None, None


def amount_text(value: float | None, currency: str = "RUB") -> str:
    if value is None:
        return "—"
    sign = "-" if value < 0 else ""
    integer, _, frac = f"{abs(value):.2f}".partition(".")
    chunks: list[str] = []
    while integer:
        chunks.append(integer[-3:])
        integer = integer[:-3]
    number = " ".join(reversed(chunks)) + "," + frac
    if currency == "USD":
        return f"${sign}{number}"
    if currency == "EUR":
        return f"€{sign}{number}"
    return f"{sign}{number} р."


def spec_number(label: str) -> str:
    text = compact_space(label)
    match = re.search(r"(?:Спецификация|Заявка)\s*(?:№)?\s*([^,\s]+)", text, re.I)
    return match.group(1) if match else text


def status_for_delta(delta: float | None) -> list[dict[str, str]]:
    if delta is None:
        return [{"key": "pending", "label": "Нет дельты"}]
    if abs(delta) <= 0.01:
        return [{"key": "ok", "label": "ОК"}]
    return [{"key": "sum", "label": "Остаток"}]


def append_line_if_any(block: dict[str, Any], row_number: int, row: list[str]) -> None:
    account = cell(row, COLUMNS["account"])
    invoice_sum_text = cell(row, COLUMNS["invoice_sum"])
    payment_sum_text = cell(row, COLUMNS["payment_sum"])
    if not any([account, invoice_sum_text, payment_sum_text]):
        return

    invoice_amount, invoice_currency = parse_amount(invoice_sum_text)
    payment_amount, _ = parse_amount(payment_sum_text, "RUB")
    block["lines"].append(
        {
            "source_row": row_number,
            "account": account,
            "invoice_sum_text": invoice_sum_text,
            "invoice_amount": invoice_amount,
            "invoice_currency": invoice_currency,
            "payment_sum_text": payment_sum_text,
            "payment_amount_rub": payment_amount,
        }
    )


def append_adjustment_if_any(block: dict[str, Any], row_number: int, row: list[str]) -> None:
    reimbursable_text = cell(row, COLUMNS["reimbursable"])
    non_reimbursable_text = cell(row, COLUMNS["non_reimbursable"])
    sf_text = cell(row, COLUMNS["sf"])
    delta_text = cell(row, COLUMNS["delta"])
    if not any([reimbursable_text, non_reimbursable_text, sf_text, delta_text]):
        return

    reimbursable_amount, _ = parse_amount(reimbursable_text, "RUB")
    non_reimbursable_amount, _ = parse_amount(non_reimbursable_text, "RUB")
    delta_amount, _ = parse_amount(delta_text, "RUB")
    block["adjustments"].append(
        {
            "source_row": row_number,
            "reimbursable_text": reimbursable_text,
            "reimbursable_amount_rub": reimbursable_amount,
            "non_reimbursable_text": non_reimbursable_text,
            "non_reimbursable_amount_rub": non_reimbursable_amount,
            "sf_text": sf_text,
            "delta_text": delta_text,
            "delta_amount_rub": delta_amount,
        }
    )


def finalize_block(block: dict[str, Any]) -> dict[str, Any]:
    payment_total = round(
        sum(float(line["payment_amount_rub"] or 0) for line in block["lines"]), 2
    )
    invoice_totals: dict[str, float] = {}
    for line in block["lines"]:
        amount = line["invoice_amount"]
        currency = line["invoice_currency"]
        if amount is None or not currency:
            continue
        invoice_totals[currency] = round(invoice_totals.get(currency, 0) + float(amount), 2)

    reimbursable_base, _ = parse_amount(block.get("reimbursable_text", ""), "RUB")
    non_reimbursable_base, _ = parse_amount(block.get("non_reimbursable_text", ""), "RUB")
    reimbursable_adjustments = round(
        sum(float(row.get("reimbursable_amount_rub") or 0) for row in block["adjustments"]),
        2,
    )
    non_reimbursable_adjustments = round(
        sum(float(row.get("non_reimbursable_amount_rub") or 0) for row in block["adjustments"]),
        2,
    )
    reimbursable = round(float(reimbursable_base or 0) + reimbursable_adjustments, 2)
    non_reimbursable = round(float(non_reimbursable_base or 0) + non_reimbursable_adjustments, 2)
    delta, _ = parse_amount(block.get("delta_text", ""), "RUB")
    calculated_delta = round(
        payment_total - float(reimbursable or 0) - float(non_reimbursable or 0), 2
    )
    sf_parts = [block.get("sf_text", "")]
    sf_parts.extend(row["sf_text"] for row in block["adjustments"] if row.get("sf_text"))
    block.update(
        {
            "spec_number": spec_number(block["label"]),
            "invoice_totals": invoice_totals,
            "payment_total_rub": payment_total,
            "reimbursable_base_rub": reimbursable_base,
            "non_reimbursable_base_rub": non_reimbursable_base,
            "reimbursable_adjustments_rub": reimbursable_adjustments,
            "non_reimbursable_adjustments_rub": non_reimbursable_adjustments,
            "reimbursable_rub": reimbursable,
            "non_reimbursable_rub": non_reimbursable,
            "delta_rub": delta,
            "calculated_delta_rub": calculated_delta,
            "delta_check_diff_rub": round((delta or 0) - calculated_delta, 2),
            "sf_full_text": "\n".join(part for part in sf_parts if part),
            "status": status_for_delta(delta),
        }
    )
    return block


def parse_sheet(rows: list[list[str]]) -> tuple[list[dict[str, Any]], int]:
    header_index = -1
    for idx, row in enumerate(rows):
        if cell(row, COLUMNS["spec"]) == "№ спецификации" and cell(row, COLUMNS["account"]) == "Счет":
            header_index = idx
            break
    if header_index < 0:
        raise RuntimeError("Header row was not found in Google Sheet")

    blocks: list[dict[str, Any]] = []
    current: dict[str, Any] | None = None
    for row_index, row in enumerate(rows[header_index + 1 :], start=header_index + 2):
        spec_label = cell(row, COLUMNS["spec"])
        relevant_values = [
            spec_label,
            cell(row, COLUMNS["account"]),
            cell(row, COLUMNS["invoice_sum"]),
            cell(row, COLUMNS["payment_sum"]),
            cell(row, COLUMNS["reimbursable"]),
            cell(row, COLUMNS["non_reimbursable"]),
            cell(row, COLUMNS["sf"]),
            cell(row, COLUMNS["delta"]),
        ]
        if not any(relevant_values):
            continue

        if spec_label:
            if current and current["lines"]:
                blocks.append(finalize_block(current))
            current = {
                "source_start_row": row_index,
                "label": spec_label,
                "reimbursable_text": cell(row, COLUMNS["reimbursable"]),
                "non_reimbursable_text": cell(row, COLUMNS["non_reimbursable"]),
                "sf_text": cell(row, COLUMNS["sf"]),
                "delta_text": cell(row, COLUMNS["delta"]),
                "cb_goods_marker": cell(row, COLUMNS["cb_goods"]),
                "lines": [],
                "adjustments": [],
            }
        elif current is None:
            current = {
                "source_start_row": row_index,
                "label": "Без спецификации",
                "reimbursable_text": "",
                "non_reimbursable_text": "",
                "sf_text": "",
                "delta_text": "",
                "cb_goods_marker": "",
                "lines": [],
                "adjustments": [],
            }

        append_line_if_any(current, row_index, row)
        if not spec_label:
            append_adjustment_if_any(current, row_index, row)

    if current and current["lines"]:
        blocks.append(finalize_block(current))
    return blocks, header_index + 1


def invoice_breakdown(blocks: list[dict[str, Any]]) -> dict[str, float]:
    totals: dict[str, float] = {}
    for block in blocks:
        for currency, amount in block.get("invoice_totals", {}).items():
            totals[currency] = round(totals.get(currency, 0) + float(amount), 2)
    return totals


def invoice_breakdown_label(totals: dict[str, float]) -> str:
    order = ["RUB", "USD", "EUR"]
    parts = []
    for currency in order + sorted(set(totals) - set(order)):
        if currency in totals:
            parts.append(amount_text(totals[currency], currency))
    return " + ".join(parts) if parts else "—"


def build_ui_fixture(blocks: list[dict[str, Any]], audit: dict[str, Any]) -> dict[str, Any]:
    total_payment = round(sum(float(block.get("payment_total_rub") or 0) for block in blocks), 2)
    total_reimb = round(sum(float(block.get("reimbursable_rub") or 0) for block in blocks), 2)
    total_non = round(sum(float(block.get("non_reimbursable_rub") or 0) for block in blocks), 2)
    total_delta = round(sum(float(block.get("delta_rub") or 0) for block in blocks), 2)
    total_invoices = invoice_breakdown(blocks)
    total_invoice_label = invoice_breakdown_label(total_invoices)
    issue_count = sum(1 for block in blocks if abs(float(block.get("delta_rub") or 0)) > 0.01)
    totals = {
        "invoiceSum": None,
        "invoiceSumLabel": total_invoice_label,
        "paymentSum": total_payment,
        "reimbursableSum": total_reimb,
        "nonReimbursableSum": total_non,
        "delta": total_delta,
        "badges": (
            [{"key": "sum", "label": f"Ост.: {issue_count}"}]
            if issue_count
            else [{"key": "ok", "label": "ОК"}]
        ),
        "showAmounts": True,
    }
    spec_rows = []
    for block in blocks:
        account_lines = [line["account"] or "—" for line in block["lines"]]
        invoice_lines = [line["invoice_sum_text"] or "—" for line in block["lines"]]
        payment_lines = [line["payment_sum_text"] or "—" for line in block["lines"]]
        spec_rows.append(
            {
                "id": f"reference-spec-{block['source_start_row']}",
                "kind": "spec",
                "level": 3,
                "name": block["label"],
                "note": f"строка листа {block['source_start_row']}",
                "specNo": block["spec_number"],
                "isParent": False,
                "defaultExpanded": False,
                "detailDocs": [],
                "invoiceLabel": "\n".join(account_lines),
                "invoiceSum": None,
                "invoiceSumLabel": "\n".join(invoice_lines),
                "paymentSum": block["payment_total_rub"],
                "paymentSumLabel": "\n".join(payment_lines),
                "reimbursableSum": block["reimbursable_rub"],
                "nonReimbursableSum": block["non_reimbursable_rub"],
                "sfLabel": block["sf_full_text"] or "—",
                "delta": block["delta_rub"],
                "badges": block["status"],
                "showAmounts": True,
            }
        )

    contract = {
        "id": "reference-contract-660-1",
        "kind": "contract",
        "level": 2,
        "name": "Договор: 660/1",
        "note": "ERP dog_id=88, код 1С БП-003453; лист «2020 договор 660/1»",
        "specNo": f"{len(spec_rows)} спецификаций",
        "isParent": True,
        "defaultExpanded": True,
        "children": spec_rows,
        **totals,
    }
    legal = {
        "id": "reference-legal-221",
        "kind": "legal",
        "level": 1,
        "name": "ЮЛ: АЭРО-ТРЕЙД",
        "note": "ИНН 7811451960",
        "specNo": "1 договор",
        "isParent": True,
        "defaultExpanded": True,
        "children": [contract],
        **totals,
    }
    client = {
        "id": "reference-client-115",
        "kind": "client",
        "level": 0,
        "name": "Клиент: АЭРО-ТРЕЙД",
        "note": "ИНН 7811451960",
        "specNo": "1 ЮЛ",
        "isParent": True,
        "defaultExpanded": True,
        "children": [legal],
        **totals,
    }
    return {
        "source": "google_reference",
        "sourceState": "reference",
        "sourceLabel": "Референс бухгалтера · лист «2020 договор 660/1»",
        "delivery": {
            "spec_id": "contract-660-1-reference",
            "spec_number": "660/1",
            "client_name": "АЭРО-ТРЕЙД",
            "client_inn": "7811451960",
            "main_dog_number": "660/1",
            "main_dog_code1c": "БП-003453",
            "goods_name": "Бухгалтерская ведомость по договору 660/1",
        },
        "matrix_model": {
            "clients": [
                {"id": client["id"], "name": "АЭРО-ТРЕЙД", "inn": "7811451960", "root": client}
            ],
            "totals": totals,
            "rowsCount": sum(len(block["lines"]) for block in blocks),
            "sourceLabel": "Референс бухгалтера · MariaDB audit",
        },
        "reference": {
            "spreadsheet_id": SPREADSHEET_ID,
            "sheet_name": SHEET_NAME,
            "blocks_count": len(blocks),
            "line_rows_count": sum(len(block["lines"]) for block in blocks),
            "invoice_breakdown": total_invoices,
            "mariadb_audit": audit,
        },
    }


def load_audit(path: Path | None) -> dict[str, Any]:
    if path and path.exists():
        return json.loads(path.read_text(encoding="utf-8"))
    return {}


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--credentials", default=DEFAULT_CREDENTIALS)
    parser.add_argument("--reference-json", default="reference/aero_trade_660_1_2020_reference.json")
    parser.add_argument("--ui-json", default="ui/reference/aero_trade_660_1_2020.json")
    parser.add_argument("--audit-json", default="reference/aero_trade_660_1_mariadb_audit.json")
    parser.add_argument("--min-row", type=int, default=0)
    parser.add_argument("--max-row", type=int, default=0)
    parser.add_argument("--specs", default="")
    args = parser.parse_args()

    if not args.credentials:
        raise SystemExit("Pass --credentials or set GOOGLE_APPLICATION_CREDENTIALS")

    scopes = ["https://www.googleapis.com/auth/spreadsheets.readonly"]
    credentials = Credentials.from_service_account_file(args.credentials, scopes=scopes)
    client = gspread.authorize(credentials)
    worksheet = client.open_by_key(SPREADSHEET_ID).worksheet(SHEET_NAME)
    rows = worksheet.get_all_values()
    blocks, header_row = parse_sheet(rows)
    wanted_specs = {item.strip() for item in args.specs.split(",") if item.strip()}
    if args.min_row:
        blocks = [block for block in blocks if int(block["source_start_row"]) >= args.min_row]
    if args.max_row:
        blocks = [block for block in blocks if int(block["source_start_row"]) <= args.max_row]
    if wanted_specs:
        blocks = [block for block in blocks if str(block.get("spec_number", "")).strip() in wanted_specs]
    audit = load_audit(Path(args.audit_json))
    now = datetime.now(timezone.utc).isoformat()

    reference = {
        "source": {
            "spreadsheet_id": SPREADSHEET_ID,
            "sheet_name": SHEET_NAME,
            "header_row": header_row,
            "extracted_at": now,
        },
        "contract": {
            "client_contact_id": 115,
            "client_legal_id": 221,
            "dog_id": 88,
            "dog_number": "660/1",
            "dog_code1c": "БП-003453",
        },
        "summary": {
            "blocks_count": len(blocks),
            "line_rows_count": sum(len(block["lines"]) for block in blocks),
            "payment_total_rub": round(sum(float(block.get("payment_total_rub") or 0) for block in blocks), 2),
            "reimbursable_total_rub": round(sum(float(block.get("reimbursable_rub") or 0) for block in blocks), 2),
            "non_reimbursable_total_rub": round(sum(float(block.get("non_reimbursable_rub") or 0) for block in blocks), 2),
            "delta_total_rub": round(sum(float(block.get("delta_rub") or 0) for block in blocks), 2),
            "invoice_breakdown": invoice_breakdown(blocks),
        },
        "mariadb_audit": audit,
        "blocks": blocks,
    }

    ref_path = Path(args.reference_json)
    ui_path = Path(args.ui_json)
    ref_path.parent.mkdir(parents=True, exist_ok=True)
    ui_path.parent.mkdir(parents=True, exist_ok=True)
    ref_path.write_text(json.dumps(reference, ensure_ascii=False, indent=2), encoding="utf-8")
    ui_path.write_text(
        json.dumps(build_ui_fixture(blocks, audit), ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    print(f"wrote {ref_path} ({len(blocks)} blocks)")
    print(f"wrote {ui_path}")


if __name__ == "__main__":
    main()
