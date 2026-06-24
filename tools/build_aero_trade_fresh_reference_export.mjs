import fs from "node:fs/promises";
import path from "node:path";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const ROOT = process.cwd();
const REF_JSON = path.join(ROOT, "reference/aero_trade_660_1_fresh_reference.json");
const AUDIT_JSON = path.join(ROOT, "reference/aero_trade_660_1_fresh_mariadb_audit.json");
const OUT_XLSX = path.join(ROOT, "exports/AERO_TRADE_660-1_fresh_reference_export.xlsx");
const OUT_SCREEN_DIR = path.join(ROOT, "screenshots/excel");

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
const plainMoneyFormat = "#,##0.00;[Red]-#,##0.00;0.00";

async function readJson(file) {
  return JSON.parse(await fs.readFile(file, "utf8"));
}

function clean(value) {
  return String(value ?? "").replace(/\s+/g, " ").trim();
}

function multiline(value) {
  return String(value ?? "")
    .replace(/\r\n/g, "\n")
    .replace(/\r/g, "\n")
    .split("\n")
    .map(clean)
    .filter(Boolean)
    .join("\n");
}

function num(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed) ? parsed : 0;
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

function applyNumberFormat(sheet, row, col, format = rubFormat) {
  const cell = sheet.getRange(`${col}${row}`);
  cell.format.numberFormat = format;
  cell.format.horizontalAlignment = "right";
}

function accountRows(block) {
  return block.lines.length ? block.lines : [{
    account: "",
    invoice_amount: null,
    invoice_sum_text: "",
    payment_amount_rub: null,
  }];
}

