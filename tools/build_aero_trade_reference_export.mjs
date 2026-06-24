import fs from "node:fs/promises";
import path from "node:path";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const ROOT = "/Users/kachalichmaxim/Desktop/template_for_print";
const API_DIR = path.join(ROOT, "outputs/akt_sverki/aero_trade_api");
const OUT_DIR = path.join(ROOT, "outputs/akt_sverki");
const OUT_XLSX = path.join(OUT_DIR, "AERO_TRADE_660-1_reference_style_export.xlsx");
const RENDER_DIR = path.join(OUT_DIR, "rendered_aero_trade_reference_export");

const SPEC_IDS = [15764, 14994, 16339];

const palette = {
  header: "#D9D7D2",
  section: "#F3F1ED",
  border: "#D6D3CD",
  text: "#1F2933",
  muted: "#6B7280",
  invoice: "#EEF6FF",
  payment: "#EEF8F0",
  expense: "#FFF5DF",
  sf: "#F7F4EF",
  separator: "#FAFAF9",
  green: "#E2F0D9",
  red: "#FCE4D6",
};

const rubFormat = '#,##0.00"р.";[Red]-#,##0.00"р.";0.00"р."';
const usdFormat = '$#,##0.00;[Red]-$#,##0.00;$0.00';
const plainMoneyFormat = '#,##0.00;[Red]-#,##0.00;0.00';

async function readJson(file) {
  return JSON.parse(await fs.readFile(file, "utf8"));
}

function clean(value) {
  if (value === undefined || value === null) return "";
  return String(value).trim();
}

function num(value) {
  const n = Number(value);
  return Number.isFinite(n) ? n : 0;
}

function unique(values) {
  return [...new Set(values.map(clean).filter(Boolean))];
}

function compareDateRu(a, b) {
  return clean(a).localeCompare(clean(b), "ru");
}

function buildPaymentMap(snapshot) {
  const byOper = new Map();
  for (const payment of snapshot.payments || []) {
    if (payment.direction !== "incoming") continue;
    const key = String(payment.oper_id || "");
    if (!byOper.has(key)) byOper.set(key, []);
    byOper.get(key).push(payment);
  }
  return byOper;
}

function buildInvoiceRows(snapshot) {
  const schets = snapshot.schets || [];
  const topInvoices = new Map();
  for (const row of schets) {
    if (num(row.oper_id) === 0) {
      topInvoices.set(String(row.erp_doc_id || clean(row.number)), row);
      topInvoices.set(clean(row.number), row);
    }
  }

  const paymentByOper = buildPaymentMap(snapshot);
  const groups = new Map();
  for (const row of schets.filter((item) => num(item.oper_id) > 0)) {
    const invoiceKey = String(row.invoice_id || clean(row.invoice_number) || row.erp_doc_id);
    if (!groups.has(invoiceKey)) {
      groups.set(invoiceKey, {
        invoiceId: row.invoice_id,
        invoiceNumber: clean(row.invoice_number) || clean(row.number),
        invoiceDate: row.invoice_date || row.date || "",
        operations: [],
        payment: 0,
      });
    }
    const group = groups.get(invoiceKey);
    group.operations.push(row);
    const payments = paymentByOper.get(String(row.oper_id || "")) || [];
    group.payment += payments.reduce((sum, payment) => sum + num(payment.classified_sum), 0);
  }

  const rows = [];
  for (const group of groups.values()) {
    const top = topInvoices.get(String(group.invoiceId)) || topInvoices.get(clean(group.invoiceNumber));
    const invoiceSum = top ? num(top.sum) : group.operations.reduce((sum, row) => sum + num(row.sum), 0);
    const currency = top ? clean(top.currency) : clean(group.operations[0]?.currency);
    const account = clean(top?.number || group.invoiceNumber);
    rows.push({
      account,
      invoiceSum,
      currency,
      payment: group.payment,
      date: top?.date || group.invoiceDate || "",
      operationHint: unique(group.operations.map((row) => `${row.oper_num}. ${row.oper_type_name}`)).join("\n"),
    });
  }

  rows.sort((a, b) => compareDateRu(a.date, b.date) || a.account.localeCompare(b.account, "ru"));

  const operationIdsWithInvoice = new Set(
    schets.filter((item) => num(item.oper_id) > 0).map((item) => String(item.oper_id)),
  );
  const unmappedPayments = [];
  for (const payment of snapshot.payments || []) {
    if (payment.direction !== "incoming") continue;
    if (operationIdsWithInvoice.has(String(payment.oper_id))) continue;
    unmappedPayments.push(payment);
  }
  const unmappedSum = unmappedPayments.reduce((sum, payment) => sum + num(payment.classified_sum), 0);
  if (Math.abs(unmappedSum) >= 0.01) {
    rows.push({
      account: "",
      invoiceSum: "по платежу без счета",
      currency: "",
      payment: unmappedSum,
      date: "",
      operationHint: unique(unmappedPayments.map((payment) => `${payment.pp_number} от ${payment.payment_date}`)).join("\n"),
      isPaymentOnly: true,
    });
  }

  return rows;
}

