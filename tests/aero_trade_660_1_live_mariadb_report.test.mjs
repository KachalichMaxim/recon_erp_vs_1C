import test from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";

const ROOT = new URL("../", import.meta.url);

async function readJson(relativePath) {
  return JSON.parse(await readFile(new URL(relativePath, ROOT), "utf8"));
}

function money(value) {
  return Math.round(Number(value || 0) * 100);
}

function assertMoney(actual, expected, message) {
  assert.equal(money(actual), money(expected), message);
}

function blockBySpec(report) {
  return new Map(report.blocks.map((block) => [String(block.spec_num), block]));
}

test("live MariaDB report for AERO-TRADE 660/1 is built from server SQL sources", async () => {
  const report = await readJson("reference/aero_trade_660_1_live_mariadb_report_20260624.json");

  assert.equal(report.context.contact_id, 115);
  assert.equal(report.context.legal_id, 221);
  assert.equal(report.context.dog_id, 88);
  assert.equal(report.context.dog_name, "660/1");
  assert.match(report.sql_sources.payments, /f_acchistid/);
  assert.equal(report.parameters.spec_nums.join(","), "1063,1064,1065,1068,1072,1073,1074,1076");

  assertMoney(report.summary.customer_invoice_total, 288_812.42);
  assertMoney(report.summary.paid_total_get_paidsum, 2_331_111.33);
  assertMoney(report.summary.paid_total_acchist_docs, 2_331_111.33);
  assertMoney(report.summary.reimbursable_realization_get_realizsum, 2_152_392.27);
  assertMoney(report.summary.non_reimbursable_realization_get_realizsum, 263_133.46);
  assertMoney(report.summary.realization_total_get_realizsum, 2_415_525.73);
  assertMoney(report.summary.settlement_delta_paid_minus_realization, -84_414.40);
  assert.deepEqual(report.summary.issues, { SETTLEMENT_BALANCE_NONZERO: 8 });
});

test("live report reconciles every payment total against veda_acchist_docs.f_clssum", async () => {
  const report = await readJson("reference/aero_trade_660_1_live_mariadb_report_20260624.json");

  for (const block of report.blocks) {
    assertMoney(
      block.totals.paid_total_get_paidsum,
      block.totals.paid_total_acchist_docs,
      `payment control for spec ${block.spec_num}`,
    );
    assert.equal(block.issues.includes("PAYMENT_ROUTINE_VS_DOCS_MISMATCH"), false);
  }
});

test("live report keeps all closing documents as multiline values without +N truncation", async () => {
  const report = await readJson("reference/aero_trade_660_1_live_mariadb_report_20260624.json");
  const specs = blockBySpec(report);
  const spec1063 = specs.get("1063");

  assert.ok(spec1063, "spec 1063 should exist");
  assertMoney(spec1063.totals.paid_total_get_paidsum, 199_375.04);
  assertMoney(spec1063.totals.reimbursable_realization_get_realizsum, 185_181.88);
  assertMoney(spec1063.totals.non_reimbursable_realization_get_realizsum, 41_275.82);
  assertMoney(spec1063.totals.settlement_delta_paid_minus_realization, -27_082.66);

  assert.equal(spec1063.customer_invoices.length, 3);
  assert.equal(spec1063.closing_docs.length, 15);
  assert.ok(spec1063.closing_docs.some((doc) => String(doc.code1c) === "00БП-013350"));
  const collapsedMarker = ["+3", "документов"].join(" ");
  assert.equal(JSON.stringify(spec1063.closing_docs).includes(collapsedMarker), false);
});
