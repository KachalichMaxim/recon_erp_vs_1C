import test from "node:test";
import assert from "node:assert/strict";
import {
  buildAccountingXlsxLayout,
  buildMatrixReference,
  compareErpOneCDocument,
  reimbursementKind,
  REIMBURSEMENT_KIND,
  schetKind,
  SCHET_KIND,
  serializeFlatTsv,
} from "../ui/reconciliation_matrix_logic.mjs";

function baseCase(overrides = {}) {
  return {
    context: {
      specId: 20334,
      specNumber: "1051",
      clientName: "АЭРО-ТРЕЙД",
      clientInn: "7811451960",
      legalName: "ООО \"АЭРО-ТРЕЙД\"",
      legalInn: "7811451960",
      contractNumber: "660/1",
    },
    operations: [
      { id: 378518, parentType: 2, specId: 20334, isvozm: 1, amount: 1980.91 },
      { id: 378525, parentType: 2, specId: 20334, isvozm: 2, amount: 1333.16 },
      { id: 378533, parentType: 4, categorySpecId: 20334, isvozm: 1, amount: 1685.93 },
      { id: 999001, parentType: 2, specId: 20334, isvozm: 0, amount: 777.77 },
      { id: 777777, parentType: 2, specId: 99999, isvozm: 1, amount: 999999 },
    ],
    invoices: [
      { id: 501, number: "ВА-012610", type: 1, operId: 378518, amount: 1000, currency: "RUB" },
      { id: 502, number: "ВА-012611", type: 1, mainInv: 501, operId: 378525, amount: 600, currency: "RUB" },
      { id: 503, number: "ВА-012612", type: 2, operId: 378533, amount: 700, currency: "RUB" },
      { id: 504, number: "ВА-012613", type: 1, amount: 300, currency: "RUB", detailOpers: [{ operId: 378533 }] },
    ],
    payments: [
      { id: 701, paymentId: "PP-1", number: "ПП-1", docType: 1, docId: 501, classifiedSum: 1000 },
      { id: 702, paymentId: "PP-1", number: "ПП-1", docType: 3, operId: 378518, classifiedSum: 1000 },
      { id: 703, paymentId: "PP-2", number: "ПП-2", docType: 3, operId: 378525, classifiedSum: 333.16 },
      { id: 704, paymentId: "PP-3", number: "ПП-3", docType: 3, operId: 777777, classifiedSum: 999999 },
    ],
    closingDocs: [
      { id: 801, number: "00БП-001542", date: "10.01.2025", operId: 378518, vatRate: "0%" },
      { id: 802, number: "00БП-023423", date: "19.12.2024", detailOpers: [{ operId: 378525 }], vatRate: "0%" },
      { id: 803, number: "00БП-000196", date: "22.01.2025", operId: 378533, mainAkt: 804, vatRate: "0%" },
      { id: 804, number: "00БП-000197", date: "22.01.2025", vatRate: "0%" },
    ],
    oneCSourceOk: true,
    ...overrides,
  };
}

test("C11-C13: f_isvozm is classified only by numeric ERP value", () => {
  assert.equal(reimbursementKind(1), REIMBURSEMENT_KIND.REIMBURSABLE);
  assert.equal(reimbursementKind("1"), REIMBURSEMENT_KIND.REIMBURSABLE);
  assert.equal(reimbursementKind(2), REIMBURSEMENT_KIND.NON_REIMBURSABLE);
  assert.equal(reimbursementKind("Возмещаемые расходы"), REIMBURSEMENT_KIND.UNKNOWN);
  assert.equal(reimbursementKind(0), REIMBURSEMENT_KIND.UNKNOWN);
});

test("C01-C04: buyer invoices are included once, supplier invoices are excluded", () => {
  assert.equal(schetKind(1), SCHET_KIND.BUYER);
  assert.equal(schetKind(2), SCHET_KIND.SUPPLIER);

  const matrix = buildMatrixReference(baseCase());
  assert.deepEqual(matrix.invoiceRows.map((row) => row.number), ["ВА-012610", "ВА-012613"]);
  assert.equal(matrix.totals.invoiceSum, 1300);
  assert.equal(matrix.invoiceRows.some((row) => row.number === "ВА-012612"), false);
});

test("C05-C08: payments use f_clssum and do not double-count invoice plus operation links", () => {
  const matrix = buildMatrixReference(baseCase());
  assert.deepEqual(matrix.paymentRows.map((row) => row.number), ["ПП-1", "ПП-2"]);
  assert.equal(matrix.totals.paymentSum, 1333.16);
});