function buildSfText(snapshot) {
  const labels = [];
  for (const act of snapshot.akts || []) {
    const code = clean(act.main_code1c || act.code1c || act.number);
    if (!code) continue;
    const date = clean(act.main_date || act.date);
    labels.push(date ? `${code} от ${date}` : code);
  }
  return unique(labels).join("\n");
}

function styleHeader(sheet, rangeAddress) {
  const range = sheet.getRange(rangeAddress);
  range.format = {
    fill: palette.header,
    font: { name: "Calibri", size: 10, bold: true, color: palette.text },
    borders: { preset: "all", style: "thin", color: palette.border },
    horizontalAlignment: "center",
    verticalAlignment: "center",
    wrapText: true,
  };
}

function styleBody(sheet, rangeAddress) {
  const range = sheet.getRange(rangeAddress);
  range.format = {
    font: { name: "Calibri", size: 10, color: palette.text },
    borders: { preset: "all", style: "thin", color: palette.border },
    verticalAlignment: "center",
    wrapText: true,
  };
}

function applyMoneyFormat(sheet, row, col, currency) {
  const cell = sheet.getRange(`${col}${row}`);
  if (clean(currency).toLowerCase().includes("usd")) {
    cell.format.numberFormat = usdFormat;
  } else if (clean(currency).toLowerCase().includes("руб") || clean(currency) === "643") {
    cell.format.numberFormat = rubFormat;
  } else {
    cell.format.numberFormat = plainMoneyFormat;
  }
  cell.format.horizontalAlignment = "right";
}

