from pathlib import Path

from PIL import Image, ImageDraw, ImageFont
from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Inches, Pt, RGBColor


OUT = Path("/Users/kachalichmaxim/Desktop/template_for_print/TZ_ERP_1C_Akt_Sverki_Matrix.docx")
SCREEN_DIR = Path("/Users/kachalichmaxim/Desktop/template_for_print/akt_sverki/reference/screenshots")
TARGET_MATRIX_IMAGE = SCREEN_DIR / "matrix_target_example.png"
CORRECTED_MATRIX_IMAGE = SCREEN_DIR / "matrix_corrected_view.png"
ERRORS_IMAGE = SCREEN_DIR / "matrix_errors_mode.png"


BLUE = RGBColor(46, 116, 181)
DARK_BLUE = RGBColor(31, 77, 120)
MUTED = RGBColor(90, 90, 90)
LIGHT_BLUE = "E8EEF5"
LIGHT_GRAY = "F2F4F7"
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


def set_cell_width(cell, width_dxa):
    tc_pr = cell._tc.get_or_add_tcPr()
    tc_w = tc_pr.find(qn("w:tcW"))
    if tc_w is None:
        tc_w = OxmlElement("w:tcW")
        tc_pr.append(tc_w)
    tc_w.set(qn("w:w"), str(width_dxa))
    tc_w.set(qn("w:type"), "dxa")


def set_table_width(table, widths):
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


def set_cell_text(cell, text, bold=False, color=None, size=8.7, align=None):
    cell.text = ""
    p = cell.paragraphs[0]
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(0)
    p.paragraph_format.line_spacing = 1.05
    if align is not None:
        p.alignment = align
    run = p.add_run(text)
    set_font(run, size=size, bold=bold, color=color)
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER


def add_table(doc, headers, rows, widths, font_size=8.5):
    table = doc.add_table(rows=1, cols=len(headers))
    table.style = "Table Grid"
    set_table_width(table, widths)
    for i, header in enumerate(headers):
        shade_cell(table.rows[0].cells[i], LIGHT_BLUE)
        set_cell_text(table.rows[0].cells[i], header, bold=True, color=DARK_BLUE, size=font_size)
    for row_data in rows:
        cells = table.add_row().cells
        for i, value in enumerate(row_data):
            text = "" if value is None else str(value)
            set_cell_text(cells[i], text, size=font_size)
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(2)
    return table


def add_heading(doc, text, level=1):
    p = doc.add_paragraph()
    p.style = f"Heading {level}"
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    set_font(run, size={1: 16, 2: 13, 3: 12}.get(level, 11), bold=True, color=BLUE if level < 3 else DARK_BLUE)
    return p


def add_body(doc, text, bold_prefix=None):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(5)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix and text.startswith(bold_prefix):
        run = p.add_run(bold_prefix)
        set_font(run, size=10.5, bold=True)
        rest = text[len(bold_prefix):]
        if rest:
            run = p.add_run(rest)
            set_font(run, size=10.5)
    else:
        run = p.add_run(text)
        set_font(run, size=10.5)
    return p


def add_bullet(doc, text):
    p = doc.add_paragraph(style="List Bullet")
    p.paragraph_format.space_after = Pt(2)
    run = p.add_run(text)
    set_font(run, size=10)
    return p


def add_number(doc, text):
    p = doc.add_paragraph(style="List Number")
    p.paragraph_format.space_after = Pt(2)
    run = p.add_run(text)
    set_font(run, size=10)
    return p


def add_caption(doc, text):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(8)
    run = p.add_run(text)
    set_font(run, size=8.5, italic=True, color=MUTED)
    return p


def add_image(doc, path, caption):
    if path.exists():
        p = doc.add_paragraph()
        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p.paragraph_format.space_after = Pt(2)
        run = p.add_run()
        run.add_picture(str(path), width=Inches(6.15))
        add_caption(doc, caption)


def _pil_font(size, bold=False):
    candidates = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/System/Library/Fonts/Supplemental/Helvetica Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Helvetica.ttf",
        "/Library/Fonts/Arial Unicode.ttf",
    ]
    for path in candidates:
        try:
            return ImageFont.truetype(path, size)
        except OSError:
            continue
    return ImageFont.load_default()


def _draw_badge(draw, xy, text, fill, outline, text_fill, font):
    x, y = xy
    pad_x, pad_y = 13, 6
    bbox = draw.textbbox((0, 0), text, font=font)
    w = bbox[2] - bbox[0] + pad_x * 2
    h = bbox[3] - bbox[1] + pad_y * 2
    draw.rounded_rectangle([x, y, x + w, y + h], radius=13, fill=fill, outline=outline, width=1)
    draw.text((x + pad_x, y + pad_y - 1), text, fill=text_fill, font=font)
    return w, h


def _text(draw, xy, text, font, fill=(34, 34, 34), anchor=None, align="left"):
    draw.text(xy, text, font=font, fill=fill, anchor=anchor, align=align)


def _money(value):
    return value


