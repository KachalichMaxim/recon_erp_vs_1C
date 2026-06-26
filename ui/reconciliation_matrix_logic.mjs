const EPSILON = 0.01;

const text = (value) => String(value ?? "").trim();
const number = (value) => {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
};

const uniq = (values) => [...new Set(values.map(text).filter(Boolean))];

export const REIMBURSEMENT_KIND = Object.freeze({
  REIMBURSABLE: "reimbursable",
  NON_REIMBURSABLE: "non_reimbursable",
  UNKNOWN: "unknown",
});

export const SCHET_KIND = Object.freeze({
  BUYER: "buyer",
  SUPPLIER: "supplier",
  UNKNOWN: "unknown",
});

export function reimbursementKind(value) {
  const numeric = Number(value);
  if (numeric === 1) return REIMBURSEMENT_KIND.REIMBURSABLE;
  if (numeric === 2) return REIMBURSEMENT_KIND.NON_REIMBURSABLE;
  return REIMBURSEMENT_KIND.UNKNOWN;
}

export function schetKind(value) {
  const numeric = Number(value);
  if (numeric === 1) return SCHET_KIND.BUYER;
  if (numeric === 2) return SCHET_KIND.SUPPLIER;
  return SCHET_KIND.UNKNOWN;
}

function operationMatrixAmount(operation) {
  return number(operation.matrixAmount ?? operation.amount ?? operation.sum);
}

function selectOperations({ specId, operations = [] }) {
  const selected = new Map();
  for (const operation of operations) {
    const isDirect = Number(operation.parentType ?? operation.f_parenttype) === 2
      && Number(operation.specId ?? operation.f_specid) === Number(specId);
    const isCategory = Number(operation.parentType ?? operation.f_parenttype) === 4
      && Number(operation.categorySpecId ?? operation.category_spec_id) === Number(specId);
    if (isDirect || isCategory) {
      selected.set(String(operation.id ?? operation.f_id), operation);
    }
  }
  return [...selected.values()];
}

function operationId(operation) {
  return String(operation.id ?? operation.f_id);
}

function invoiceId(invoice) {
  return String(invoice.id ?? invoice.f_id);
}

function invoiceRootId(invoice) {
  return String(invoice.mainInv ?? invoice.f_maininv ?? invoice.id ?? invoice.f_id);
}

function invoiceIsLinkedToOperations(invoice, operationIds) {
  if (operationIds.has(String(invoice.operId ?? invoice.f_operid))) return true;
  return (invoice.detailOpers || invoice.details || []).some((detail) => {
    return operationIds.has(String(detail.operId ?? detail.f_operid));
  });
}

function selectBuyerInvoices({ invoices = [], operationIds }) {
  const byId = new Map(invoices.map((invoice) => [invoiceId(invoice), invoice]));
  const linkedBuyerInvoices = invoices.filter((invoice) => {
    return schetKind(invoice.type ?? invoice.f_type) === SCHET_KIND.BUYER
      && invoiceIsLinkedToOperations(invoice, operationIds);
  });

  const roots = new Map();
  const relatedIds = new Set();
  for (const invoice of linkedBuyerInvoices) {
    const rootId = invoiceRootId(invoice);
    const root = byId.get(rootId) || invoice;
    roots.set(rootId, root);
    relatedIds.add(invoiceId(invoice));
    relatedIds.add(rootId);
  }

  const rows = [...roots.values()].map((invoice) => ({
    id: invoiceId(invoice),
    number: text(invoice.number ?? invoice.f_num ?? invoice.code1c),
    amount: number(invoice.amount ?? invoice.sum ?? invoice.f_sum),
    currency: text(invoice.currency ?? invoice.f_val),
  }));

  return { rows, relatedIds };
}

function paymentLinkIsInScope(payment, invoiceIds, operationIds) {
  const doctype = Number(payment.docType ?? payment.f_doctype);
  if (doctype === 1) return invoiceIds.has(String(payment.docId ?? payment.f_docid));
  if (doctype === 3) return operationIds.has(String(payment.operId ?? payment.f_operid ?? payment.docId ?? payment.f_docid));
  return false;
}

function selectPayments({ payments = [], invoiceIds, operationIds }) {
  const scoped = payments.filter((payment) => paymentLinkIsInScope(payment, invoiceIds, operationIds));
  const byBankDoc = new Map();
  for (const payment of scoped) {
    const key = text(payment.bankPaymentId ?? payment.paymentId ?? payment.id ?? payment.f_id);
    if (!byBankDoc.has(key)) byBankDoc.set(key, []);
    byBankDoc.get(key).push(payment);
  }

  const rows = [];
  for (const group of byBankDoc.values()) {
    const invoiceLinks = group.filter((payment) => Number(payment.docType ?? payment.f_doctype) === 1);
    const selected = invoiceLinks.length ? invoiceLinks : group;
    const seen = new Set();
    for (const payment of selected) {
      const key = [
        payment.docType ?? payment.f_doctype,
        payment.docId ?? payment.f_docid,
        payment.operId ?? payment.f_operid,
        number(payment.classifiedSum ?? payment.f_clssum),
      ].join("|");
      if (seen.has(key)) continue;
      seen.add(key);
      rows.push({
        id: text(payment.id ?? payment.f_id),
        number: text(payment.number ?? payment.ppNumber ?? payment.f_num),
        amount: number(payment.classifiedSum ?? payment.f_clssum),
      });
    }
  }
  return rows;
}