function addMainSheet(workbook, snapshots) {
  const sheet = workbook.worksheets.add("Выгрузка");
  sheet.getRange("A1:K1").values = [[
    "№ спецификации",
    "",
    "Счет",
    "Сумма по счету",
    "Сумма оплаты",
    "",
    "Возмещаемые расходы",
    "Невозмещаемые расходы",
    "",
    "№ счф",
    "(+/-)",
  ]];
  styleHeader(sheet, "A1:K1");

  let row = 2;
  const specBlocks = [];
  for (const snapshot of snapshots) {
    const d = snapshot.delivery || {};
    const st = snapshot.settlements || {};
    const invoiceRows = buildInvoiceRows(snapshot);
    const sfText = buildSfText(snapshot);
    const sfLineCount = Math.max(1, sfText.split("\n").filter(Boolean).length);
    const blockStart = row;
    const blockEnd = row + Math.max(invoiceRows.length, 1) - 1;
    specBlocks.push({ start: blockStart, end: blockEnd });

    if (!invoiceRows.length) {
      invoiceRows.push({
        account: "",
        invoiceSum: "",
        currency: "",
        payment: 0,
        operationHint: "",
      });
    }

    const values = invoiceRows.map((invoice, idx) => [
      idx === 0 ? `Спецификация ${d.spec_number}` : "",
      "",
      invoice.account,
      invoice.invoiceSum,
      invoice.payment || "",
      "",
      idx === 0 ? num(st.control_reimbursable_total) : "",
      idx === 0 ? num(st.control_non_reimbursable_total) : "",
      "",
      idx === 0 ? sfText : "",
      "",
    ]);
    sheet.getRange(`A${blockStart}:K${blockEnd}`).values = values;
    styleBody(sheet, `A${blockStart}:K${blockEnd}`);

    sheet.getRange(`K${blockStart}`).formulas = [[`=SUM(E${blockStart}:E${blockEnd})-G${blockStart}-H${blockStart}`]];
    sheet.getRange(`A${blockStart}:A${blockEnd}`).format.fill = palette.section;
    sheet.getRange(`B${blockStart}:B${blockEnd}`).format.fill = palette.separator;
    sheet.getRange(`C${blockStart}:D${blockEnd}`).format.fill = palette.invoice;
    sheet.getRange(`E${blockStart}:E${blockEnd}`).format.fill = palette.payment;
    sheet.getRange(`F${blockStart}:F${blockEnd}`).format.fill = palette.separator;
    sheet.getRange(`G${blockStart}:H${blockEnd}`).format.fill = palette.expense;
    sheet.getRange(`I${blockStart}:I${blockEnd}`).format.fill = palette.separator;
    sheet.getRange(`J${blockStart}:J${blockEnd}`).format.fill = palette.sf;
    const deltaFill = num(st.customer_settlement_balance_rub) === 0 ? palette.green : palette.red;
    sheet.getRange(`K${blockStart}:K${blockEnd}`).format.fill = deltaFill;
    sheet.getRange(`A${blockStart}`).format.font = { bold: true, color: palette.text };
    sheet.getRange(`J${blockStart}`).format.wrapText = true;
    if (blockEnd > blockStart) {
      sheet.getRange(`A${blockStart}:A${blockEnd}`).merge();
      sheet.getRange(`G${blockStart}:G${blockEnd}`).merge();
      sheet.getRange(`H${blockStart}:H${blockEnd}`).merge();
      sheet.getRange(`J${blockStart}:J${blockEnd}`).merge();
      sheet.getRange(`K${blockStart}:K${blockEnd}`).merge();
    }
    sheet.getRange(`A${blockStart}:A${blockEnd}`).format.verticalAlignment = "center";
    sheet.getRange(`G${blockStart}:H${blockEnd}`).format.verticalAlignment = "center";
    sheet.getRange(`J${blockStart}:K${blockEnd}`).format.verticalAlignment = "center";
    sheet.getRange(`A${blockStart}:K${blockEnd}`).format.rowHeightPx = Math.max(
      34,
      Math.ceil((sfLineCount * 19 + 14) / Math.max(invoiceRows.length, 1)),
    );

    for (let i = 0; i < invoiceRows.length; i++) {
      const currentRow = blockStart + i;
      applyMoneyFormat(sheet, currentRow, "D", invoiceRows[i].currency);
      applyMoneyFormat(sheet, currentRow, "E", "руб.");
      if (i === 0) {
        applyMoneyFormat(sheet, currentRow, "G", "руб.");
        applyMoneyFormat(sheet, currentRow, "H", "руб.");
        applyMoneyFormat(sheet, currentRow, "K", "руб.");
      }
    }

    row = blockEnd + 2;
  }

  const totalRow = row;
  sheet.getRange(`A${totalRow}:K${totalRow}`).values = [[
    "Итого по договору 660/1",
    "",
    "",
    "",
    "",
    "",
    "",
    "",
    "",
    "",
    "",
  ]];
  styleBody(sheet, `A${totalRow}:K${totalRow}`);
  sheet.getRange(`A${totalRow}:K${totalRow}`).format.fill = palette.header;
  sheet.getRange(`E${totalRow}`).format.fill = palette.payment;
  sheet.getRange(`G${totalRow}:H${totalRow}`).format.fill = palette.expense;
  sheet.getRange(`K${totalRow}`).format.fill = palette.red;
  sheet.getRange(`A${totalRow}`).format.font = { bold: true, color: palette.text };

  const sumRanges = (col) => specBlocks.map((block) => `${col}${block.start}:${col}${block.end}`).join(",");
  const firstRows = (col) => specBlocks.map((block) => `${col}${block.start}`).join(",");
  sheet.getRange(`E${totalRow}`).formulas = [[`=SUM(${sumRanges("E")})`]];
  sheet.getRange(`G${totalRow}`).formulas = [[`=SUM(${firstRows("G")})`]];
  sheet.getRange(`H${totalRow}`).formulas = [[`=SUM(${firstRows("H")})`]];
  sheet.getRange(`K${totalRow}`).formulas = [[`=E${totalRow}-G${totalRow}-H${totalRow}`]];
  for (const col of ["E", "G", "H", "K"]) applyMoneyFormat(sheet, totalRow, col, "руб.");

  for (const targetRow of [...specBlocks.map((block) => block.start), totalRow]) {
    sheet.getRange(`K${targetRow}`).conditionalFormats.addCellIs({
      operator: "equal",
      formula: 0,
      format: { fill: palette.green, font: { bold: true, color: "#0F5132" } },
    });
    sheet.getRange(`K${targetRow}`).conditionalFormats.addCellIs({
      operator: "notEqual",
      formula: 0,
      format: { fill: palette.red, font: { bold: true, color: "#842029" } },
    });
  }

  sheet.freezePanes.freezeRows(1);
  sheet.getRange("A:A").format.columnWidthPx = 170;
  sheet.getRange("B:B").format.columnWidthPx = 18;
  sheet.getRange("C:C").format.columnWidthPx = 115;
  sheet.getRange("D:E").format.columnWidthPx = 135;
  sheet.getRange("F:F").format.columnWidthPx = 18;
  sheet.getRange("G:H").format.columnWidthPx = 160;
  sheet.getRange("I:I").format.columnWidthPx = 18;
  sheet.getRange("J:J").format.columnWidthPx = 310;
  sheet.getRange("K:K").format.columnWidthPx = 120;
  sheet.getRange(`A1:K${totalRow}`).format.verticalAlignment = "center";
  sheet.getRange(`C2:C${totalRow}`).format.horizontalAlignment = "center";
  sheet.getRange(`D2:H${totalRow}`).format.horizontalAlignment = "right";
}

