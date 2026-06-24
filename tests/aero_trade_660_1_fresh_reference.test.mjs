import test from "node:test";
import assert from "node:assert/strict";
import { readFile } from "node:fs/promises";

const ROOT = new URL("../", import.meta.url);
const money = (value) => Number(value || 0);
const bySpec = (blocks) => new Map(blocks.map((block) => [String(block.spec_number), block]));

async function readJson(relativePath) {
  return JSON.parse(await readFile(new URL(relativePath, ROOT), "utf8"));
}

function assertMoney(actual, expected, message) {
  assert.equal(Math.round(money(actual) * 100), Math.round(expected * 100), message);
}

test("fresh 660/1 reference uses lower 2025 sheet rows and reconciles block deltas", async () => {
  const reference = await readJson("reference/aero_trade_660_1_fresh_reference.json");
  const blocks = reference.blocks;
  const specs = bySpec(blocks);

  assert.equal(reference.source.sheet_name, "2020 договор 660/1");
  assert.equal(blocks.length, 8);
  assert.deepEqual(blocks.map((block) => block.source_start_row), [
    10401, 10408, 10415, 10422, 10429, 10436, 10443, 10450,
  ]);

  assertMoney(reference.summary.payment_total_rub, 2_268_353.17);
  assertMoney(reference.summary.reimbursable_total_rub, 2_152_392.27);
  assertMoney(reference.summary.non_reimbursable_total_rub, 196_372.85);
  assertMoney(reference.summary.delta_total_rub, -80_411.95);
  assert.equal(blocks.every((block) => money(block.delta_check_diff_rub) === 0), true);

  const spec1063 = specs.get("1063");
  assert.ok(spec1063, "spec 1063 should exist");
  assertMoney(spec1063.payment_total_rub, 174_585.57);
  assertMoney(spec1063.reimbursable_base_rub, 182_090.87);
  assertMoney(spec1063.reimbursable_adjustments_rub, 3_091.01);
  assertMoney(spec1063.reimbursable_rub, 185_181.88);
  assertMoney(spec1063.non_reimbursable_rub, 14_900.65);
  assertMoney(spec1063.delta_rub, -25_496.96);
  assert.match(spec1063.sf_full_text, /доп\. отчет от 14\.08\.2025/);

  const spec1068 = specs.get("1068");
  assert.ok(spec1068.lines.some((line) => line.account === "ВА-009459"));
  assertMoney(spec1068.reimbursable_adjustments_rub, 7_802.00);
  assert.match(spec1068.sf_full_text, /доп\. отчет/);
});

test("fresh 660/1 MariaDB audit proves payment and contractor-expense mapping", async () => {
  const reference = await readJson("reference/aero_trade_660_1_fresh_reference.json");
  const audit = await readJson("reference/aero_trade_660_1_fresh_mariadb_audit.json");
  const specs = bySpec(reference.blocks);

  assert.equal(audit.contract.dog_id, 88);
  assert.equal(audit.contract.client_contact_id, 115);
  assert.equal(audit.contract.client_legal_id, 221);
  assert.equal(audit.mariadb_payments.length, 8);

  const paymentsBySpec = new Map(
    audit.mariadb_payments.map((row) => [String(row.spec_num), money(row.classified_sum)]),
  );
  for (const [specNumber, block] of specs.entries()) {
    assertMoney(paymentsBySpec.get(specNumber), block.payment_total_rub, `payment for ${specNumber}`);
  }

  const contractorOps = audit.mariadb_operation_samples.filter((row) => {
    return ["378518", "378525", "378533"].includes(String(row.operation_id));
  });
  assert.deepEqual(contractorOps.map((row) => String(row.f_isvozm)), ["2", "2", "2"]);
  assert.deepEqual(contractorOps.map((row) => Number(row.operation_sum)), [1980.91, 1333.16, 1685.93]);
  assert.equal(contractorOps.every((row) => String(row.f_invcom || "").toLowerCase().includes("тэо русмарин")), true);

  const settlementTotals = new Map(
    audit.mariadb_customer_settlement_totals_by_spec.map((row) => [String(row.spec_num), row]),
  );
  for (const [specNumber, block] of specs.entries()) {
    const row = settlementTotals.get(specNumber);
    assertMoney(row.reimbursable_amount, block.reimbursable_rub, `reimbursable expenses for ${specNumber}`);
    assertMoney(row.non_reimbursable_amount, block.non_reimbursable_rub, `non-reimbursable expenses for ${specNumber}`);
  }

  const spec1063Adjust = audit.mariadb_akt_detail_allocations.find((row) => row.operation_id === "378519");
  assert.equal(spec1063Adjust.akt_num, "14837");
  assertMoney(spec1063Adjust.allocated_sum, 3_091.01);
  const excluded1063 = settlementTotals.get("1063").excluded_contractor_amount;
  assertMoney(excluded1063, 1_980.91);
});

test("fresh UI fixture keeps readable hierarchy and compact matrix badges", async () => {
  const fixture = await readJson("ui/reference/aero_trade_660_1_fresh.json");
  const contract = fixture.matrix_model.clients[0].root.children[0].children[0];
  const firstSpec = contract.children[0];

  assert.equal(fixture.matrix_model.rowsCount, 25);
  assert.equal(contract.name, "Договор: 660/1");
  assert.match(contract.note, /ERP dog_id=88/);
  assert.match(contract.note, /лист «2020 договор 660\/1»/);
  assert.equal(contract.badges[0].label, "Ост.: 8");
  assert.equal(firstSpec.badges[0].label, "Остаток");
  assert.equal(firstSpec.invoiceLabel.split("\n").length, 3);
});