function closingDocIsLinkedToOperations(doc, operationIds) {
  if (operationIds.has(String(doc.operId ?? doc.f_operid))) return true;
  return (doc.detailOpers || doc.details || []).some((detail) => {
    return operationIds.has(String(detail.operId ?? detail.f_operid));
  });
}

function selectClosingDocs({ closingDocs = [], operationIds }) {
  const byId = new Map(closingDocs.map((doc) => [String(doc.id ?? doc.f_id), doc]));
  const selected = new Map();

  for (const doc of closingDocs) {
    if (!closingDocIsLinkedToOperations(doc, operationIds)) continue;
    const rootId = String(doc.mainAkt ?? doc.f_mainakt ?? doc.id ?? doc.f_id);
    const root = byId.get(rootId) || doc;
    selected.set(rootId, root);
  }

  return [...selected.values()].map((doc) => {
    const num = text(doc.sfNumber ?? doc.updNumber ?? doc.number ?? doc.f_num);
    const date = text(doc.date ?? doc.f_date);
    return {
      id: text(doc.id ?? doc.f_id),
      number: date && num ? `${num} от ${date}` : num,
      vatRate: text(doc.vatRate ?? doc.ndsRate ?? doc.f_nds),
    };
  }).filter((doc) => doc.number);
}

function buildStatuses({ unknownOperations, sfDocs, oneCSourceOk, comparisons = [] }) {
  const statuses = [];
  if (unknownOperations.length) statuses.push({
    code: "DATA_ERROR_UNKNOWN_ISVOZM",
    label: `Неизвестный f_isvozm: ${unknownOperations.length}`,
  });
  if (!oneCSourceOk) statuses.push({ code: "SOURCE_ERROR_1C", label: "Ошибка источника 1С" });
  if (oneCSourceOk && sfDocs.length === 0) statuses.push({ code: "NO_SF_UPD", label: "Нет СФ/УПД" });
  for (const comparison of comparisons) {
    if (comparison.status === "AMOUNT_MISMATCH") statuses.push({ code: "AMOUNT_MISMATCH", label: "Расходится сумма" });
    if (comparison.status === "VAT_MISMATCH") statuses.push({ code: "VAT_MISMATCH", label: "Вопрос по НДС" });
    if (comparison.status === "FIELDS_MISMATCH") statuses.push({ code: "FIELDS_MISMATCH", label: "Расходятся реквизиты" });
  }
  return statuses.length ? statuses : [{ code: "OK", label: "ОК" }];
}

function compareMoney(a, b) {
  return Math.abs(number(a) - number(b)) <= EPSILON;
}

export function compareErpOneCDocument(erp, oneC, sourceState = "ok") {
  if (sourceState !== "ok") return { status: "SOURCE_ERROR_1C", fields: [] };
  if (!erp && oneC) return { status: "NOT_FOUND_IN_ERP", fields: [] };
  if (erp && !oneC) return { status: "NOT_FOUND_IN_1C", fields: [] };

  const fields = [];
  if (!compareMoney(erp.amount, oneC.amount)) fields.push("amount");
  if (text(erp.number) !== text(oneC.number)) fields.push("number");
  if (text(erp.date) !== text(oneC.date)) fields.push("date");
  if (text(erp.org) !== text(oneC.org)) fields.push("org");
  if (text(erp.contract) !== text(oneC.contract)) fields.push("contract");

  const erpVat = text(erp.vatRate);
  const oneCVat = text(oneC.vatRate);
  if (erpVat && oneCVat && erpVat !== oneCVat) fields.push("vat");

  if (!fields.length) return { status: "MATCH", fields };
  if (fields.includes("amount")) return { status: "AMOUNT_MISMATCH", fields };
  if (fields.includes("vat")) return { status: "VAT_MISMATCH", fields };
  return { status: "FIELDS_MISMATCH", fields };
}

