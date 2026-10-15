const { test } = require('node:test');
const assert = require('node:assert/strict');
const parser = require('../../public/js/dpi-parser.js');
const ocr = require('../../public/js/dpi-ocr.js');

const word = (text, x0, y0, x1, y1, confidence = 90) => ({ text, bbox: { x0, y0, x1, y1 }, confidence });
const data = rows => ({ blocks: [{ paragraphs: [{ lines: rows.map(words => ({ words })) }] }] });
const nameHeader = () => [word('NOMBRE', 500, 230, 600, 250, 80)];
const surnameHeader = () => [word('APELLIDOS', 500, 390, 650, 410, 80)];

test('isolates a name from small OCR noise beside the label and excludes a watermark', () => {
    const boxes = ocr.regions(data([
        [...nameHeader(), word('LAA', 610, 235, 642, 250, 20)],
        [word('ANDRÉS', 500, 280, 670, 320, 91), word('FONDO', 720, 295, 770, 305, 8)],
        surnameHeader(),
        [word('PÉREZ', 500, 440, 640, 480, 94)]
    ]), 1600, 1009);

    assert.equal(boxes.names.text, 'ANDRÉS');
    assert.equal(boxes.names.confidence, 91);
    assert.equal(boxes.names.letterHeight, 40);
    assert.equal(boxes.names.anchored, true);
    assert.equal(boxes.surnames.text, 'PÉREZ');
    assert.equal(parser.extract({ names: boxes.names.text }).Primer_Nombre, 'ANDRÉS');
});

test('keeps a legitimate value printed beside its label when no lower value is present', () => {
    const boxes = ocr.regions(data([
        [...nameHeader(), word('ANA', 660, 232, 735, 270, 92)],
        surnameHeader()
    ]), 1600, 1009);

    assert.equal(boxes.names.text, 'ANA');
    assert.equal(boxes.names.confidence, 92);
});

test('splits CUI and names that share a Tesseract row by their word coordinates', () => {
    const boxes = ocr.regions(data([
        [word('CUI', 80, 190, 130, 210, 85)],
        nameHeader(),
        [word('1234', 65, 280, 160, 320, 70), word('56789', 180, 280, 310, 320, 72),
            word('0101', 330, 280, 430, 320, 74), word('ANDRÉS', 500, 280, 670, 320, 95)],
        surnameHeader(),
        [word('PÉREZ', 500, 440, 640, 480, 93)]
    ]), 1600, 1009);

    assert.equal(boxes.cui.text, '1234567890101');
    assert.equal(boxes.cui.confidence, 72);
    assert.equal(boxes.names.text, 'ANDRÉS');
    assert.equal(boxes.names.confidence, 95);
    const parsed = parser.extract({ cui: boxes.cui.text, names: boxes.names.text, surnames: boxes.surnames.text });
    assert.equal(parsed.cui, '1234567890101');
    assert.equal(parsed.Primer_Nombre, 'ANDRÉS');
    assert.equal(parsed.Primer_Apellido, 'PÉREZ');
});

test('retains three name rows and stops before the surname label', () => {
    const boxes = ocr.regions(data([
        nameHeader(),
        [word('JUAN', 500, 275, 590, 315)],
        [word('CARLOS', 500, 325, 635, 365)],
        [word('ALEJANDRO', 500, 375, 700, 415)],
        [word('APELLIDOS', 500, 450, 650, 470, 80)],
        [word('DE', 500, 500, 545, 540), word('LEÓN', 552, 500, 650, 540)]
    ]), 1600, 1009);

    assert.equal(boxes.names.text, 'JUAN\nCARLOS\nALEJANDRO');
    assert.equal(boxes.surnames.text, 'DE LEÓN');
    const parsed = parser.extract({ names: boxes.names.text, surnames: boxes.surnames.text });
    assert.equal(parsed.Primer_Nombre, 'JUAN');
    assert.equal(parsed.Segundo_Nombre, 'CARLOS ALEJANDRO');
    assert.equal(parsed.Primer_Apellido, 'DE LEÓN');
});