function addExportSheet(workbook, reference) {
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
  const blocks = [];
  for (const block of reference.blocks) {
    const lines = accountRows(block);
    const start = row;
    const end = row + lines.length - 1;
    blocks.push({ start, end });

    sheet.getRange(`A${start}:K${end}`).values = lines.map((line, index) => [
      index === 0 ? block.label : "",
      "",
      clean(line.account),
      line.invoice_amount ?? clean(line.invoice_sum_text),
      line.payment_amount_rub ?? "",
      "",
      index === 0 ? num(block.reimbursable_rub) : "",
      index === 0 ? num(block.non_reimbursable_rub) : "",
      "",
      index === 0 ? multiline(block.sf_full_text || block.sf_text) : "",
      "",
    ]);
    styleBody(sheet, `A${start}:K${end}`);
    sheet.getRange(`K${start}`).formulas = [[`=SUM(E${start}:E${end})-G${start}-H${start}`]];

    sheet.getRange(`A${start}:A${end}`).format.fill = palette.section;
    sheet.getRange(`B${start}:B${end}`).format.fill = palette.separator;
    sheet.getRange(`C${start}:D${end}`).format.fill = palette.invoice;
    sheet.getRange(`E${start}:E${end}`).format.fill = palette.payment;
    sheet.getRange(`F${start}:F${end}`).format.fill = palette.separator;
    sheet.getRange(`G${start}:H${end}`).format.fill = palette.expense;
    sheet.getRange(`I${start}:I${end}`).format.fill = palette.separator;
    sheet.getRange(`J${start}:J${end}`).format.fill = palette.sf;
    sheet.getRange(`K${start}:K${end}`).format.fill = Math.abs(num(block.delta_rub)) <= 0.01 ? palette.green : palette.red;

    if (end > start) {
      for (const col of ["A", "G", "H", "J", "K"]) {
        sheet.getRange(`${col}${start}:${col}${end}`).merge();
      }
    }
    sheet.getRange(`A${start}`).format.font = { bold: true, color: palette.text };
    sheet.getRange(`A${start}:K${end}`).format.verticalAlignment = "center";
    sheet.getRange(`J${start}`).format.wrapText = true;
    sheet.getRange(`J${start}`).format.rowHeightPx = Math.max(48, multiline(block.sf_full_text).split("\n").length * 20 + 18);

    for (let current = start; current <= end; current += 1) {
      applyNumberFormat(sheet, current, "D", plainMoneyFormat);
      applyNumberFormat(sheet, current, "E");
    }
    for (const col of ["G", "H", "K"]) applyNumberFormat(sheet, start, col);

    row = end + 1;
  }

  const totalRow = row + 1;
  sheet.getRange(`A${totalRow}:K${totalRow}`).values = [["Итого по фрагменту 10401-10450", "", "", "", "", "", "", "", "", "", ""]];
  styleBody(sheet, `A${totalRow}:K${totalRow}`);
  sheet.getRange(`A${totalRow}:K${totalRow}`).format.fill = palette.header;
  const ranges = (col) => blocks.map((block) => `${col}${block.start}:${col}${block.end}`).join(",");
  const firstRows = (col) => blocks.map((block) => `${col}${block.start}`).join(",");
  sheet.getRange(`E${totalRow}`).formulas = [[`=SUM(${ranges("E")})`]];
  sheet.getRange(`G${totalRow}`).formulas = [[`=SUM(${firstRows("G")})`]];
  sheet.getRange(`H${totalRow}`).formulas = [[`=SUM(${firstRows("H")})`]];
  sheet.getRange(`K${totalRow}`).formulas = [[`=E${totalRow}-G${totalRow}-H${totalRow}`]];
  for (const col of ["E", "G", "H", "K"]) applyNumberFormat(sheet, totalRow, col);
  sheet.getRange(`A${totalRow}`).format.font = { bold: true, color: palette.text };

  sheet.freezePanes.freezeRows(1);
  sheet.getRange("A:A").format.columnWidthPx = 175;
  sheet.getRange("B:B").format.columnWidthPx = 18;
  sheet.getRange("C:C").format.columnWidthPx = 120;
  sheet.getRange("D:E").format.columnWidthPx = 130;
  sheet.getRange("F:F").format.columnWidthPx = 18;
  sheet.getRange("G:H").format.columnWidthPx = 155;
  sheet.getRange("I:I").format.columnWidthPx = 18;
  sheet.getRange("J:J").format.columnWidthPx = 285;
  sheet.getRange("K:K").format.columnWidthPx = 120;
  sheet.getRange(`C2:C${totalRow}`).format.horizontalAlignment = "center";
  sheet.getRange(`D2:H${totalRow}`).format.horizontalAlignment = "right";
  return totalRow;
}