function addRulesSheet(workbook) {
  const sheet = workbook.worksheets.add("Правила");
  sheet.getRange("A1:E1").values = [["Колонка выгрузки", "Откуда берем в ERP", "Select-шаблон", "Как считаем", "Контроль с 1С"]];
  styleHeader(sheet, "A1:E1");
  const rows = [
    [
      "№ спецификации",
      "veda_specs + veda_dogs + veda_clients/veda_contacts",
      "SELECT s.f_id, s.f_num, d.f_dogname, legal.f_name AS legal_name, contact.f_name AS client_name\nFROM veda_specs s\nJOIN veda_dogs d ON d.f_id = s.f_dogid\nJOIN veda_clients legal ON legal.f_id = d.f_contrid\nLEFT JOIN veda_contacts contact ON contact.f_id = legal.f_contactid\nWHERE s.f_id = :spec_id;",
      "Один блок строк на одну поставку/спецификацию. Клиент = veda_contacts, ЮЛ = veda_clients.",
      "Договор-заявка в 1С ищется по кодам veda_specs.f_kod1cb/f_kod1cp и номеру спецификации.",
    ],
    [
      "Счет",
      "veda_schets",
      "SELECT main.f_num AS account_num, main.f_dt, main.f_sum, main.f_val,\n       line.f_operid, line.f_num AS line_num\nFROM veda_schets line\nLEFT JOIN veda_schets main ON main.f_id = line.f_maininv\nWHERE line.f_operid IN (SELECT si.f_id FROM veda_spec_invoices si WHERE si.f_specid = :spec_id)\nORDER BY main.f_dt, main.f_num, line.f_num;",
      "В выгрузке показываем агрегирующий счет покупателю; операции внутри счета суммируются под ним.",
      "Сверяется с 1С счетом по номеру, дате, сумме и договору-заявке.",
    ],
    [
      "Сумма по счету",
      "veda_schets.f_sum + veda_schets.f_val",
      "SELECT main.f_num, main.f_sum, main.f_val\nFROM veda_schets main\nWHERE main.f_id IN (SELECT DISTINCT line.f_maininv FROM veda_schets line WHERE line.f_operid IN (...));",
      "Сумма исходного счета в валюте счета; USD остается USD, рублевые оплаты считаются отдельно.",
      "В 1С сверяется сумма документа; рублевое сальдо идет через ERP-расчет оплат/расходов.",
    ],
    [
      "Сумма оплаты",
      "veda_acchist + veda_acchist_docs",
      "SELECT ah.f_id, ah.f_ppnum, COALESCE(ah.f_dt1c, ah.f_ppdt) AS pay_dt,\n       ahd.f_docid AS oper_id, ahd.f_clssum\nFROM veda_acchist_docs ahd\nJOIN veda_acchist ah ON ah.f_id = ahd.f_acchistid\nWHERE ahd.f_doctype = 3\n  AND ah.f_type = 0\n  AND ahd.f_docid IN (SELECT si.f_id FROM veda_spec_invoices si WHERE si.f_specid = :spec_id);",
      "По каждому счету суммируем только доли входящих оплат клиента по oper_id операций, входящих в счет.",
      "Сверяется с 1С поступлением на расчетный счет и расшифровкой платежа.",
    ],
    [
      "Возмещаемые расходы",
      "veda_spec_invoices + routines get_expensessum",
      "SELECT SUM(get_expensessum(si.f_id)) AS reimbursable_total\nFROM veda_spec_invoices si\nWHERE si.f_specid = :spec_id\n  AND si.f_isvozm = 1\n  AND <исключения текущей ERP-логики>;",
      "Агрегат по спецификации; в форме объединяется по высоте блока счетов.",
      "Подтверждается актами/поступлениями 1С по возмещаемым операциям.",
    ],
    [
      "Невозмещаемые расходы",
      "veda_spec_invoices + routines get_realizsum/get_profit",
      "SELECT SUM(CASE\n         WHEN si.f_isvozm <> 1\n          AND NOT (get_expensessum(si.f_id) > 0 AND get_profit(si.f_id) < 0)\n         THEN get_profit(si.f_id)\n         ELSE 0 END) AS non_reimbursable_total\nFROM veda_spec_invoices si\nWHERE si.f_specid = :spec_id;",
      "Агрегат по спецификации; исключаем расходные техоперации, которые не являются клиентским взаиморасчетом.",
      "Подтверждается реализацией/актами 1С по агентскому вознаграждению.",
    ],
    [
      "№ счф",
      "veda_akts + veda_akts_details + veda_akts_details_opers",
      "SELECT a.f_kod1c, COALESCE(a.f_dt1c, a.f_dt) AS akt_dt,\n       COALESCE(ado.f_operid, a.f_operid) AS oper_id\nFROM veda_akts a\nLEFT JOIN veda_akts_details ad ON ad.f_aktid = a.f_id\nLEFT JOIN veda_akts_details_opers ado ON ado.f_detailid = ad.f_id\nWHERE COALESCE(ado.f_operid, a.f_operid) IN (SELECT si.f_id FROM veda_spec_invoices si WHERE si.f_specid = :spec_id);",
      "Выводим список связанных актов/УПД/счф, которые участвуют в блоке поставки.",
      "Документы должны существовать в 1С и совпадать по ключевым полям.",
    ],
    [
      "(+/-)",
      "формула Excel",
      "SUM(E<начало блока>:E<конец блока>) - G<начало блока> - H<начало блока>",
      "Ноль означает закрытый блок по выбранной логике сверки; неноль — бухгалтерское сальдо/разрыв.",
      "Сверяется с расчетным сальдо по поставке в ERP и 1С.",
    ],
  ];
  sheet.getRange(`A2:E${rows.length + 1}`).values = rows;
  styleBody(sheet, `A2:E${rows.length + 1}`);
  sheet.freezePanes.freezeRows(1);
  sheet.getRange("A:A").format.columnWidthPx = 150;
  sheet.getRange("B:B").format.columnWidthPx = 230;
  sheet.getRange("C:C").format.columnWidthPx = 440;
  sheet.getRange("D:E").format.columnWidthPx = 260;
  sheet.getRange("A:E").format.wrapText = true;
}