export function buildMatrixReference(input) {
  const context = input.context || {};
  const operations = selectOperations({ specId: context.specId, operations: input.operations || [] });
  const operationIds = new Set(operations.map(operationId));
  const { rows: invoiceRows, relatedIds: invoiceIds } = selectBuyerInvoices({
    invoices: input.invoices || [],
    operationIds,
  });
  const paymentRows = selectPayments({
    payments: input.payments || [],
    invoiceIds,
    operationIds,
  });
  const sfDocs = selectClosingDocs({
    closingDocs: input.closingDocs || [],
    operationIds,
  });

  const expenses = operations.reduce((acc, operation) => {
    const kind = reimbursementKind(operation.isvozm ?? operation.f_isvozm);
    const amount = operationMatrixAmount(operation);
    if (kind === REIMBURSEMENT_KIND.REIMBURSABLE) acc.reimbursable += amount;
    else if (kind === REIMBURSEMENT_KIND.NON_REIMBURSABLE) acc.nonReimbursable += amount;
    else acc.unknown.push(operation);
    return acc;
  }, { reimbursable: 0, nonReimbursable: 0, unknown: [] });

  const currencies = uniq(invoiceRows.map((row) => row.currency));
  const mixedCurrencies = currencies.length > 1;
  const invoiceSum = mixedCurrencies ? null : invoiceRows.reduce((sum, row) => sum + row.amount, 0);
  const paymentSum = paymentRows.reduce((sum, row) => sum + row.amount, 0);
  const delta = paymentSum - expenses.reimbursable - expenses.nonReimbursable;
  const comparisons = (input.comparisons || []).map(({ erp, oneC, sourceState }) => compareErpOneCDocument(erp, oneC, sourceState));
  const statuses = buildStatuses({
    unknownOperations: expenses.unknown,
    sfDocs,
    oneCSourceOk: input.oneCSourceOk !== false,
    comparisons,
  });
  if (mixedCurrencies) statuses.push({ code: "MIXED_CURRENCIES", label: "Смешанные валюты" });

  const specRow = {
    level: 3,
    type: "spec",
    hierarchy: `Спецификация ${text(context.specNumber)}`,
    specNumber: text(context.specNumber),
    invoices: uniq(invoiceRows.map((row) => row.number)).join("\n"),
    invoiceSum,
    paymentSum,
    reimbursableExpenses: expenses.reimbursable,
    nonReimbursableExpenses: expenses.nonReimbursable,
    sfNumbers: uniq(sfDocs.map((doc) => doc.number)).join("\n"),
    delta,
    statuses,
  };

  const totals = {
    invoiceSum,
    paymentSum,
    reimbursableExpenses: expenses.reimbursable,
    nonReimbursableExpenses: expenses.nonReimbursable,
    delta,
    mixedCurrencies,
  };

  const rows = [
    {
      level: 0,
      type: "client",
      hierarchy: text(context.clientName),
      note: context.clientInn ? `ИНН ${context.clientInn}` : "",
      specNumber: "",
      ...totals,
      statuses,
    },
    {
      level: 1,
      type: "legal",
      hierarchy: text(context.legalName),
      note: context.legalInn ? `ИНН ${context.legalInn}` : "",
      specNumber: "",
      ...totals,
      statuses,
    },
    {
      level: 2,
      type: "contract",
      hierarchy: `Договор ${text(context.contractNumber)}`,
      specNumber: "",
      ...totals,
      statuses,
    },
    specRow,
  ];

  return {
    rows,
    invoiceRows,
    paymentRows,
    sfDocs,
    totals,
    statuses,
    unknownOperations: expenses.unknown,
  };
}

export const MATRIX_COLUMNS = [
  "Иерархия",
  "№ спецификации",
  "Счет",
  "Сумма по счету",
  "Сумма оплаты",
  "Возмещаемые расходы",
  "Невозмещаемые расходы",
  "№ счф",
  "(+/-)",
  "Статус",
];

function rowToFlatObject(row) {
  return {
    "Иерархия": `${"  ".repeat(row.level || 0)}${row.hierarchy}${row.note ? ` (${row.note})` : ""}`,
    "№ спецификации": row.specNumber || "",
    "Счет": row.invoices || "",
    "Сумма по счету": row.invoiceSum ?? "",
    "Сумма оплаты": row.paymentSum ?? "",
    "Возмещаемые расходы": row.reimbursableExpenses ?? "",
    "Невозмещаемые расходы": row.nonReimbursableExpenses ?? "",
    "№ счф": row.sfNumbers || "",
    "(+/-)": row.delta ?? "",
    "Статус": (row.statuses || []).map((status) => status.label).join(", "),
  };
}

export function serializeFlatTsv(rows) {
  const escapeCell = (value) => text(value).replace(/\r?\n/g, " / ").replace(/\t/g, " ");
  const objects = rows.map(rowToFlatObject);
  return [
    MATRIX_COLUMNS.join("\t"),
    ...objects.map((row) => MATRIX_COLUMNS.map((column) => escapeCell(row[column])).join("\t")),
  ].join("\n");
}

export function buildAccountingXlsxLayout(matrix) {
  const specRows = matrix.invoiceRows.length || 1;
  return {
    blockRows: specRows,
    mergedColumns: ["№ спецификации", "Возмещаемые расходы", "Невозмещаемые расходы", "№ счф", "(+/-)"],
    invoiceRows: matrix.invoiceRows,
  };
}
