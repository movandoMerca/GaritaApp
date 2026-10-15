const { test } = require('node:test');
const assert = require('node:assert/strict');
const parser = require('../../public/js/dpi-parser.js');

test('extracts the sample DPI by labels, without the country, gender or card serial', () => {
    assert.deepEqual(parser.extract(`REPÚBLICA DE GUATEMALA
CÓDIGO ÚNICO DE IDENTIFICACIÓN - CUI
1234 56789 0101
NOMBRE / GIVEN NAMES
MARÍA
LUCÍA
APELLIDOS / SURNAME
PÉREZ
LÓPEZ
NACIONALIDAD / NATIONALITY
GTM
SEXO / GENDER
FEMENINO
FECHA DE NACIMIENTO / DATE OF BIRTH
09AGO1990
FIRMA / HOLDER SIGNATURE
090877`), {
        Primer_Nombre: 'MARÍA', Segundo_Nombre: 'LUCÍA', Primer_Apellido: 'PÉREZ', Segundo_Apellido: 'LÓPEZ',
        cui: '1234567890101', Fecha_nac: '1990-08-09'
    });
});
test('extracts OCR regions and keeps compound surnames printed on separate lines', () => {
    const result = parser.extract({ names: 'NOMBRE GIVEN NAMES\nANA MARÍA', surnames: 'APELLIDOS SURNAME\nDE LEÓN\nLÓPEZ', cui: '1234-56789-0101', birth: 'FECHA DE NACIMIENTO\n9 / 08 / 1990' });
    assert.equal(result.Primer_Nombre, 'ANA');
    assert.equal(result.Segundo_Nombre, 'MARÍA');
    assert.equal(result.Primer_Apellido, 'DE LEÓN');
    assert.equal(result.Segundo_Apellido, 'LÓPEZ');
    assert.equal(result.Fecha_nac, '1990-08-09');
});

test('distributes one, two and three names consistently across OCR rows', () => {
    const cases = [
        ['ANDRÉS', 'ANDRÉS', ''],
        ['JUAN CARLOS', 'JUAN', 'CARLOS'],
        ['JUAN CARLOS ALEJANDRO', 'JUAN', 'CARLOS ALEJANDRO'],
        ['JUAN\nCARLOS ALEJANDRO', 'JUAN', 'CARLOS ALEJANDRO'],
        ['JUAN\nCARLOS\nALEJANDRO', 'JUAN', 'CARLOS ALEJANDRO']
    ];
    cases.forEach(([names, first, others]) => {
        const parsed = parser.extract({ names });
        assert.equal(parsed.Primer_Nombre, first, names);
        assert.equal(parsed.Segundo_Nombre, others, names);
    });
    const oneName = parser.analyze([
        { names: 'ANDRÉS', confidence: 94, source: 'document' },
        { names: 'ANDRÉS', confidence: 92, source: 'contrast' }
    ]);
    assert.equal(oneName.groups.names.status, 'recognized');
    assert.equal(oneName.fields.Segundo_Nombre.status, 'empty');
    assert.equal(oneName.values.Segundo_Nombre, '');
});
test('rejects invalid dates and does not invent data from incomplete OCR', () => {
    assert.equal(parser.date('31FEB1990'), '');
    assert.equal(parser.date('29FEB2000'), '2000-02-29');
    assert.equal(parser.date('09 de agosto de 1990'), '');
    assert.equal(parser.date('9-AGOSTO-1990'), '1990-08-09');
    assert.equal(parser.date('O9AGO1990'), '1990-08-09');
    assert.equal(parser.date('O9YAGO1990'), '1990-08-09');
    assert.equal(parser.date('09AG0199O'), '1990-08-09');
    assert.equal(parser.cui('12345678901234'), '');
    assert.equal(parser.cui('1 234 56789 0101 9'), '');
    assert.equal(parser.cui('123456\n7890101'), '');
    assert.equal(parser.extract('NACIONALIDAD GTM\n090877').Primer_Nombre, '');
    assert.equal(parser.extract({}).cui, '');
});

test('rejects corrupted headers as surnames and reads values on the label line', () => {
    const result = parser.extract('NOMBRE / GIVEN NAMES: MARÍA LUCÍA\nNPELLIDO: PEÑA\nDE LEÓN\nNACIONALIDAD GTM\nFECHA DE NACIMIENTO: 17JUL1997\nFECHA DE EMISIÓN: 15JUN2023');
    assert.equal(result.Primer_Nombre, 'MARÍA');
    assert.equal(result.Segundo_Nombre, 'LUCÍA');
    assert.equal(result.Primer_Apellido, 'PEÑA');
    assert.equal(result.Segundo_Apellido, 'DE LEÓN');
    assert.equal(result.Fecha_nac, '1997-07-17');
});

test('discards neighboring data before a region label and preserves compound surnames', () => {
    const result = parser.extract({ surnames: 'ALEJANDRO\nAPELLIDOS / SURNAME\nDE LA CRUZ\nLÓPEZ\nNACIONALIDAD\nGTM' });
    assert.equal(result.Primer_Apellido, 'DE LA CRUZ');
    assert.equal(result.Segundo_Apellido, 'LÓPEZ');
    assert.equal(parser.extract({ surnames: 'DE LEÓN LÓPEZ' }).Primer_Apellido, 'DE LEÓN');
    assert.equal(parser.extract({ names: 'ANA MARÍA\nLUISA' }).Segundo_Nombre, 'MARÍA LUISA');
});