async function build() {
  await fs.mkdir(OUT_DIR, { recursive: true });
  await fs.mkdir(RENDER_DIR, { recursive: true });

  const snapshots = [];
  for (const specId of SPEC_IDS) {
    snapshots.push(await readJson(path.join(API_DIR, `${specId}_erp-snapshot.json`)));
  }

  const workbook = Workbook.create();
  addMainSheet(workbook, snapshots);
  addRulesSheet(workbook);

  const mainInspect = await workbook.inspect({
    kind: "table",
    range: "Выгрузка!A1:K18",
    include: "values,formulas",
    tableMaxRows: 18,
    tableMaxCols: 11,
  });
  console.log(mainInspect.ndjson);

  const errors = await workbook.inspect({
    kind: "match",
    searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
    options: { useRegex: true, maxResults: 200 },
    summary: "formula error scan",
  });
  console.log(errors.ndjson);

  for (const sheetName of ["Выгрузка", "Правила"]) {
    const blob = await workbook.render({ sheetName, range: "A1:N32", format: "png", scale: 1.25 });
    await fs.writeFile(path.join(RENDER_DIR, `${sheetName}.png`), Buffer.from(await blob.arrayBuffer()));
  }

  const output = await SpreadsheetFile.exportXlsx(workbook);
  await output.save(OUT_XLSX);
  console.log(OUT_XLSX);
}

await build();
