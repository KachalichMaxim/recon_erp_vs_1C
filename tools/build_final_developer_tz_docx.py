from pathlib import Path
import shutil

from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor


ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs" / "TZ_ERP_1C_Akt_Sverki_Matrix.docx"
VERSION_LABEL = "LIVE MariaDB / 2026-06-24 / версия с разделом 5.5"
OUT_VERSIONED = ROOT / "docs" / "TZ_ERP_1C_Akt_Sverki_Matrix_LIVE_MARIADB_2026-06-24.docx"

IMG_UI = ROOT / "screenshots" / "ui" / "matrix_aero_trade_660_1_fresh_fixture.png"
IMG_ERRORS = ROOT / "screenshots" / "ui" / "matrix_errors_mode.png"
IMG_XLSX = ROOT / "screenshots" / "excel" / "aero_trade_660_1_live_export.png"


BLUE = RGBColor(46, 116, 181)
DARK_BLUE = RGBColor(31, 77, 120)
MUTED = RGBColor(90, 90, 90)
INK = RGBColor(30, 30, 30)
RISK = RGBColor(155, 28, 28)
OK_GREEN = RGBColor(42, 125, 72)
HEADER_FILL = "E8EEF5"
LIGHT_FILL = "F4F6F9"
WARN_FILL = "FFF1EB"
WHITE = "FFFFFF"


def set_font(run, size=None, bold=None, italic=None, color=None, name="Calibri"):
    run.font.name = name
    run._element.rPr.rFonts.set(qn("w:ascii"), name)
    run._element.rPr.rFonts.set(qn("w:hAnsi"), name)
    if size is not None:
        run.font.size = Pt(size)
    if bold is not None:
        run.bold = bold
    if italic is not None:
        run.italic = italic
    if color is not None:
        run.font.color.rgb = color


def shade_cell(cell, fill):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def set_cell_margins(cell, top=80, start=120, bottom=80, end=120):
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_mar = tc_pr.find(qn("w:tcMar"))
    if tc_mar is None:
        tc_mar = OxmlElement("w:tcMar")
        tc_pr.append(tc_mar)
    for key, value in [("top", top), ("start", start), ("bottom", bottom), ("end", end)]:
        node = tc_mar.find(qn(f"w:{key}"))
        if node is None:
            node = OxmlElement(f"w:{key}")
            tc_mar.append(node)
        node.set(qn("w:w"), str(value))
        node.set(qn("w:type"), "dxa")


def set_cell_width(cell, width_dxa):
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_w = tc_pr.find(qn("w:tcW"))
    if tc_w is None:
        tc_w = OxmlElement("w:tcW")
        tc_pr.append(tc_w)
    tc_w.set(qn("w:w"), str(width_dxa))
    tc_w.set(qn("w:type"), "dxa")


def set_table_geometry(table, widths):
    table.autofit = False
    tbl = table._tbl
    tbl_pr = tbl.tblPr

    tbl_w = tbl_pr.find(qn("w:tblW"))
    if tbl_w is None:
        tbl_w = OxmlElement("w:tblW")
        tbl_pr.append(tbl_w)
    tbl_w.set(qn("w:w"), str(sum(widths)))
    tbl_w.set(qn("w:type"), "dxa")

    tbl_ind = tbl_pr.find(qn("w:tblInd"))
    if tbl_ind is None:
        tbl_ind = OxmlElement("w:tblInd")
        tbl_pr.append(tbl_ind)
    tbl_ind.set(qn("w:w"), "120")
    tbl_ind.set(qn("w:type"), "dxa")

    old_grid = tbl.find(qn("w:tblGrid"))
    if old_grid is not None:
        tbl.remove(old_grid)
    grid = OxmlElement("w:tblGrid")
    for width in widths:
        col = OxmlElement("w:gridCol")
        col.set(qn("w:w"), str(width))
        grid.append(col)
    tbl.insert(0, grid)

    for row in table.rows:
        for idx, cell in enumerate(row.cells):
            set_cell_width(cell, widths[idx])
            set_cell_margins(cell)
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER


def set_cell_text(cell, text, *, size=8.5, bold=False, color=INK, align=None, fill=None):
    cell.text = ""
    if fill:
        shade_cell(cell, fill)
    set_cell_margins(cell)
    p = cell.paragraphs[0]
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(0)
    p.paragraph_format.line_spacing = 1.05
    if align is not None:
        p.alignment = align
    run = p.add_run("" if text is None else str(text))
    set_font(run, size=size, bold=bold, color=color)
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER


def add_table(doc, headers, rows, widths, *, font_size=8.2, alignments=None):
    table = doc.add_table(rows=1, cols=len(headers))
    table.style = "Table Grid"
    set_table_geometry(table, widths)
    for i, header in enumerate(headers):
        set_cell_text(
            table.rows[0].cells[i],
            header,
            size=font_size,
            bold=True,
            color=DARK_BLUE,
            fill=HEADER_FILL,
            align=WD_ALIGN_PARAGRAPH.CENTER,
        )
    for row_data in rows:
        cells = table.add_row().cells
        for i, value in enumerate(row_data):
            align = alignments[i] if alignments and i < len(alignments) else None
            set_cell_text(cells[i], value, size=font_size, align=align)
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(2)
    return table