def create_matrix_target_example(path=TARGET_MATRIX_IMAGE):
    path.parent.mkdir(parents=True, exist_ok=True)
    W, H = 2100, 1000
    img = Image.new("RGB", (W, H), (248, 248, 247))
    draw = ImageDraw.Draw(img)
    f10 = _pil_font(20)
    f11 = _pil_font(22)
    f12 = _pil_font(24)
    f13b = _pil_font(26, True)
    f15b = _pil_font(30, True)
    f18b = _pil_font(36, True)

    # App chrome.
    draw.rectangle([0, 0, 310, H], fill=(246, 244, 239))
    draw.rectangle([310, 0, W, 68], fill=(255, 255, 255))
    _text(draw, (32, 36), "AC", f13b, fill=(255, 255, 255), anchor="mm")
    draw.rounded_rectangle([15, 18, 49, 54], radius=8, fill=(22, 22, 22))
    _text(draw, (68, 24), "Акт сверки", f12, fill=(35, 35, 35))
    _text(draw, (68, 50), "ERP ↔ 1C", f10, fill=(120, 120, 120))
    menu = [
        ("Поставки", "12"),
        ("Сверки", "84"),
        ("Матрица", "1"),
        ("Ошибки", "3"),
        ("Документы", ""),
    ]
    y = 130
    for name, count in menu:
        selected = name == "Матрица"
        if selected:
            draw.rounded_rectangle([18, y - 12, 292, y + 35], radius=9, fill=(36, 31, 25))
        _text(draw, (50, y), name, f11, fill=(255, 255, 255) if selected else (80, 80, 80))
        if count:
            _text(draw, (270, y), count, f10, fill=(255, 255, 255) if selected else (80, 80, 80), anchor="ra")
        y += 55

    _text(draw, (345, 31), "Поставки / SPEC-14994 / Матрица взаиморасчетов", f11, fill=(90, 90, 90))
    _text(draw, (345, 104), "Акт сверки по взаиморасчетам", f18b, fill=(31, 31, 31))
    _text(draw, (345, 142), "ERP-снапшот заявки 921: суммы взяты из settlements и ERP-документов, без внешнего листа", f11, fill=(110, 110, 110))
    _draw_badge(draw, (1270, 92), "Сверить с 1С", (255, 255, 255), (214, 214, 214), (35, 35, 35), f10)
    _draw_badge(draw, (1460, 92), "Выгрузить Excel", (31, 124, 172), (31, 124, 172), (255, 255, 255), f10)

    # Controls.
    draw.rounded_rectangle([345, 168, 760, 220], radius=10, fill=(255, 255, 255), outline=(220, 220, 220))
    _text(draw, (365, 184), "АЭРО-ТРЕЙД · ИНН 7811451960", f11, fill=(50, 50, 50))
    draw.rounded_rectangle([775, 168, 1560, 220], radius=10, fill=(255, 255, 255), outline=(220, 220, 220))
    _text(draw, (800, 184), "Поиск по клиенту, договору, спецификации, счету...", f11, fill=(130, 130, 130))
    _draw_badge(draw, (1580, 174), "Все", (255, 255, 255), (210, 210, 210), (50, 50, 50), f10)
    _draw_badge(draw, (1642, 174), "Ошибки", (255, 244, 239), (230, 170, 150), (150, 60, 35), f10)

    cards = [
        ("166 901,52 р.", "сумма по счетам"),
        ("833 921,00 р.", "сумма оплат"),
        ("829 165,90 р.", "возмещаемые расходы"),
        ("67 055,99 р.", "невозмещаемые расходы"),
        ("-62 300,89 р.", "(+/-) сальдо"),
    ]
    x = 345
    for value, label in cards:
        draw.rounded_rectangle([x, 240, x + 280, 306], radius=10, fill=(255, 255, 255), outline=(226, 226, 226))
        _text(draw, (x + 18, 251), value, f13b, fill=(35, 35, 35))
        _text(draw, (x + 18, 282), label.upper(), f10, fill=(120, 120, 120))
        x += 295

    # Table.
    table_x, table_y = 345, 330
    cols = [
        ("ИЕРАРХИЯ", 360),
        ("№\nСПЕЦ.", 110),
        ("СЧЕТ", 210),
        ("СУММА\nПО СЧЕТУ", 165),
        ("СУММА\nОПЛАТЫ", 165),
        ("ВОЗМ.\nРАСХОДЫ", 165),
        ("НЕВОЗМ.\nРАСХОДЫ", 165),
        ("№ СЧФ", 135),
        ("(+/-)", 120),
        ("СТАТУС", 130),
    ]
    total_w = sum(w for _, w in cols)
    row_h = 62
    header_h = 62
    row_count = 7
    draw.rounded_rectangle([table_x, table_y, table_x + total_w, table_y + header_h + row_h * row_count], radius=13, fill=(255, 255, 255), outline=(220, 220, 220))
    draw.rectangle([table_x, table_y, table_x + total_w, table_y + header_h], fill=(222, 221, 217))
    x = table_x
    for label, w in cols:
        _text(draw, (x + 12, table_y + 15), label, f10, fill=(70, 70, 70))
        x += w
        draw.line([x, table_y, x, table_y + header_h + row_h * row_count], fill=(230, 230, 230), width=1)

    rows = [
        ("▾  АЭРО-ТРЕЙД\n    ИНН 7811451960", "4", "5 счетов", "512 622,83 р.", "889 921,00 р.", "869 165,90 р.", "67 055,99 р.", "6 док.", "-46 300,89", "2 ошибки", 0),
        ("  ▾  ООО \"АЭРО-ТРЕЙД\"", "4", "5 счетов", "512 622,83 р.", "889 921,00 р.", "869 165,90 р.", "67 055,99 р.", "6 док.", "-46 300,89", "2 ошибки", 1),
        ("    ▾  Договор 660/1", "4", "5 счетов", "512 622,83 р.", "889 921,00 р.", "869 165,90 р.", "67 055,99 р.", "6 док.", "-46 300,89", "Есть ошибки", 2),
        ("      ▾  Основной отчет", "0", "срез ERP", "", "", "", "", "", "", "Сводно", 3),
        ("        Заявка №921\n        ATV-715-S море", "921", "ВА-015695,\nВА-015696 +1", "166 901,52 р.", "833 921,00 р.", "829 165,90 р.", "67 055,99 р.", "00БП-198", "-62 300,89", "Есть остаток", 4),
        ("        Заявка №922", "922", "ВА-015697", "142 142,31 р.", "142 142,31 р.", "142 142,31 р.", "0,00 р.", "00БП-1542", "0,00", "ОК", 4),
        ("        Заявка №938", "938", "ВА-015841", "203 579,00 р.", "203 579,00 р.", "203 579,00 р.", "0,00 р.", "00БП-23423", "0,00", "НДС", 4),
    ]
    y = table_y + header_h
    for row_idx, row in enumerate(rows):
        fill = (250, 249, 247) if row[-1] < 3 else (255, 255, 255)
        draw.rectangle([table_x, y, table_x + total_w, y + row_h], fill=fill)
        draw.line([table_x, y + row_h, table_x + total_w, y + row_h], fill=(232, 232, 232), width=1)
        x = table_x
        for idx, (label, w) in enumerate(cols):
            value = row[idx]
            if idx in (3, 4, 5, 6, 8):
                _text(draw, (x + w - 12, y + 24), value, f11 if row[-1] == 3 else f11, fill=(35, 35, 35), anchor="ra")
            elif idx == 9:
                if value == "ОК":
                    _draw_badge(draw, (x + 10, y + 20), value, (232, 246, 238), (146, 206, 166), (42, 125, 72), f10)
                elif value == "Нет СФ/УПД":
                    _draw_badge(draw, (x + 10, y + 20), value, (255, 241, 235), (233, 171, 151), (154, 65, 36), f10)
                elif value in ("НДС", "2 ошибки", "Есть ошибки"):
                    _draw_badge(draw, (x + 10, y + 20), value, (255, 241, 235), (233, 171, 151), (154, 65, 36), f10)
                elif value == "Сводно":
                    _draw_badge(draw, (x + 10, y + 20), value, (238, 238, 238), (204, 204, 204), (75, 75, 75), f10)
                elif value == "Строка 1С":
                    _draw_badge(draw, (x + 10, y + 20), value, (237, 244, 255), (160, 190, 230), (49, 87, 150), f10)
                else:
                    _draw_badge(draw, (x + 10, y + 20), value, (255, 245, 224), (222, 184, 105), (135, 89, 20), f10)
            else:
                font = f11 if row[-1] == 3 else f11
                _text(draw, (x + 12, y + 13), value, font, fill=(35, 35, 35))
            x += w
        y += row_h

    _text(
        draw,
        (348, 925),
        "Формула строки поставки: (+/-) = сумма оплаты - возмещаемые расходы - невозмещаемые расходы. Сумма по счетам сверяется отдельно.",
        f11,
        fill=(85, 85, 85),
    )
    img.save(path)


