import fs from "node:fs/promises";
import { fileURLToPath } from "node:url";
import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const ROOT = new URL("../", import.meta.url);
const XLSX = fileURLToPath(new URL("exports/AERO_TRADE_660-1_live_mariadb_reconciliation_20260624.xlsx", ROOT));
const OUT_DIR = fileURLToPath(new URL("screenshots/excel/", ROOT));

await fs.mkdir(OUT_DIR, { recursive: true });

const input = await FileBlob.load(XLSX);
const workbook = await SpreadsheetFile.importXlsx(input);

const table = await workbook.inspect({
  kind: "table",
  range: "Выгрузка!A1:K26",
  include: "values,formulas",
  tableMaxRows: 30,
  tableMaxCols: 12,
});
console.log(table.ndjson);

const errors = await workbook.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
  options: { useRegex: true, maxResults: 100 },
  summary: "formula error scan",
});
console.log(errors.ndjson);

const exportSheet = await workbook.render({ sheetName: "Выгрузка", range: "A1:K26", scale: 2 });
await fs.writeFile(
  `${OUT_DIR}/aero_trade_660_1_live_export.png`,
  new Uint8Array(await exportSheet.arrayBuffer()),
);

const rulesSheet = await workbook.render({ sheetName: "SQL и правила", range: "A1:H28", scale: 2 });
await fs.writeFile(
  `${OUT_DIR}/aero_trade_660_1_live_rules.png`,
  new Uint8Array(await rulesSheet.arrayBuffer()),
);