def add_heading(doc, text, level=1):
    p = doc.add_paragraph(style=f"Heading {level}")
    p.paragraph_format.keep_with_next = True
    p.text = ""
    run = p.add_run(text)
    set_font(run, size={1: 16, 2: 13, 3: 12}.get(level, 11), bold=True, color=BLUE if level < 3 else DARK_BLUE)
    return p


def add_body(doc, text, *, color=INK, after=6, bold_prefix=None):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(after)
    p.paragraph_format.line_spacing = 1.25
    if bold_prefix and text.startswith(bold_prefix):
        first = p.add_run(bold_prefix)
        set_font(first, size=10.5, bold=True, color=color)
        rest = text[len(bold_prefix):]
        if rest:
            run = p.add_run(rest)
            set_font(run, size=10.5, color=color)
    else:
        run = p.add_run(text)
        set_font(run, size=10.5, color=color)
    return p


def add_bullet(doc, text):
    p = doc.add_paragraph(style="List Bullet")
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.line_spacing = 1.25
    run = p.add_run(text)
    set_font(run, size=10)
    return p


def add_number(doc, text):
    p = doc.add_paragraph(style="List Number")
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.line_spacing = 1.25
    run = p.add_run(text)
    set_font(run, size=10)
    return p


def add_code(doc, text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.left_indent = Inches(0.15)
    p.paragraph_format.line_spacing = 1.05
    for line_no, line in enumerate(text.strip("\n").splitlines()):
        if line_no:
            p.add_run().add_break()
        run = p.add_run(line)
        set_font(run, size=7.6, name="Courier New", color=RGBColor(40, 40, 40))
    return p


def add_callout(doc, title, text, fill=LIGHT_FILL):
    table = doc.add_table(rows=1, cols=1)
    table.style = "Table Grid"
    set_table_geometry(table, [9360])
    cell = table.rows[0].cells[0]
    shade_cell(cell, fill)
    cell.text = ""
    p = cell.paragraphs[0]
    p.paragraph_format.space_after = Pt(3)
    run = p.add_run(title)
    set_font(run, size=10, bold=True, color=DARK_BLUE)
    p2 = cell.add_paragraph()
    p2.paragraph_format.space_after = Pt(0)
    p2.paragraph_format.line_spacing = 1.2
    run = p2.add_run(text)
    set_font(run, size=9.5, color=INK)
    doc.add_paragraph().paragraph_format.space_after = Pt(2)


def add_image(doc, path, caption, width=6.3):
    if not path.exists():
        return
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(2)
    run = p.add_run()
    run.add_picture(str(path), width=Inches(width))
    cap = doc.add_paragraph()
    cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
    cap.paragraph_format.space_after = Pt(8)
    cap_run = cap.add_run(caption)
    set_font(cap_run, size=8.3, italic=True, color=MUTED)


def configure_styles(doc):
    section = doc.sections[0]
    section.top_margin = Inches(1)
    section.right_margin = Inches(1)
    section.bottom_margin = Inches(1)
    section.left_margin = Inches(1)
    section.header_distance = Inches(0.492)
    section.footer_distance = Inches(0.492)

    styles = doc.styles
    normal = styles["Normal"]
    normal.font.name = "Calibri"
    normal._element.rPr.rFonts.set(qn("w:ascii"), "Calibri")
    normal._element.rPr.rFonts.set(qn("w:hAnsi"), "Calibri")
    normal.font.size = Pt(11)
    normal.paragraph_format.space_before = Pt(0)
    normal.paragraph_format.space_after = Pt(6)
    normal.paragraph_format.line_spacing = 1.25

    for name, size, color, before, after in [
        ("Heading 1", 16, BLUE, 18, 10),
        ("Heading 2", 13, BLUE, 14, 7),
        ("Heading 3", 12, DARK_BLUE, 10, 5),
    ]:
        style = styles[name]
        style.font.name = "Calibri"
        style._element.rPr.rFonts.set(qn("w:ascii"), "Calibri")
        style._element.rPr.rFonts.set(qn("w:hAnsi"), "Calibri")
        style.font.size = Pt(size)
        style.font.bold = True
        style.font.color.rgb = color
        style.paragraph_format.space_before = Pt(before)
        style.paragraph_format.space_after = Pt(after)
        style.paragraph_format.line_spacing = 1.25

    for list_style in ["List Bullet", "List Number"]:
        style = styles[list_style]
        style.font.name = "Calibri"
        style._element.rPr.rFonts.set(qn("w:ascii"), "Calibri")
        style._element.rPr.rFonts.set(qn("w:hAnsi"), "Calibri")
        style.font.size = Pt(10)
        style.paragraph_format.left_indent = Inches(0.375)
        style.paragraph_format.first_line_indent = Inches(-0.188)
        style.paragraph_format.space_after = Pt(4)
        style.paragraph_format.line_spacing = 1.25


def title_page(doc):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(2)
    run = p.add_run("Техническое задание для PHP-разработчика")
    set_font(run, size=20, bold=True, color=RGBColor(0, 0, 0))

    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(10)
    run = p.add_run("Матрица акта сверки ERP и 1С в разрезе поставки")
    set_font(run, size=13, color=MUTED)

    add_table(
        doc,
        ["Параметр", "Значение"],
        [
            ("Версия", VERSION_LABEL),
            ("Контур", "Встроенный экран текущей PHP ERP, MariaDB, существующий SOAP-обмен с 1С."),
            ("Назначение", "Сверка взаиморасчетов клиента в иерархии клиент -> ЮЛ -> договор -> поставка."),
            ("Итог", "Экран-матрица, режим ошибок, Excel в бухгалтерском формате, журнал расхождений."),
            ("Не входит", "отдельный Python-модуль, новая БД, замена действующего ERP-интерфейса, внешние аналитические источники как часть production-расчета."),
            ("Контрольный пример", "АЭРО-ТРЕЙД, договор 660/1, свежие поставки 1063, 1064, 1065, 1068, 1072, 1073, 1074, 1076."),
        ],
        [1900, 7460],
        font_size=8.8,
    )
    add_callout(
        doc,
        "Главное правило",
        "Матрица должна считать данные из MariaDB ERP и подтверждать их существующим сервисом 1С. Внешние аналитические файлы могут использоваться только для ручной проверки корректности расчетов и не входят в production-логику.",
    )


def add_workflow(doc):
    add_heading(doc, "1. Рабочий процесс", 1)
    steps = [
        "Бухгалтер открывает страницу сверки из ERP: из поставки, клиента, договора или общего раздела `Сверки`.",
        "Выбирает клиента, период и при необходимости договор. ERP подтягивает все ЮЛ клиента через `veda_contacts -> veda_clients`.",
        "Нажимает `Сверить с 1С`. Backend собирает ERP-срез и вызывает существующие SOAP-методы сервиса 1С.",
        "ERP нормализует документы в общий формат: тип, дата, номер, код 1С, организация, контрагент, договор, поставка, сумма, НДС, источник.",
        "Экран показывает агрегированную иерархию и статусы. Документы раскрываются только внутри поставки.",
        "Кнопка `Ошибки` фильтрует строки с расхождениями и показывает причину, источник и предложенное действие.",
        "Кнопка `Выгрузить Excel` формирует бухгалтерскую форму с объединенными ячейками по спецификации.",
        "Каждая проверка пишет журнал расхождений для анализа частоты проблем по типам операций и видам ошибок.",
    ]
    for step in steps:
        add_number(doc, step)


def add_hierarchy(doc):
    add_heading(doc, "2. Иерархия данных", 1)
    add_body(
        doc,
        "Матрица строится как дерево. Верхние уровни являются агрегатами дочерних поставок. На них нельзя механически повторять сумму выбранной строки документа: это и дает ошибочную картину, когда у клиента оплата больше счета, а дельта выглядит нулевой.",
    )
    add_table(
        doc,
        ["Уровень", "ERP-таблица", "Связь", "Что выводить"],
        [
            ("Клиент", "veda_contacts", "veda_contacts.f_id = veda_clients.f_contactid", "итоги по всем ЮЛ клиента"),
            ("ЮЛ", "veda_clients", "veda_clients.f_id = veda_dogs.f_contrid", "итоги по юридическому лицу"),
            ("Договор", "veda_dogs", "veda_dogs.f_id = veda_specs.f_dogid", "итоги по договору"),
            ("Поставка / спецификация", "veda_specs", "veda_specs.f_id", "счета, оплаты, расходы, СФ/УПД, дельта"),
            ("Документы внутри поставки", "veda_schets, veda_acchist_docs, veda_akts", "операция поставки / `f_operid`", "раскрытие только для диагностики"),
        ],
        [1150, 1750, 3250, 3210],
    )
    add_code(
        doc,
        """
-- Клиент -> ЮЛ -> договор -> поставка
SELECT c.f_id contact_id, cl.f_id client_id, d.f_id dog_id, s.f_id spec_id
FROM veda_contacts c
JOIN veda_clients cl ON cl.f_contactid = c.f_id
JOIN veda_dogs d ON d.f_contrid = cl.f_id
JOIN veda_specs s ON s.f_dogid = d.f_id
WHERE c.f_id = :contact_id;
""",
    )


def add_ui(doc):
    add_heading(doc, "3. Требования к интерфейсу", 1)
    add_table(
        doc,
        ["Элемент", "Требование"],
        [
            ("Первая колонка", "`Иерархия` закреплена слева при горизонтальном скролле."),
            ("Деньги", "Все денежные колонки выровнены вправо; формат с валютой сохраняется."),
            ("Родительские строки", "Клиент, ЮЛ, договор и поставка выделены жирнее и показывают агрегаты."),
            ("Документы", "Счета, оплаты, акты и СФ/УПД раскрываются внутри поставки, а не показываются сразу всем списком."),
            ("Статусы", "Ошибки показываются бейджами. В одной строке допустимо несколько бейджей."),
            ("Поиск", "По клиенту, ЮЛ, договору, номеру поставки, счету, акту, СФ/УПД."),
        ],
        [1900, 7460],
    )
    add_image(doc, IMG_UI, "Рисунок 1. Целевой вид матрицы по свежему кейсу 660/1.")
    add_image(doc, IMG_ERRORS, "Рисунок 2. Режим ошибок: фильтр проблемных строк и карточка причины.")


def add_mapping(doc):
    add_heading(doc, "4. Маппинг ERP и 1С", 1)
    add_body(
        doc,
        "Доработка должна использовать текущий PHP-код ERP и существующий 1С-сервис. Ниже перечислены сущности, которые нужно сравнивать в разрезе поставки.",
    )
    add_table(
        doc,
        ["ERP", "1С", "Ключ / поля сверки", "Колонка или статус"],
        [
            ("veda_contacts / veda_clients", "Справочник.Контрагенты", "код 1С, ИНН, КПП, название, признак удаления", "иерархия, справочник"),
            ("veda_dogs", "Справочник.ДоговорыКонтрагентов", "код 1С, номер, дата, организация, контрагент, валюта", "договор"),
            ("veda_specs", "договор-заявка / спецификация", "f_kod1cb, f_kod1cp, номер заявки, договор 660/1", "№ спецификации"),
            ("veda_schets, f_type=1", "Документ.СчетНаОплатуПокупателю", "номер/код 1С, дата, организация, контрагент, договор, сумма", "Счет, Сумма по счету"),
            ("veda_acchist_docs", "Поступление/списание по банку", "f_docid, f_doctype=3, f_clssum, код/дата 1С", "Сумма оплаты"),
            ("veda_akts + veda_akts_details_opers", "Поступление/реализация, строки услуг", "акт, строка, операция, сумма, ставка НДС, договор строки", "Возм./невозм. расходы, НДС"),
            ("veda_akts / СФ-УПД", "Реализация, УПД, СФ", "номер/дата, сумма, НДС, контрагент, договор", "№ счф, Нет СФ/УПД"),
            ("1С акт сверки", "Документ.АктСверкиВзаиморасчетов", "организация, контрагент, договор, сальдо, обороты", "контроль итогов"),
        ],
        [1700, 1900, 3860, 1900],
        font_size=7.8,
    )
    add_heading(doc, "4.1 SOAP-методы 1С, которые нужны", 2)
    add_table(
        doc,
        ["Метод 1С", "Использование в сверке"],
        [
            ("getClients", "справочник контрагентов, договоры `dogs`, счета `accs`, признаки удаления"),
            ("getOrgs", "справочник организаций"),
            ("getAcchist", "банковские документы и расшифровка платежа по счетам/договорам"),
            ("getAkts", "акты реализации и поступления за период"),
            ("getEDOArh / getEDPacket", "СФ/УПД и ЭДО-статусы, если закрывающий документ в ЭДО"),
            ("getcoacsu / getcoacsuinfo", "итоги 1С акта сверки по контрагенту без договора и детализация по договорам"),
        ],
        [2050, 7310],
        font_size=8.2,
    )


def add_sources_and_selects(doc):
    add_heading(doc, "5. ERP-селекты и правила расчета", 1)
    add_heading(doc, "5.1 Операции поставки", 2)
    add_code(
        doc,
        """
-- Операции, привязанные к поставке напрямую или через f_parenttype=4
SELECT si.*
FROM veda_spec_invoices si
LEFT JOIN veda_categs cg
  ON cg.f_objectid = si.f_id
 AND cg.f_ctgtype = 24
 AND cg.f_objecttype = 5
WHERE (si.f_parenttype = 2 AND si.f_specid = :spec_id)
   OR (si.f_parenttype = 4 AND CAST(cg.f_valstr AS SIGNED) = :spec_id);
""",
    )
    add_heading(doc, "5.2 Счета покупателю", 2)
    add_code(
        doc,
        """
-- В матрицу идут только счета покупателю
SELECT main.f_id, main.f_num, main.f_sum, main.f_val, main.f_kod1c, main.f_dt
FROM veda_schets child
JOIN veda_schets main
  ON main.f_id = IF(child.f_maininv > 0, child.f_maininv, child.f_id)
WHERE child.f_operid IN (:operation_ids)
  AND main.f_type = 1; -- 1 = счет покупателю, 2 = счет от поставщика
""",
    )
    add_heading(doc, "5.3 Оплаты", 2)
    add_code(
        doc,
        """
-- Нельзя брать всю сумму банковского документа: нужна только распределенная доля
SELECT ahd.f_docid operation_id, SUM(ahd.f_clssum) paid_sum
FROM veda_acchist_docs ahd
JOIN veda_acchist ah ON ah.f_id = ahd.f_acchistid
WHERE ahd.f_doctype = 3
  AND ahd.f_docid IN (:operation_ids)
  AND ah.f_type = 0
GROUP BY ahd.f_docid;
""",
    )
    add_heading(doc, "5.4 Расходы", 2)
    add_table(
        doc,
        ["Тип", "ERP-признак", "Как считать", "Комментарий"],
        [
            ("Возмещаемые", "f_isvozm = 1", "`get_realizsum` по реализации/актам и деталям операции", "попадают в колонку `Возмещаемые расходы`"),
            ("Невозмещаемые", "f_isvozm = 2", "`get_realizsum` по реализации/актам и деталям операции", "попадают в колонку `Невозмещаемые расходы`"),
            ("Расходы подрядчика", "подрядная себестоимость без клиентской реализации", "не включать в взаиморасчет с клиентом", "логировать как `EXCLUDED_CONTRACTOR_EXPENSE`"),
        ],
        [1750, 2100, 2950, 2560],
        font_size=8,
    )
    add_callout(
        doc,
        "Ограничение по агентской деятельности",
        "Если услуга подрядчика отражена в 1С как расходы компании на вкладке `Услуги` с субконто `Агентское вознаграждение / Услуги сторонних организаций`, договор клиента нельзя искусственно подтягивать в взаиморасчет. Такая строка подтверждает себестоимость, но не должна менять сальдо клиента.",
        fill=WARN_FILL,
    )
    add_heading(doc, "5.5 Сквозной алгоритм для PHP-разработчика", 2)
    add_body(
        doc,
        "Матрица должна строиться не из Excel и не из внешнего файла. Источник экрана и XLSX - один нормализованный массив блоков поставок. Ниже указано, какие данные собрать, что агрегировать и в какие колонки положить.",
    )
    add_table(
        doc,
        ["Шаг", "Данные / запрос", "Что сохранить в блоке поставки"],
        [
            (
                "1. Контекст",
                "`veda_contacts -> veda_clients -> veda_dogs -> veda_specs`; фильтры `contact_id`, `client_id`, `dog_id`, период или список `spec_id/f_num`.",
                "`contact`, `legal`, `dog`, `spec`: id, название, ИНН, номер договора, код 1С, номер/дата поставки.",
            ),
            (
                "2. Операции",
                "`veda_spec_invoices` по `f_parenttype=2` и по `f_parenttype=4` через `veda_categs(f_ctgtype=24, f_objecttype=5)`.",
                "`operations[]`: `oper_id`, тип операции, `f_isvozm`, НДС, `get_paidsum`, `get_realizsum`, `get_expensessum`, `get_profit`.",
            ),
            (
                "3. Счета",
                "Из `fetch_erp_docs(spec_id)` брать `doc_kind='schet'`, `type_name='Счет покупателю'`, `invoice_id=0`; при отдельном SQL - только `veda_schets.f_type=1` и основной счет.",
                "`customer_invoices[]`: номер, дата, сумма, валюта, код 1С. Счета поставщиков не добавлять.",
            ),
            (
                "4. Оплаты",
                "`SUM(veda_acchist_docs.f_clssum)` по `f_doctype=3`, `f_docid IN operation_ids`, `JOIN veda_acchist ON f_acchistid`, `ah.f_type=0`; контроль с `get_paidsum(oper_id)`.",
                "`paid_total_get_paidsum`, `paid_total_acchist_docs`; если различаются больше 0,01 - статус `PAYMENT_ROUTINE_VS_DOCS_MISMATCH`.",
            ),
            (
                "5. Реализация",
                "Для каждой операции взять `get_realizsum(oper_id)` и разделить по `veda_spec_invoices.f_isvozm`.",
                "`reimbursable = SUM(get_realizsum WHERE f_isvozm=1)`, `non_reimbursable = SUM(get_realizsum WHERE f_isvozm=2)`.",
            ),
            (
                "6. Закрывающие документы",
                "Из `fetch_erp_docs(spec_id)` брать `doc_kind='act'`; дедупликация по `code1c/number/date/sum`.",
                "`closing_docs[]`: каждый акт/УПД/СФ отдельной строкой; не сворачивать список в счетчик количества документов.",
            ),
            (
                "7. Дельта",
                "`delta = SUM(get_paidsum) - SUM(get_realizsum)`.",
                "`settlement_delta_paid_minus_realization`; ненулевое значение - статус `SETTLEMENT_BALANCE_NONZERO`, а не ошибка SQL.",
            ),
        ],
        [700, 4380, 4280],
        font_size=7.15,
    )
    add_code(
        doc,
        """
// PHP-структура, которую должен отдавать backend экрана и XLSX
foreach ($specs as $spec) {
    $operations = fetch_operations($spec['spec_id']); // f_parenttype 2/4
    $docs       = fetch_erp_docs($spec['spec_id']);   // счета + акты/УПД/СФ
    $payments   = fetch_payments($spec['spec_id']);   // veda_acchist_docs.f_clssum

    $customerInvoices = filter($docs, doc_kind='schet', type_name='Счет покупателю', invoice_id=0);
    $closingDocs      = unique(filter($docs, doc_kind='act'), code1c, number, date, sum);

    $paidByRoutine = SUM($operations.get_paidsum);
    $paidByDocs    = SUM($payments WHERE direction='incoming'.classified_sum);
    $reimb         = SUM($operations WHERE f_isvozm=1 . get_realizsum);
    $nonReimb      = SUM($operations WHERE f_isvozm=2 . get_realizsum);
    $realization   = $reimb + $nonReimb + SUM(unclassified get_realizsum);
    $delta         = $paidByRoutine - $realization;
}
""",
    )
    add_table(
        doc,
        ["Колонка XLSX", "Заполнение", "Формат/объединение"],
        [
            ("A `№ спецификации`", "`Спецификация {spec_num}` из `veda_specs.f_num`.", "Объединить по числу строк счетов поставки."),
            ("C `Счет`", "Каждый `customer_invoices[].number` отдельной строкой.", "Не объединять, строки идут вертикально."),
            ("D `Сумма по счету`", "`customer_invoices[].sum + currency`.", "Не суммировать разные валюты без курса."),
            ("E `Сумма оплаты`", "`paid_total_get_paidsum`.", "Объединить по поставке; контроль с `paid_total_acchist_docs`."),
            ("G `Возмещаемые расходы`", "`reimbursable_realization_get_realizsum`.", "Объединить по поставке, деньги вправо."),
            ("H `Невозмещаемые расходы`", "`non_reimbursable_realization_get_realizsum`.", "Объединить по поставке, деньги вправо."),
            ("J `№ счф`", "`closing_docs` строками: `{code1c|number} от {date} ({sum})`.", "Объединить по поставке, перенос строки внутри ячейки."),
            ("K `(+/-)`", "`settlement_delta_paid_minus_realization`.", "Объединить по поставке; красный фон/текст при ненуле."),
            ("ИТОГО", "Суммы E/G/H/K по всем поставкам выборки.", "Подпись `ИТОГО`, без номеров строк внешних таблиц."),
        ],
        [1650, 5100, 2610],
        font_size=7.25,
    )
    add_callout(
        doc,
        "Где смотреть рабочий пример",
        "В репозитории рабочая реализация проверки находится в `tools/server_mariadb_reconciliation_report.py`. Она не является production PHP-кодом, но фиксирует SQL-связи, формулы, структуру JSON и правила XLSX, которые PHP-разработчик должен повторить в ERP.",
    )


def add_column_rules(doc):
    add_heading(doc, "6. Колонки матрицы", 1)
    add_table(
        doc,
        ["Колонка", "ERP-источник", "Проверка с 1С / правило"],
        [
            ("Иерархия", "veda_contacts -> veda_clients -> veda_dogs -> veda_specs", "1С-справочники проверяют коды, ИНН, КПП и договоры."),
            ("№ спецификации", "veda_specs.f_num, f_id, f_kod1cb/f_kod1cp", "договор-заявка/спецификация в 1С. Если кода договора нет, полный `ОК` запрещен."),
            ("Счет", "veda_schets.f_num, f_kod1c, f_dt, f_operid", "1С `СчетНаОплатуПокупателю`; `f_type=1` только покупательские счета."),
            ("Сумма по счету", "сумма счетов покупателю по поставке, с учетом валюты", "сверяется со счетом 1С, но не входит в формулу `(+/-)`."),
            ("Сумма оплаты", "SUM(veda_acchist_docs.f_clssum)", "сверяется с расшифровкой банковского документа 1С, не с общей суммой выписки."),
            ("Возмещаемые расходы", "`get_realizsum`, f_isvozm=1", "сверка по акту/УПД/поступлению и строке."),
            ("Невозмещаемые расходы", "`get_realizsum`, f_isvozm=2", "подрядные расходы без клиентской реализации не включать."),
            ("№ счф", "закрывающие документы ERP/1С", "все номера актов/СФ/УПД показывать через перенос строки."),
            ("(+/-)", "поступления - реализация", "сальдо поставки: дельта между распределенными поступлениями и реализацией (`get_realizsum`) по поставке."),
            ("Статус", "результат проверок ERP + 1С", "один или несколько бейджей."),
        ],
        [1280, 2940, 5140],
        font_size=7.7,
    )


def add_statuses(doc):
    add_heading(doc, "7. Статусы и сценарии проверки", 1)
    add_table(
        doc,
        ["Статус", "Когда ставим", "Что сравниваем"],
        [
            ("ОК", "ключ документа найден в ERP и 1С, суммы и обязательные поля совпали", "код/номер/дата, организация, контрагент, договор, сумма, валюта, НДС при наличии"),
            ("Нет в 1С", "ERP-документ должен быть выгружен/найден, но 1С-сервис его не вернул", "по типу документа, коду 1С, номеру, дате и организации"),
            ("Нет в ERP", "1С вернула документ, которому нет ERP-аналога", "номер/дата/организация/контрагент/договор/сумма"),
            ("Сумма расходится", "документ или агрегат найден, но сумма отличается больше допуска", "счет, оплата, акт, расход или итог поставки"),
            ("Договор расходится", "сумма или номер найдены, но договор/заявка не совпадает", "dogcode, spec code, владелец, организация"),
            ("Нет СФ/УПД", "после успешной загрузки источников нет ожидаемого закрывающего документа", "наличие акта/УПД/СФ в ERP и 1С; не путать с возмещаемостью"),
            ("Вопрос по НДС", "расход или акт найден, но ставка НДС ERP и ставка НДС 1С различаются", "только ставка НДС и сумма НДС; `f_isvozm` не является причиной этого статуса"),
            ("Ожидает ключ 1С", "ERP-документ есть, но нет кода/даты 1С для надежного матчинга", "f_kod1c, f_dt1c, номер, дата"),
            ("Подтверждено строкой 1С", "сумма найдена в строке 1С, но договор-заявка строки недоступен", "разрешено как желтый статус, не полный `ОК`"),
            ("Исключено из сальдо", "расход подрядчика `f_isvozm=2` через детали операций", "логировать, но не включать в взаиморасчет с клиентом"),
            ("Смешанные валюты", "в счетах есть разные валюты без утвержденной конвертации", "показывать суммы отдельно по валютам"),
            ("Корректировки", "у поставки есть корректировки, которые меняют срез отчета", "основной отчет и корректировки показывать отдельными уровнями"),
        ],
        [1500, 3730, 4130],
        font_size=7.45,
    )


def add_excel(doc):
    add_heading(doc, "8. Excel-выгрузка", 1)
    add_body(
        doc,
        "Excel не должен быть простым экспортом экранной матрицы. Требуется бухгалтерская форма: счета и оплаты идут строками, а расходы, СФ/УПД и дельта объединяются по количеству строк счетов внутри спецификации.",
    )
    add_table(
        doc,
        ["Колонки Excel", "Правило"],
        [
            ("№ спецификации", "объединить по строкам счетов; текст вида `Спецификация 1063`"),
            ("Счет", "каждый покупательский счет отдельной строкой; если несколько счетов - переносы/строки"),
            ("Сумма по счету", "сумма счета в его валюте; не суммировать разные валюты без курса"),
            ("Сумма оплаты", "распределенные оплаты по счету/поставке; дополнительные строки допускаются"),
            ("Возмещаемые расходы", "объединенная ячейка по спецификации"),
            ("Невозмещаемые расходы", "объединенная ячейка по спецификации"),
            ("№ счф", "объединенная ячейка; все документы через Enter, без сокращений количества документов"),
            ("(+/-)", "объединенная ячейка по спецификации"),
        ],
        [2350, 7010],
        font_size=8,
    )
    add_body(
        doc,
        "Копирование в буфер должно формировать плоский TSV: значения объединенных ячеек повторяются в каждой строке, чтобы бухгалтер мог вставить данные в Excel без потери контекста.",
    )
    add_image(doc, IMG_XLSX, "Рисунок 3. Требуемый вид XLSX-выгрузки по свежему кейсу 660/1.")


def add_logging(doc):
    add_heading(doc, "9. Журнал расхождений", 1)
    add_body(
        doc,
        "Нужна таблица логирования не только для текущего экрана, но и для последующего анализа частоты проблем: какие типы операций чаще ломаются, где нет ключей 1С, какие поставщики/услуги дают расхождения.",
    )
    add_table(
        doc,
        ["Поле", "Назначение"],
        [
            ("run_id", "идентификатор запуска сверки"),
            ("contact_id, client_id, dog_id, spec_id", "контекст клиента, ЮЛ, договора и поставки"),
            ("doc_type, erp_doc_id, onec_doc_key, onec_guid", "идентификация документа с обеих сторон"),
            ("mismatch_code", "код статуса: AMOUNT_MISMATCH, VAT_MISMATCH, MISSING_1C и т.д."),
            ("erp_value, onec_value, diff_amount", "значения и числовая разница"),
            ("operation_id, operation_type_id, operation_type_name", "операция поставки и ее тип"),
            ("f_isvozm, source_table, source_row_id", "классификация и источник ERP-строки"),
            ("created_at, created_by", "время и пользователь"),
        ],
        [3000, 6360],
        font_size=8.1,
    )


def add_control_case(doc):
    add_heading(doc, "10. Контрольный кейс 660/1", 1)
    add_body(
        doc,
        "Проверка выполнялась на свежих поставках договора 660/1. Итоговый расчет ниже воспроизводится из MariaDB ERP и должен совпадать с production-логикой PHP.",
    )
    add_table(
        doc,
        ["Параметр", "Значение"],
        [
            ("Клиент", "АЭРО-ТРЕЙД"),
            ("contact_id / client_id / dog_id", "115 / 221 / 88"),
            ("Договор", "660/1, код 1С БП-003453"),
            ("Поставки", "1063, 1064, 1065, 1068, 1072, 1073, 1074, 1076"),
            ("Итоги", "поступления/get_paidsum 2 331 111,33; реализация/get_realizsum: возмещаемые 2 152 392,27 и невозмещаемые 263 133,46; сальдо -84 414,40"),
            ("Особенность", "`get_paidsum` совпал с `SUM(veda_acchist_docs.f_clssum)`; подрядные расходы без клиентской реализации исключаются из взаиморасчета, но логируются"),
        ],
        [2550, 6810],
        font_size=8.2,
    )
    add_table(
        doc,
        ["Спец.", "Оплата", "Возм.", "Невозм.", "Реализация", "(+/-)"],
        [
            ("1063", "199 375,04", "185 181,88", "41 275,82", "226 457,70", "-27 082,66"),
            ("1064", "134 234,42", "124 577,67", "27 882,63", "152 460,30", "-18 225,88"),
            ("1065", "170 517,04", "158 354,45", "35 211,68", "193 566,13", "-23 049,09"),
            ("1068", "442 296,92", "416 239,47", "36 640,04", "452 879,51", "-10 582,59"),
            ("1072", "319 193,45", "287 869,95", "26 049,03", "313 918,98", "5 274,47"),
            ("1073", "131 241,57", "118 363,63", "10 708,61", "129 072,24", "2 169,33"),
            ("1074", "442 296,92", "416 239,47", "36 640,04", "452 879,51", "-10 582,59"),
            ("1076", "491 955,97", "445 565,75", "48 725,61", "494 291,36", "-2 335,39"),
            ("Итого", "2 331 111,33", "2 152 392,27", "263 133,46", "2 415 525,73", "-84 414,40"),
        ],
        [1000, 1670, 1670, 1670, 1670, 1680],
        font_size=7.9,
        alignments=[
            WD_ALIGN_PARAGRAPH.CENTER,
            WD_ALIGN_PARAGRAPH.RIGHT,
            WD_ALIGN_PARAGRAPH.RIGHT,
            WD_ALIGN_PARAGRAPH.RIGHT,
            WD_ALIGN_PARAGRAPH.RIGHT,
            WD_ALIGN_PARAGRAPH.RIGHT,
        ],
    )
    add_callout(
        doc,
        "Проверка источника",
        "Контрольный JSON и XLSX собраны серверным скриптом из MariaDB ERP. Для каждой поставки проверено равенство `get_paidsum` и распределенной оплаты `SUM(veda_acchist_docs.f_clssum)`, а дельта рассчитана как `SUM(get_paidsum) - SUM(get_realizsum)`.",
    )
    add_body(
        doc,
        "Письма и внешние корректировки вида `по письму` в ERP отсутствуют и не должны искусственно добавляться в расчет. Если источник отсутствует, строка остается пустой или получает статус `Недостаточно данных`.",
        color=RISK,
    )


def add_acceptance(doc):
    add_heading(doc, "11. Критерии приемки", 1)
    checks = [
        "Экран строит иерархию `veda_contacts -> veda_clients -> veda_dogs -> veda_specs` и не смешивает уровни цифрами.",
        "Свежий кейс 660/1 воспроизводит live-итоги MariaDB: поступления/get_paidsum 2 331 111,33; реализация/get_realizsum 2 415 525,73; сальдо -84 414,40.",
        "Сумма оплаты берется из `veda_acchist_docs.f_clssum`; полная сумма банка не используется как оплата поставки.",
        "`f_isvozm=1` идет в возмещаемые расходы; `f_isvozm=2` идет в невозмещаемые расходы через `get_realizsum`; подрядные расходы без клиентской реализации исключаются из сальдо.",
        "`Нет СФ/УПД` определяется отсутствием закрывающего документа после загрузки источников, а `Вопрос по НДС` - только несовпадением ставки НДС ERP и 1С.",
        "XLSX выгружает все документы в объединенной ячейке через Enter, без сокращения списка до счетчика количества документов.",
        "Журнал расхождений пишет тип ошибки, источник, операцию, суммы и контекст поставки.",
        "Автотесты live-валидации проходят: `npm test` по текущему PR.",
    ]
    for check in checks:
        add_bullet(doc, check)


def add_footer(doc):
    section = doc.sections[0]
    footer = section.footer
    p = footer.paragraphs[0]
    p.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    p.text = ""
    run = p.add_run("ТЗ ERP ↔ 1С / матрица акта сверки")
    set_font(run, size=8, color=MUTED)


def build_doc():
    doc = Document()
    configure_styles(doc)
    add_footer(doc)

    title_page(doc)
    add_workflow(doc)
    add_hierarchy(doc)
    add_ui(doc)
    doc.add_page_break()
    add_mapping(doc)
    add_sources_and_selects(doc)
    doc.add_page_break()
    add_column_rules(doc)
    add_statuses(doc)
    add_excel(doc)
    add_logging(doc)
    add_control_case(doc)
    add_acceptance(doc)

    OUT.parent.mkdir(parents=True, exist_ok=True)
    doc.save(OUT)
    shutil.copyfile(OUT, OUT_VERSIONED)
    print(OUT)
    print(OUT_VERSIONED)


if __name__ == "__main__":
    build_doc()