def create_errors_mode_example(path=ERRORS_IMAGE):
    path.parent.mkdir(parents=True, exist_ok=True)
    W, H = 2100, 1000
    img = Image.new("RGB", (W, H), (248, 248, 247))
    draw = ImageDraw.Draw(img)
    f10 = _pil_font(20)
    f11 = _pil_font(22)
    f12 = _pil_font(24)
    f13b = _pil_font(26, True)
    f15b = _pil_font(30, True)
    f18b = _pil_font(36, True)

    draw.rectangle([0, 0, 310, H], fill=(246, 244, 239))
    draw.rectangle([310, 0, W, 68], fill=(255, 255, 255))
    draw.rounded_rectangle([15, 18, 49, 54], radius=8, fill=(22, 22, 22))
    _text(draw, (32, 36), "AC", f13b, fill=(255, 255, 255), anchor="mm")
    _text(draw, (68, 24), "Акт сверки", f12, fill=(35, 35, 35))
    y = 130
    for name, count in [("Поставки", "12"), ("Сверки", "84"), ("Матрица", "1"), ("Ошибки", "3"), ("Документы", "")]:
        selected = name == "Ошибки"
        if selected:
            draw.rounded_rectangle([18, y - 12, 292, y + 35], radius=9, fill=(36, 31, 25))
        _text(draw, (50, y), name, f11, fill=(255, 255, 255) if selected else (80, 80, 80))
        if count:
            _text(draw, (270, y), count, f10, fill=(255, 255, 255) if selected else (80, 80, 80), anchor="ra")
        y += 55

    _text(draw, (345, 31), "Поставки / ERP / Ошибки сверки", f11, fill=(90, 90, 90))
    _text(draw, (345, 104), "Ошибки и расхождения", f18b, fill=(31, 31, 31))
    _text(draw, (345, 142), "Тот же набор данных, но только проблемные поставки и объяснение причины", f11, fill=(110, 110, 110))
    _draw_badge(draw, (1460, 92), "Выгрузить ошибки", (31, 124, 172), (31, 124, 172), (255, 255, 255), f10)

    filter_labels = ["Все ошибки", "Нет в 1С", "Нет СФ/УПД", "Вопрос по НДС", "Сумма расходится"]
    x = 345
    for i, label in enumerate(filter_labels):
        selected = i == 0
        _draw_badge(
            draw,
            (x, 178),
            label,
            (36, 31, 25) if selected else (255, 255, 255),
            (36, 31, 25) if selected else (214, 214, 214),
            (255, 255, 255) if selected else (50, 50, 50),
            f10,
        )
        x += 34 + draw.textbbox((0, 0), label, font=f10)[2] - draw.textbbox((0, 0), label, font=f10)[0] + 36

    # Main table.
    table_x, table_y = 345, 250
    left_w, side_w = 1080, 610
    draw.rounded_rectangle([table_x, table_y, table_x + left_w, 770], radius=13, fill=(255, 255, 255), outline=(220, 220, 220))
    headers = [("ИЕРАРХИЯ", 300), ("ПОЛЕ", 175), ("ERP", 160), ("1C", 160), ("РАЗНИЦА", 140), ("СТАТУС", 145)]
    x = table_x
    draw.rectangle([table_x, table_y, table_x + left_w, table_y + 62], fill=(222, 221, 217))
    for label, w in headers:
        _text(draw, (x + 12, table_y + 20), label, f10, fill=(70, 70, 70))
        x += w
        draw.line([x, table_y, x, 770], fill=(230, 230, 230), width=1)
    error_rows = [
        ("Поставка 15823", "Сумма", "897,50", "0,00", "897,50", "Сумма"),
        ("Поставка 15823", "Договор строки", "договор ERP", "нет в Услуги", "—", "Строка 1С"),
        ("Заявка №938", "НДС", "0%", "20%", "—", "НДС"),
        ("Закрывающий док.", "УПД/СФ", "ожидается", "не найден", "—", "Нет СФ/УПД"),
    ]
    y = table_y + 62
    for row in error_rows:
        draw.rectangle([table_x, y, table_x + left_w, y + 82], fill=(255, 255, 255))
        draw.line([table_x, y + 82, table_x + left_w, y + 82], fill=(232, 232, 232), width=1)
        x = table_x
        for idx, (label, w) in enumerate(headers):
            value = row[idx]
            if idx in (2, 3, 4):
                _text(draw, (x + w - 12, y + 30), value, f11, fill=(35, 35, 35), anchor="ra")
            elif idx == 5:
                fill = (255, 241, 235) if value in ("Нет СФ/УПД", "НДС", "Сумма") else (237, 244, 255)
                outline = (233, 171, 151) if value in ("Нет СФ/УПД", "НДС", "Сумма") else (160, 190, 230)
                txt = (154, 65, 36) if value in ("Нет СФ/УПД", "НДС", "Сумма") else (49, 87, 150)
                _draw_badge(draw, (x + 10, y + 25), value, fill, outline, txt, f10)
            else:
                _text(draw, (x + 12, y + 30), value, f11, fill=(35, 35, 35))
            x += w
        y += 82

    # Side explanation panel.
    panel_x = table_x + left_w + 25
    draw.rounded_rectangle([panel_x, table_y, panel_x + side_w, 845], radius=13, fill=(255, 255, 255), outline=(220, 220, 220))
    _text(draw, (panel_x + 24, table_y + 24), "Карточка ошибки", f15b, fill=(35, 35, 35))
    _draw_badge(draw, (panel_x + 24, table_y + 70), "Выбрана: Поставка 15823", (237, 244, 255), (160, 190, 230), (49, 87, 150), f10)
    lines = [
        ("Проблема", "Поставка с корректировками и валютными операциями не сворачивается в одну линейную строку без среза корректировки."),
        ("ERP", "основной отчет и корректировки должны идти отдельными уровнями внутри поставки"),
        ("1C", "часть строк Услуги не возвращает договор-заявку; сумма может подтвердиться, но полный ОК запрещен"),
        ("Статус", "Ограничение / Сумма расходится / Договор расходится по конкретной строке"),
        ("Действие", "Показать срез корректировки и не подставлять договор клиента в расходы подрядчика."),
    ]
    yy = table_y + 125
    for label, value in lines:
        _text(draw, (panel_x + 24, yy), label, f12, fill=(95, 95, 95))
        yy += 28
        # manual wrap for side panel
        words = value.split()
        line = ""
        for word in words:
            trial = f"{line} {word}".strip()
            if draw.textbbox((0, 0), trial, font=f11)[2] > side_w - 48:
                _text(draw, (panel_x + 24, yy), line, f11, fill=(35, 35, 35))
                yy += 25
                line = word
            else:
                line = trial
        if line:
            _text(draw, (panel_x + 24, yy), line, f11, fill=(35, 35, 35))
            yy += 32

    _text(draw, (348, 910), "Экран ошибок не заменяет матрицу: это фильтр для бухгалтерии, чтобы быстро разобрать только проблемные поставки и видеть источник каждого расхождения.", f11, fill=(85, 85, 85))
    img.save(path)