function addRulesSheet(workbook, audit) {
  const sheet = workbook.worksheets.add("Правила");
  sheet.getRange("A1:E1").values = [["Колонка", "ERP-источник", "SQL/связь", "Правило", "Проверка на свежем фрагменте"]];
  styleHeader(sheet, "A1:E1");
  const rows = [
    [
      "Иерархия",
      "veda_contacts -> veda_clients -> veda_dogs -> veda_specs",
      "veda_contacts.f_id = veda_clients.f_contactid; veda_clients.f_id = veda_dogs.f_contrid; veda_dogs.f_id = veda_specs.f_dogid",
      "Договор 660/1: contact_id=115, legal_id=221, dog_id=88. Фильтра по году нет.",
      `${audit.mariadb_specs.length} поставок в контрольном фрагменте.`,
    ],
    [
      "Счет / сумма по счету",
      "veda_schets",
      "Основной счет покупателю: main = IF(child.f_maininv>0, child.f_maininv, child.f_id), main.f_type=1, child.f_operid IN operations.",
      "Показываем счета покупателю построчно. Счета поставщиков f_type=2 не выводятся в клиентскую колонку.",
      `${audit.mariadb_invoices.length} счетов покупателю подтверждены MariaDB.`,
    ],
    [
      "Сумма оплаты",
      "veda_acchist_docs + veda_acchist",
      "ahd.f_doctype=3 AND ahd.f_docid=operation_id; сумма = SUM(ahd.f_clssum); ah.f_type=0.",
      "Используется распределенная сумма по операции, не полная сумма банковского документа.",
      "Платежи 10401-10450 сходятся с референсом по f_clssum.",
    ],
    [
      "Возмещаемые расходы",
      "veda_akts + veda_akts_details_opers",
      "Прямые акты veda_akts.f_operid и распределения veda_akts_details_opers.f_sum по операциям с f_isvozm=1.",
      "В матрице показываем итог клиентского блока. Доп. отчеты являются отдельными актами/распределениями ERP, а не внешними письмами.",
      "Для 1063: прямые/распределенные ERP-строки дают 185181.88; дельта сходится.",
    ],
    [
      "Невозмещаемые расходы",
      "veda_akts",
      "Прямые клиентские акты veda_akts.f_operid по операциям с f_isvozm=2.",
      "Подрядные распределенные строки veda_akts_details_opers с f_isvozm=2 не включаются в клиентскую колонку.",
      "Для 1063: 14900.65 включено; 1980.91 по подрядчику ТЭО Русмарин исключено и логируется.",
    ],
    [
      "Исключенные подрядные расходы",
      "veda_akts_details_opers",
      "ado.f_operid -> veda_spec_invoices.f_id, где f_isvozm=2 и сумма пришла из распределенной строки акта подрядчика.",
      "Не участвуют в (+/-) клиента, но сохраняются для анализа расхождений и частоты проблем.",
      "1063/1064/1065: исключены 1980.91 / 1333.16 / 1685.93.",
    ],
    [
      "№ счф",
      "Референс + veda_akts/1C",
      "В промышленной версии требуется связать с veda_akts/1C и вывести все документы через перенос строки.",
      "Основной УПД/акт и доп. отчет выводятся полностью внутри объединенной ячейки.",
      "В 1063-1065 видны 00БП-003266/003274/003279 и доп. отчет от 14.08.2025.",
    ],
    [
      "(+/-)",
      "серверная формула",
      "SUM(Сумма оплаты по строкам блока) - Возмещаемые расходы - Невозмещаемые расходы.",
      "Значение считается, а не копируется слепо из Google. Доп. отчеты должны входить в расходы.",
      "После учета доп. отчетов delta_check_diff=0 для всех 8 блоков.",
    ],
  ];
  sheet.getRange(`A2:E${rows.length + 1}`).values = rows;
  styleBody(sheet, `A2:E${rows.length + 1}`);
  sheet.freezePanes.freezeRows(1);
  sheet.getRange("A:A").format.columnWidthPx = 150;
  sheet.getRange("B:B").format.columnWidthPx = 250;
  sheet.getRange("C:C").format.columnWidthPx = 380;
  sheet.getRange("D:E").format.columnWidthPx = 285;
  sheet.getRange("A:E").format.wrapText = true;
}

async function build() {
  await fs.mkdir(path.dirname(OUT_XLSX), { recursive: true });
  await fs.mkdir(OUT_SCREEN_DIR, { recursive: true });
  const reference = await readJson(REF_JSON);
  const audit = await readJson(AUDIT_JSON);
  const workbook = Workbook.create();
  const totalRow = addExportSheet(workbook, reference);
  addRulesSheet(workbook, audit);

  const scan = await workbook.inspect({
    kind: "match",
    searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
    options: { useRegex: true, maxResults: 50 },
    summary: "formula error scan",
  });
  console.log(scan.ndjson);

  for (const [sheetName, fileName] of [["Выгрузка", "aero_trade_660_1_fresh_export.png"], ["Правила", "aero_trade_660_1_fresh_rules.png"]]) {
    const blob = await workbook.render({ sheetName, range: sheetName === "Выгрузка" ? `A1:K${totalRow}` : "A1:E9", format: "png", scale: 1.25 });
    await fs.writeFile(path.join(OUT_SCREEN_DIR, fileName), Buffer.from(await blob.arrayBuffer()));
  }

  const output = await SpreadsheetFile.exportXlsx(workbook);
  await output.save(OUT_XLSX);
  console.log(OUT_XLSX);
}

await build();