test('birth extraction never uses issue dates, country of birth, invalid or future dates', () => {
    assert.equal(parser.extract('FECHA DE EMISIÓN\n15JUN2023').Fecha_nac, '');
    assert.equal(parser.extract('PAÍS DE NACIMIENTO\nGTM\nFECHA DE EMISIÓN\n15JUN2023').Fecha_nac, '');
    assert.equal(parser.extract({ birth: '31FEB1997' }).Fecha_nac, '');
    assert.equal(parser.extract({ birth: '17JUL2997' }).Fecha_nac, '');
});

test('corrects CUI digit confusions without concatenating unrelated lines or numbers', () => {
    assert.equal(parser.cui('I234 56789 O101'), '1234567890101');
    assert.equal(parser.cui('1234 56789 0101 99'), '');
    assert.equal(parser.cui('1234 56789\n0101'), '');
    assert.equal(parser.cui('IIII IIIII IIII'), '');
});

test('weak fallback cannot replace a good candidate or mix name pairs', () => {
    const result = parser.analyze([
        { names: 'MARÍA\nLUCÍA', surnames: 'DE LEÓN\nLÓPEZ', confidence: 94, source: 'original' },
        { names: 'MARC\nALEJANDRO', surnames: 'NPELLIDO\nOVANDO\nMORALES', confidence: 30, source: 'fallback' }
    ]);
    assert.equal(result.values.Primer_Nombre, 'MARÍA');
    assert.equal(result.values.Segundo_Nombre, 'LUCÍA');
    assert.equal(result.values.Primer_Apellido, 'DE LEÓN');
    assert.equal(result.values.Segundo_Apellido, 'LÓPEZ');
    assert.equal(result.fields.Primer_Nombre.status, 'review');
});

test('agreement from separate passes confirms data; repeated copies of one pass do not', () => {
    const sample = { names: 'MARÍA\nLUCÍA', surnames: 'PÉREZ\nLÓPEZ', cui: '1234567890101', birth: '17JUL1997', confidence: 90, source: 'original' };
    assert.equal(parser.analyze([sample, sample]).complete, false);
    const result = parser.analyze([sample, { ...sample, source: 'contrast' }]);
    assert.equal(result.complete, true);
    assert.equal(result.fields.cui.agreement, 2);
});

test('contradictory dates and clipped candidates remain visibly doubtful', () => {
    const result = parser.analyze([
        { birth: '17JUL1997', confidence: 92, source: 'original' },
        { birth: '07JUL1997', confidence: 90, source: 'contrast' },
        { birth: '17JUL1997', confidence: 92, source: 'binary' }
    ]);
    assert.equal(result.values.Fecha_nac, '1997-07-17');
    assert.equal(result.fields.Fecha_nac.status, 'review');
    assert.equal(result.fields.Fecha_nac.conflict, true);
    const clipped = parser.analyze([{ names: 'MARC', confidence: 99, clipped: true, source: 'one' }, { names: 'MARC', confidence: 99, clipped: true, source: 'two' }]);
    assert.equal(clipped.fields.Primer_Nombre.status, 'review');
    assert.equal(clipped.complete, false);
});

test('zero-confidence numeric OCR corroborates an existing strong CUI without inventing confidence', () => {
    const numeric = { cui: '1234567890101', confidence: 0, confidenceMissing: true, source: 'numeric' };
    assert.equal(parser.analyze([numeric]).fields.cui.status, 'review');
    const result = parser.analyze([{ ...numeric, source: 'document', confidence: 92 }, numeric]);
    assert.equal(result.fields.cui.status, 'recognized');
    assert.equal(result.fields.cui.confidence, 92);
    assert.equal(parser.analyze([{ ...numeric, source: 'document', confidence: 92 }, { ...numeric, clipped: true }]).fields.cui.status, 'review');
});

test('multiple birth dates in one region cannot be silently confirmed by repetition', () => {
    const sample = { birth: '17JUL1997 07JUL1997', confidence: 95, source: 'first' };
    const result = parser.analyze([sample, { ...sample, source: 'second' }]);
    assert.equal(result.fields.Fecha_nac.status, 'review');
    assert.equal(result.fields.Fecha_nac.conflict, true);
    const labelled = { text: 'FECHA DE NACIMIENTO: 17JUL1997\nFECHA DE EMISIÓN: 15JUN2023', confidence: 95, source: 'first' };
    assert.equal(parser.analyze([labelled, { ...labelled, source: 'second' }]).fields.Fecha_nac.status, 'recognized');
});

test('a date from an unlabelled fallback region always requires review', () => {
    const sample = { birth: '17JUL1997', confidence: 99, unanchored: true, source: 'first' };
    assert.equal(parser.analyze([sample, { ...sample, source: 'second' }]).fields.Fecha_nac.status, 'review');
});