test("C09-C13: direct and f_parenttype=4 operations are included, unknown f_isvozm is excluded from sums", () => {
  const matrix = buildMatrixReference(baseCase());
  assert.equal(matrix.totals.reimbursableExpenses, 3666.84);
  assert.equal(matrix.totals.nonReimbursableExpenses, 1333.16);
  assert.equal(matrix.unknownOperations.length, 1);
  assert.equal(matrix.statuses.some((status) => status.code === "DATA_ERROR_UNKNOWN_ISVOZM"), true);
});

test("C14-C17: closing documents are found through direct, detail and main document links without +N truncation", () => {
  const matrix = buildMatrixReference(baseCase());
  const specRow = matrix.rows.find((row) => row.type === "spec");

  assert.equal(matrix.sfDocs.length, 3);
  assert.match(specRow.sfNumbers, /00БП-001542 от 10\.01\.2025/);
  assert.match(specRow.sfNumbers, /00БП-023423 от 19\.12\.2024/);
  assert.match(specRow.sfNumbers, /00БП-000197 от 22\.01\.2025/);
  assert.equal(specRow.sfNumbers.includes("+"), false);
  assert.equal(specRow.sfNumbers.split("\n").length, 3);
});

test("C18: matrix hierarchy keeps client, legal entity, contract and specification levels", () => {
  const matrix = buildMatrixReference(baseCase());
  assert.deepEqual(matrix.rows.map((row) => row.type), ["client", "legal", "contract", "spec"]);
  assert.deepEqual(matrix.rows.map((row) => row.level), [0, 1, 2, 3]);
  assert.equal(matrix.rows[0].hierarchy, "АЭРО-ТРЕЙД");
  assert.equal(matrix.rows[3].specNumber, "1051");
});

test("C23-C25: 1C comparison separates amount, VAT, field and source errors", () => {
  const baseErp = { number: "А-1", date: "2025-01-10", org: "ООО А", contract: "660/1", amount: 100, vatRate: "20%" };

  assert.equal(compareErpOneCDocument(baseErp, { ...baseErp, amount: 99 }).status, "AMOUNT_MISMATCH");
  assert.equal(compareErpOneCDocument(baseErp, { ...baseErp, vatRate: "0%" }).status, "VAT_MISMATCH");
  assert.equal(compareErpOneCDocument(baseErp, { ...baseErp, date: "2025-01-11" }).status, "FIELDS_MISMATCH");
  assert.equal(compareErpOneCDocument(baseErp, null, "error").status, "SOURCE_ERROR_1C");
});

test("C26: mixed invoice currencies are not summed into one invoice total", () => {
  const data = baseCase({
    invoices: [
      { id: 501, number: "USD-1", type: 1, operId: 378518, amount: 100, currency: "USD" },
      { id: 502, number: "RUB-1", type: 1, operId: 378525, amount: 1000, currency: "RUB" },
    ],
  });
  const matrix = buildMatrixReference(data);

  assert.equal(matrix.totals.invoiceSum, null);
  assert.equal(matrix.totals.mixedCurrencies, true);
  assert.equal(matrix.statuses.some((status) => status.code === "MIXED_CURRENCIES"), true);
});

test("C27: accounting XLSX layout keeps required merged columns for one specification block", () => {
  const matrix = buildMatrixReference(baseCase());
  const layout = buildAccountingXlsxLayout(matrix);

  assert.equal(layout.blockRows, 2);
  assert.deepEqual(layout.mergedColumns, ["№ спецификации", "Возмещаемые расходы", "Невозмещаемые расходы", "№ счф", "(+/-)"]);
});

test("C28: copy output is flat TSV and repeats merged values per visible row", () => {
  const matrix = buildMatrixReference(baseCase());
  const tsv = serializeFlatTsv(matrix.rows);
  const lines = tsv.split("\n");

  assert.equal(lines.length, 5);
  assert.match(lines[0], /^Иерархия\t№ спецификации\tСчет/);
  assert.match(lines[4], /1051\tВА-012610 \/ ВА-012613\t1300\t1333\.16/);
  assert.equal(lines[4].includes("\n00БП"), false);
  assert.match(lines[4], /00БП-001542 от 10\.01\.2025 \/ 00БП-023423 от 19\.12\.2024 \/ 00БП-000197 от 22\.01\.2025/);
});