def set_styles(doc):
    styles = doc.styles
    normal = styles["Normal"]
    normal.font.name = "Calibri"
    normal._element.rPr.rFonts.set(qn("w:ascii"), "Calibri")
    normal._element.rPr.rFonts.set(qn("w:hAnsi"), "Calibri")
    normal.font.size = Pt(10.5)

    for name, size, color, before, after in [
        ("Heading 1", 16, BLUE, 14, 7),
        ("Heading 2", 13, BLUE, 10, 5),
        ("Heading 3", 12, DARK_BLUE, 8, 4),
    ]:
        style = styles[name]
        style.font.name = "Calibri"
        style._element.rPr.rFonts.set(qn("w:ascii"), "Calibri")
        style._element.rPr.rFonts.set(qn("w:hAnsi"), "Calibri")
        style.font.size = Pt(size)
        style.font.color.rgb = color
        style.font.bold = True
        style.paragraph_format.space_before = Pt(before)
        style.paragraph_format.space_after = Pt(after)
        style.paragraph_format.line_spacing = 1.15


def build_doc():
    create_matrix_target_example(CORRECTED_MATRIX_IMAGE)
    create_errors_mode_example()

    doc = Document()
    section = doc.sections[0]
    section.top_margin = Inches(1)
    section.bottom_margin = Inches(1)
    section.left_margin = Inches(1)
    section.right_margin = Inches(1)
    section.header_distance = Inches(0.492)
    section.footer_distance = Inches(0.492)
    set_styles(doc)

    # Title block.
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(2)
    run = p.add_run("Техническое задание")
    set_font(run, size=20, bold=True, color=RGBColor(0, 0, 0))

    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(12)
    run = p.add_run("Матрица акта сверки ERP и 1С в разрезе поставки")
    set_font(run, size=13, color=MUTED)

    meta = doc.add_table(rows=3, cols=2)
    meta.style = "Table Grid"
    set_table_width(meta, [2200, 7160])
    for row in meta.rows:
        for cell in row.cells:
            shade_cell(cell, WHITE)
    meta_rows = [
        ("Назначение", "Встроенный экран ERP для бухгалтерской сверки: клиент -> ЮЛ -> договор -> поставка -> документы."),
        ("Источники", "ERP MariaDB и сервис 1С через существующий PHP SOAP-слой."),
        ("Результат", "Матрица на экране и Excel-выгрузка с теми же итогами, деталями и статусами."),
    ]
    for row, (label, value) in zip(meta.rows, meta_rows):
        set_cell_text(row.cells[0], label, bold=True, color=DARK_BLUE, size=9.2)
        set_cell_text(row.cells[1], value, size=9.2)

    add_heading(doc, "1. Рабочий процесс бухгалтера", 1)
    workflow = [
        "Пользователь открывает экран из поставки или из раздела сверки.",
        "ERP определяет контекст: клиент, все ЮЛ клиента, договоры и поставки по выбранному периоду.",
        "Пользователь нажимает `Сверить с 1С`.",
        "Backend ERP читает свои документы и запрашивает сервис 1С по тем же организациям и периоду.",
        "Данные нормализуются в общий формат: тип, код 1С, дата, организация, контрагент, договор, поставка, сумма, строка.",
        "Система проставляет статусы: `ОК`, `Нет в 1С`, `Нет в ERP`, `Сумма расходится`, `Договор расходится`, `Нет ключа 1С`, `Подтверждено строкой 1С`.",
        "Бухгалтер раскрывает строку поставки и видит документы внутри спецификации.",
        "По кнопке `Выгрузить` формируется Excel в бухгалтерском формате референса: счета и оплаты строками, расходы и дельта одним объединенным блоком по спецификации.",
    ]
    for item in workflow:
        add_number(doc, item)

    add_heading(doc, "2. Иерархия и разрез сверки", 1)
    add_body(doc, "Матрица строится сверху вниз. Верхние уровни показывают только агрегаты дочерних строк. Документные строки внутри поставки не обязательны: по умолчанию бухгалтер видит поставки, а детали открывает только для разбора конкретной ошибки.")
    add_table(
        doc,
        ["Уровень", "ERP-источник", "Ключ связи", "Что показываем"],
        [
            ("Клиент", "veda_contacts", "veda_clients.f_contactid", "общие итоги по всем ЮЛ клиента"),
            ("ЮЛ", "veda_clients", "veda_dogs.f_contrid", "итоги по юридическому лицу"),
            ("Договор", "veda_dogs", "veda_specs.f_dogid", "итоги по договору и организации"),
            ("Поставка", "veda_specs", "veda_specs.f_id", "счета, оплаты, расходы, СФ/УПД, дельта"),
            ("Документ", "счета, оплаты, акты, УПД", "код 1С + дата + тип", "опциональная детализация; не должна повторять сальдо поставки"),
        ],
        [1250, 2150, 2250, 3710],
    )

    add_heading(doc, "3. Как должен выглядеть экран", 1)
    add_body(doc, "Основной экран - компактная матрица. Первая колонка `Иерархия` закреплена слева, деньги выровнены вправо, родительские строки выделены жирнее, ошибки показаны бейджами. Важно: навигационные уровни не копируют сумму выбранной поставки или документа; деньги показываются на агрегате клиента и на финансовом срезе поставки. Логика колонок должна повторять ERP-расчет, который уже реализован в сервисе сверки.")
    add_table(
        doc,
        ["Блок", "Колонки", "Требование"],
        [
            ("Иерархия", "Иерархия", "закреплена слева, раскрытие клиент -> ЮЛ -> договор -> поставка; документы опционально"),
            ("Документы", "№ спецификации, Счет, № счф", "короткие номера, перенос внутри ячейки; не выводить все документы сразу"),
            ("Деньги", "Сумма по счету, Сумма оплаты, Возмещаемые расходы, Невозмещаемые расходы, (+/-)", "все суммы выровнены вправо; `(+/-)` считается по ERP-формуле: оплаты - возмещаемые - невозмещаемые"),
            ("Статус", "Статус", "бейджи; в одной строке допускается несколько причин расхождения"),
        ],
        [1250, 3250, 4860],
        font_size=8.2,
    )
    add_body(doc, "Ниже зафиксирован доработанный вид: иерархия читается текстом, промежуточные уровни не повторяют деньги, документы внутри спецификации раскрываются только по требованию.")
    add_image(doc, CORRECTED_MATRIX_IMAGE, "Рисунок 1. Доработанный вид матрицы: клиент -> ЮЛ -> договор -> поставка -> спецификация, суммы только на финансовых уровнях.")
    add_image(doc, ERRORS_IMAGE, "Рисунок 2. Режим `Ошибки`: та же матрица, но только проблемные строки и карточка причины расхождения.")

    add_heading(doc, "4. Что сверяем с чем", 1)
    add_body(doc, "Ниже основной маппинг сущностей в разрезе поставки. Полный `ОК` ставится только когда совпали ключ, сумма и договор/поставка.")
    add_table(
        doc,
        ["ERP-сущность", "1С-сущность", "Сравниваемые поля", "Результат в матрице"],
        [
            ("Клиент / ЮЛ", "Контрагент", "код 1С, ИНН, КПП, наименование, признак удаления", "верхние уровни и предупреждения по справочнику"),
            ("Организация", "Организация", "код 1С, ИНН, название", "фильтр и обязательный ключ документа"),
            ("Базовый договор", "Договор контрагента", "f_kod1c -> dogcode, номер, дата, организация, валюта", "уровень договора"),
            ("Поставка", "договор-заявка / спецификация", "f_kod1cb/f_kod1cp -> код договора 1С, номер договора/заявки", "уровень поставки"),
            ("Счет ERP", "Счет на оплату покупателю", "номер/код 1С, дата, организация, контрагент, договор, сумма, валюта", "колонки `Счет` и `Сумма по счету`"),
            ("Оплата ERP", "Поступление/списание по банку", "f_kod1C + f_dt1C -> numpp + dtpp, договор, счет, сумма расшифровки", "колонка `Сумма оплаты`"),
            ("Исходящий акт/УПД", "Реализация товаров и услуг", "номер/дата, договор, сумма, строки `АгентскиеУслуги`/`Услуги`/`Товары`", "СФ/УПД и начисления"),
            ("Входящий акт/УПД", "Поступление товаров и услуг", "номер/дата, поставщик, договор, строка, сумма, субконто", "возмещаемые и невозмещаемые расходы"),
            ("Акт сверки 1С", "АктСверкиВзаиморасчетов", "контрагент, организация, договор, входящее/исходящее сальдо, обороты Дт/Кт", "контроль итогов"),
        ],
        [1500, 1700, 3950, 2210],
        font_size=7.8,
    )

    add_heading(doc, "5. Источники колонок матрицы", 1)
    add_body(doc, "Все суммы в строке поставки берутся из ERP в уже распределенном виде и затем проверяются с 1С. На родительских уровнях показывается сумма дочерних поставок, а не повторение значения текущего документа.")
    add_table(
        doc,
        ["Колонка", "Откуда берем в ERP", "Что проверяем в 1С / правило"],
        [
            ("Иерархия", "`veda_contacts` -> `veda_clients` -> `veda_dogs` -> `veda_specs`", "1С-справочники контрагентов и договоров нужны для проверки кодов, ИНН, КПП и признаков удаления."),
            ("№ спецификации", "`veda_specs.f_num`, `veda_specs.f_id`, коды `veda_specs.f_kod1cb/f_kod1cp`", "1С договор-заявка/спецификация: код договора и номер. Если договор не возвращен, полный `ОК` не ставить."),
            ("Счет", "`veda_schets.f_num`, `f_kod1c`, `f_dt`, связь `f_operid` с операцией поставки", "`СчетНаОплатуПокупателю`: номер/код, дата, организация, контрагент, договор."),
            ("Сумма по счету", "ERP `settlements.invoice_total` / `buyer_invoice_total`; детализация из `veda_schets.f_sum` по поставке/операции", "1С `СчетНаОплатуПокупателю.СуммаДокумента`. Колонка сверяется как счет, но не участвует в формуле `(+/-)`."),
            ("Сумма оплаты", "ERP `settlements.paid_total`; распределение `veda_acchist_docs.f_clssum`, не полная сумма банка `veda_acchist.f_sum`", "1С банковский документ и `РасшифровкаПлатежа.СуммаПлатежа` из сервиса выписки. Для поставки брать только привязанную долю."),
            ("Возмещаемые расходы", "ERP `settlements.control_reimbursable_total`; расчет через `get_expensessum` и `veda_akts_details_opers.f_sum` для возмещаемых операций", "1С акт/УПД или поступление проверяется по документу, строке, сумме и договору, если договор доступен."),
            ("Невозмещаемые расходы", "ERP `settlements.control_non_reimbursable_total`; расчет через `get_realizsum/get_profit` и правила исключения `f_addnds`/расходных корректировок", "Контроль услуг/расходов агента. По 1С сверять с УПД/СФ из колонки `№ счф`."),
            ("№ счф", "`veda_akts.f_num`, `f_kod1c`, `f_dt1c/f_dt`, `f_c1guid`, связь через детали/операции", "1С реализация/поступление/УПД/СФ. Если ERP ожидает закрывающий документ, но 1С его не возвращает, статус `Нет СФ/УПД`."),
            ("(+/-)", "ERP `settlements.customer_settlement_balance_rub`: `Сумма оплаты - Возмещаемые расходы - Невозмещаемые расходы` по поставке", "Это контроль остатка закрытия поставки. Ненулевое значение показывать как `Есть остаток`, а не как ошибку ERP/1С само по себе."),
            ("Статус", "результат правил матчинга ERP", "`ОК`, `Нет в 1С`, `Нет в ERP`, `Сумма расходится`, `Договор расходится`, `Нет СФ/УПД`, `Вопрос по НДС`, `Нет ключа 1С`, `Подтверждено строкой 1С`."),
        ],
        [1450, 3950, 3960],
        font_size=7.2,
    )

    add_heading(doc, "5.1 Минимальные select-шаблоны ERP", 2)
    add_body(doc, "Ниже не финальный SQL-код, а технические шаблоны для PHP-разработчика: они фиксируют таблицы, связи и смысловые фильтры, которые должны лечь в endpoint выгрузки и сверки.")
    add_table(
        doc,
        ["Блок", "Select-шаблон"],
        [
            ("Контекст поставки", "SELECT s.f_id, s.f_num, d.f_dogname, legal.f_name AS legal_name, contact.f_name AS client_name FROM veda_specs s JOIN veda_dogs d ON d.f_id=s.f_dogid JOIN veda_clients legal ON legal.f_id=d.f_contrid LEFT JOIN veda_contacts contact ON contact.f_id=legal.f_contactid WHERE s.f_id=:spec_id;"),
            ("Счета покупателю", "SELECT main.f_num AS account_num, main.f_dt, main.f_sum, main.f_val, line.f_operid, line.f_num AS line_num FROM veda_schets line LEFT JOIN veda_schets main ON main.f_id=line.f_maininv WHERE line.f_operid IN (SELECT si.f_id FROM veda_spec_invoices si WHERE si.f_specid=:spec_id) ORDER BY main.f_dt, main.f_num, line.f_num;"),
            ("Оплаты клиента", "SELECT ah.f_id, ah.f_ppnum, COALESCE(ah.f_dt1c, ah.f_ppdt) AS pay_dt, ahd.f_docid AS oper_id, ahd.f_clssum FROM veda_acchist_docs ahd JOIN veda_acchist ah ON ah.f_id=ahd.f_acchistid WHERE ahd.f_doctype=3 AND ah.f_type=0 AND ahd.f_docid IN (SELECT si.f_id FROM veda_spec_invoices si WHERE si.f_specid=:spec_id);"),
            ("Возмещаемые расходы", "SELECT SUM(get_expensessum(si.f_id)) AS reimbursable_total FROM veda_spec_invoices si WHERE si.f_specid=:spec_id AND si.f_isvozm=1 AND <исключения текущей ERP-логики>;"),
            ("Невозмещаемые расходы", "SELECT SUM(CASE WHEN si.f_isvozm<>1 AND NOT (get_expensessum(si.f_id)>0 AND get_profit(si.f_id)<0) THEN get_profit(si.f_id) ELSE 0 END) AS non_reimbursable_total FROM veda_spec_invoices si WHERE si.f_specid=:spec_id;"),
            ("СФ/УПД/акты", "SELECT a.f_kod1c, COALESCE(a.f_dt1c, a.f_dt) AS akt_dt, COALESCE(ado.f_operid, a.f_operid) AS oper_id FROM veda_akts a LEFT JOIN veda_akts_details ad ON ad.f_aktid=a.f_id LEFT JOIN veda_akts_details_opers ado ON ado.f_detailid=ad.f_id WHERE COALESCE(ado.f_operid, a.f_operid) IN (SELECT si.f_id FROM veda_spec_invoices si WHERE si.f_specid=:spec_id);"),
        ],
        [1900, 7460],
        font_size=6.7,
    )

    add_heading(doc, "6. Сценарии статусов", 1)
    add_body(doc, "Статус ставится на минимальном уровне, где система смогла выполнить проверку: документ, строка документа или поставка. На родительских уровнях показывается худший статус дочерних строк и количество проблем.")
    add_table(
        doc,
        ["Статус на экране", "Технический статус", "Что сравниваем", "Когда ставим"],
        [
            ("ОК", "MATCH", "код 1С, дата, тип, организация, контрагент, договор/поставка, сумма", "документ или строка 1С однозначно совпали с ERP"),
            ("Нет в 1С", "NOT_FOUND_IN_1C", "ERP-документ с кодом 1С против выгрузки 1С", "в ERP есть код 1С, но в 1С-источнике документ не найден"),
            ("Нет в ERP", "NOT_FOUND_IN_ERP", "1С-документ против ERP-документов поставки/периода", "в 1С есть документ, который не привязался к ERP"),
            ("Сумма расходится", "FIELDS_MISMATCH_SUM", "код/дата/договор совпали, затем сравнили сумму", "документ найден, но сумма ERP отличается от суммы 1С или 1С содержит общий документ вместо доли поставки"),
            ("Договор расходится", "FIELDS_MISMATCH_CONTRACT", "код/дата/сумма строки и договор-заявка", "строка 1С найдена, но 1С не вернула договор/заявку или вернула другой договор"),
            ("Нет ключа 1С", "NOT_COMPARABLE", "наличие `f_kod1c`, даты и типа документа", "строка ERP не имеет надежного ключа; по одной сумме матчинг запрещен"),
            ("Есть остаток", "OPEN_BALANCE", "ERP-формула `(+/-)` по `settlements.customer_settlement_balance_rub`", "ERP/1С могут совпадать, но поставка имеет ненулевой остаток: `оплаты - возмещаемые - невозмещаемые`"),
            ("Нет СФ/УПД", "MISSING_UPD_SF", "ERP ожидает закрывающий акт/УПД/СФ по `veda_akts` и связям деталей; 1С не возвращает документ с тем же кодом/датой/суммой/договором", "статус не выводится из типа операции; он ставится только когда есть ожидаемый закрывающий документ ERP и нет соответствия в 1С"),
            ("Вопрос по НДС", "VAT_MISMATCH", "ставка и сумма НДС ERP против ставки и суммы НДС строки/документа 1С", "проверяем только НДС ERP ↔ 1С; возмещаемость `isvozm` не является основанием для этого статуса"),
            ("Ограничение", "domain warning", "поставки с корректировками, смешанные валюты, ручные курсовые, общий акт на несколько поставок", "текущая модель видит данные, но без дополнительного среза не может дать зеленый статус"),
        ],
        [1700, 1750, 3200, 2710],
        font_size=6.8,
    )
    add_body(doc, "`Нет СФ/УПД` определяется не по операции и не по возмещаемости. ERP должна сначала показать, что по поставке ожидается закрывающий документ из `veda_akts`/деталей/связей с операциями. Затем этот документ ищется в нормализованной выгрузке 1С по коду 1С, дате, организации, контрагенту, договору и сумме. Если сервис 1С пока отдает только акты без отдельного номера счет-фактуры, статус должен называться `Нет закрывающего документа 1С` или `Нет УПД/СФ`, но не выводиться из типа услуги.")
    add_table(
        doc,
        ["Сценарий", "Что сравниваем", "Ожидаемый статус"],
        [
            ("Документ найден полностью", "ERP `f_kod1c/f_dt1c` + сумма + договор поставки совпали с 1С", "ОК"),
            ("ERP акт есть, 1С акт/УПД не вернул", "`veda_akts` ожидает закрывающий документ, но в 1С нет документа с тем же ключом", "Нет СФ/УПД или Нет в 1С"),
            ("1С документ найден, но сумма общая", "ключ документа найден, ERP-доля через `veda_akts_details_opers.f_sum` не равна полной сумме 1С", "Сумма расходится или Ограничение"),
            ("1С строка услуги без заявки", "сумма/содержание подтверждают строку, но договор-заявка в 1С не возвращен", "Подтверждено строкой 1С, не ОК"),
            ("НДС отличается", "ERP ставка/сумма НДС против 1С ставки/суммы НДС", "Вопрос по НДС"),
            ("Поставка закрыта не в ноль", "`SUM(оплаты) - возмещаемые - невозмещаемые`", "Есть остаток"),
        ],
        [2200, 4860, 2300],
        font_size=7.6,
    )

    add_heading(doc, "7. Правила сумм и распределений", 1)
    for text in [
        "Сумма не выбирает кандидата. Сумма только проверяет уже найденный документ или строку.",
        "Для оплат в поставке использовать сумму распределения `veda_acchist_docs.f_clssum`, а не полную сумму банковского документа.",
        "Для актов и УПД, разделенных между поставками, использовать `veda_akts_details_opers.f_sum`, а не полную строку акта.",
        "`(+/-)` считается в ERP: сумма оплат минус возмещаемые расходы минус невозмещаемые расходы. `Сумма по счету` отображается и сверяется отдельно, но не входит в эту формулу.",
        "`isvozm = 1` - возмещаемые расходы. `isvozm = 2` - невозмещаемый расход/услуга по правилам ERP; для матрицы это контроль затрат, а не автоматическое увеличение взаиморасчета с клиентом.",
        "`isvozm = 0` классифицируется по типу операции ERP и не должен автоматически попадать в возмещаемые или невозмещаемые без правила.",
        "Строка 1С `Услуги` без договора-заявки может подтверждать сумму расхода, но не дает полный `ОК` по поставке и не должна получать договор клиента как бухгалтерскую аналитику.",
        "Строка 1С `АгентскиеУслуги` может дать полный `ОК`, если есть комитент и договор с комитентом/договор поставки.",
        "Если документная детализация отображается внутри поставки, строка документа показывает собственную сумму и собственную дельту. Нельзя повторять `(+/-)` поставки в каждой документной строке.",
    ]:
        add_bullet(doc, text)

    add_heading(doc, "8. Excel-выгрузка", 1)
    add_body(doc, "Excel выгружается не как экранная матрица, а как бухгалтерская ведомость по референсу. Основной лист должен быть удобен для печати и сверки вручную: одна спецификация - один вертикальный блок строк счетов.")
    add_table(
        doc,
        ["Лист", "Назначение", "Обязательные требования"],
        [
            ("Выгрузка", "основная бухгалтерская ведомость", "`№ спецификации`, `Возмещаемые расходы`, `Невозмещаемые расходы`, `№ счф`, `(+/-)` объединяются по количеству строк счетов; счета и оплаты идут строками; деньги вправо; колонки окрашены по смысловым зонам"),
            ("Правила", "техническая памятка для разработчика и бухгалтера", "по каждой колонке: ERP-источник, select-шаблон, правило расчета, контроль с 1С; этот лист заменяет техническую расшифровку, чтобы выгрузка не раздувалась лишними строками"),
        ],
        [1500, 2450, 5410],
        font_size=8.1,
    )
    add_body(doc, "Колонки листа `Выгрузка`: № спецификации, Счет, Сумма по счету, Сумма оплаты, Возмещаемые расходы, Невозмещаемые расходы, № счф, (+/-). `Сумма по счету` показывает номинал счета в его валюте и не участвует в формуле `(+/-)`. Формула `(+/-)` на блок спецификации: `SUM(Сумма оплаты) - Возмещаемые расходы - Невозмещаемые расходы`.")
    add_body(doc, "В колонке `№ счф` показывать все связанные закрывающие документы, каждый номер с новой строки внутри объединенной ячейки. Не использовать сокращение `+N документов`: бухгалтер должен видеть полный список актов/УПД/СФ прямо в ведомости.")

    add_heading(doc, "9. Требования к чтению из 1С", 1)
    add_body(doc, "Текущих шапок документов недостаточно для полной сверки поставок. Сервис 1С должен отдавать строки актов/УПД в read-only режиме.")
    add_table(
        doc,
        ["Что вернуть из 1С", "Зачем нужно"],
        [
            ("строки `Услуги`, `АгентскиеУслуги`, `Товары`", "понять, какая сумма относится к конкретной поставке"),
            ("номер строки, номенклатура, содержание, сумма, НДС", "сопоставить с деталями ERP"),
            ("код договора строки, если есть", "полный match по договору"),
            ("комитент и договор комитента", "полный match по `АгентскиеУслуги`"),
            ("счет затрат, подразделение, субконто 1/2/3", "диагностика строк `Услуги`"),
            ("признак отсутствия договора-заявки", "статус `Подтверждено строкой 1С`, а не `ОК`"),
        ],
        [3600, 5760],
        font_size=8.4,
    )

    add_heading(doc, "10. Проверочные кейсы и ограничения", 1)
    add_table(
        doc,
        ["Кейс", "Ожидаемое поведение"],
        [
            ("Клиент с несколькими ЮЛ", "на уровне клиента отображаются все ЮЛ из связи `veda_clients.f_contactid`; договоры берутся через `veda_dogs.f_contrid`"),
            ("Документ 00БП-013350", "полная строка 1С 5 000 не повторяется в каждой заявке; в матрице показываются доли ERP 1980.91, 1333.16, 1685.93 через `veda_akts_details_opers.f_sum`; без договора-заявки полный `ОК` не ставится"),
            ("ERP snapshot: заявка 921", "`Сумма по счету = 166 901,52`, `Сумма оплаты = 833 921,00`, `Возмещаемые = 829 165,90`, `Невозмещаемые = 67 055,99`, `(+/-) = -62 300,89`; формула `(+/-)` не использует колонку `Сумма по счету`."),
            ("Документ 00БП-013275", "по строкам `АгентскиеУслуги` возможен полный match, если 1С возвращает договор комитента/поставки"),
            ("ERP-строка без кода/даты 1С", "статус `Нет ключа 1С`; такая строка не матчится только по сумме"),
            ("Оплата одной выпиской на несколько поставок", "в поставке отображается только распределенная сумма, а не весь банковский документ"),
            ("Сложная поставка 15823 / 20.216", "текущий модуль не падает, но дает `24 MATCH / 30`: 3 расхождения суммы, 2 расхождения договора, 1 `Нет ключа 1С`; кейс фиксируется как ограничение текущей модели"),
        ],
        [2700, 6660],
        font_size=8.4,
    )

    add_heading(doc, "11. Поставки с корректировками", 1)
    add_body(doc, "В ERP есть поставки, где данные взаиморасчетов формируются не одной линейной цепочкой, а через основной отчет и последующие корректировки закрытия периода. Основной отчет считается срезом `0`, все срезы `> 0` считаются корректировками и должны отображаться отдельным уровнем внутри поставки.")
    add_table(
        doc,
        ["Наблюдение по 15823", "Факт проверки текущим модулем"],
        [
            ("Объем данных", "42 операции, 16 счетов, 41 акт/УПД, 21 банковская привязка"),
            ("Валютный блок", "операции покупки валюты, валютного перевода, комиссии банка и ручной курсовой разницы"),
            ("Смешанные валюты", "счета клиенту в CNY, USD и RUB; сальдо по счетам нельзя сворачивать одной рублевой формулой"),
            ("Итог сравнения с 1С", "`24 MATCH / 30`, `FIELDS_MISMATCH_SUM = 3`, `FIELDS_MISMATCH_CONTRACT = 2`, `NOT_COMPARABLE = 1`"),
            ("Остаток", "`оплаты - закрывающие документы = 897,50`; статус поставки `Есть незакрытый остаток`"),
            ("Надежный источник корректировки", "связь операций с конкретным срезом отчета, а не только общий `correctid` у строки"),
        ],
        [3000, 6360],
        font_size=8.0,
    )
    for text in [
        "Текущий API строит срез по всей поставке через связи поставки, но должен уметь группировать строки по номеру среза отчета: `0` - основной отчет, `> 0` - корректировки.",
        "Если группировка по номеру корректировки не передана в матрицу, строки разных закрытий могут смешиваться, а часть сумм 1С будет выглядеть как расхождение при корректном бизнес-смысле.",
        "Для поставок с корректировками нужен отдельный уровень иерархии: договор -> поставка -> основной отчет / корректировка N -> документы. Иначе бухгалтер не поймет, к какому закрытию относится сумма.",
        "Строки покупки валюты, валютного перевода и ручной курсовой не должны автоматически попадать в обычные возмещаемые/невозмещаемые расходы матрицы; для них нужен отдельный сценарий валютного закрытия.",
        "Если документ 1С является общим актом на несколько операций/поставок, ERP должна сравнивать долю через `veda_akts_details_opers.f_sum`. Если 1С отдает только общую сумму документа, ставить `Сумма расходится` или `Ограничение`, а не `ОК`.",
    ]:
        add_bullet(doc, text)

    add_heading(doc, "12. Журналирование расхождений", 1)
    add_body(doc, "Каждый запуск сверки должен сохранять не только итоговый статус, но и техническую причину. Это нужно для последующего анализа: какие типы операций чаще ломаются, где системно не хватает кодов 1С, где расходится НДС, где 1С не возвращает договор строки.")
    add_table(
        doc,
        ["Таблица", "Ключевые поля", "Назначение"],
        [
            ("reconciliation_runs", "id, created_at, user_id, period_from, period_to, org_id, contact_id, legal_id, spec_count, match_count, mismatch_count, service_version, duration_ms", "одна запись на запуск сверки или выгрузки"),
            ("reconciliation_mismatch_log", "id, run_id, spec_id, spec_num, dog_id, org_id, erp_doc_id, erp_oper_id, onec_doc_key, doc_kind, mismatch_type, field_name, erp_value, onec_value, diff_amount, currency, nds_erp, nds_1c, operation_type_id, operation_type_name, isvozm, severity, matching_rule, evidence_json", "одна запись на конкретную причину расхождения"),
            ("reconciliation_status_snapshot", "run_id, hierarchy_level, node_id, parent_id, status, invoice_total, paid_total, reimbursable_total, non_reimbursable_total, balance_total", "срез итогов, который был показан пользователю на экране"),
        ],
        [2100, 4500, 2760],
        font_size=6.9,
    )
    add_body(doc, "Минимальный справочник `mismatch_type`: `NOT_FOUND_IN_1C`, `NOT_FOUND_IN_ERP`, `FIELDS_MISMATCH_SUM`, `FIELDS_MISMATCH_CONTRACT`, `VAT_MISMATCH`, `MISSING_UPD_SF`, `OPEN_BALANCE`, `NOT_COMPARABLE`, `SYSTEM_LIMITATION`. Индексы нужны по `run_id`, `created_at`, `spec_id`, `mismatch_type`, `operation_type_id`, `doc_kind`, `org_id/contact_id`.")
    add_body(doc, "В отчете по логам нужны группировки: количество расхождений по типу ошибки, по типу операции ERP, по документу (`schet/payment/akt/upd/sf/balance`), по организации, клиенту и месяцу. Это позволит увидеть, например, что `VAT_MISMATCH` возникает только на конкретных типах услуг, а `FIELDS_MISMATCH_CONTRACT` - на строках 1С `Услуги`, где не возвращен договор-заявка.")

    add_heading(doc, "13. Критерии готовности", 1)
    criteria = [
        "Матрица строится в иерархии клиент -> ЮЛ -> договор -> поставка -> основной отчет/корректировка -> документы.",
        "Верхние уровни показывают итоги, документы раскрываются только внутри поставки.",
        "Кнопка `Сверить с 1С` запускает чтение ERP и 1С, нормализацию и расчет статусов.",
        "Кнопка `Выгрузить` формирует Excel с листами `Выгрузка` и `Правила`; отдельный технический лист расшифровки не нужен.",
        "Режим `Ошибки` показывает только проблемные строки и карточку причины: поле, ERP-источник, 1С-источник, разница, действие.",
        "Полная сумма документа не повторяется на каждой поставке; используется распределенная сумма ERP, а родительские строки суммируют дочерние поставки.",
        "Для `Услуги` без договора-заявки не ставится полный `ОК`.",
        "Для `АгентскиеУслуги` с договором комитента возможен полный `ОК`.",
        "Все денежные значения в интерфейсе и Excel выровнены вправо, родительские строки визуально отличаются от деталей.",
        "Каждый проблемный статус записывается в журнал расхождений с типом ошибки, типом операции ERP, документом, суммой, НДС и правилом матчинга.",
    ]
    for item in criteria:
        add_bullet(doc, item)

    doc.save(OUT)
    return OUT


if __name__ == "__main__":
    print(build_doc())
