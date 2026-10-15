(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.DpiParser = factory();
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const groups = {
        names: ['Primer_Nombre', 'Segundo_Nombre'],
        surnames: ['Primer_Apellido', 'Segundo_Apellido'],
        cui: ['cui'], birth: ['Fecha_nac']
    };

    function plain(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
    }

    function distance(a, b) {
        let row = Array.from({ length: b.length + 1 }, (_, i) => i);
        for (let i = 0; i < a.length; i++) {
            const next = [i + 1];
            for (let j = 0; j < b.length; j++) {
                next[j + 1] = Math.min(next[j] + 1, row[j + 1] + 1, row[j] + (a[i] === b[j] ? 0 : 1));
            }
            row = next;
        }
        return row[b.length];
    }

    // Fuzzy matching is restricted to header words, never used to "correct" a person's name.
    function label(value) {
        const original = String(value || '').trim().normalize('NFC');
        const text = plain(original).replace(/0/g, 'O').replace(/1/g, 'I');
        let kind, match;
        const patterns = [
            ['end', /^(?:NACIONALIDAD|NATIONALITY|PAIS|COUNTRY|SEXO|GENDER|FIRMA|HOLDER|SIGNATURE|EMISION|VENCIMIENTO|EXPIRY|VALID|REPUBLICA|REGISTRO|DOCUMENTO|GUATEMALA)\b/],
            ['birth', /^(?:FECHA\s*(?:DE\s*)?NAC(?:IMIENTO)?|DATE\s+OF\s+BIRTH|NACIMIENTO)\b/],
            ['names', /^(?:NOMBRES?|GIVEN\s+NAMES?)\b/],
            ['surnames', /^(?:APELLIDOS?|SURNAMES?)\b/],
            ['cui', /^(?:CUI|CODIGO\s+UNICO\s+DE\s+IDENTIFICACION)\b/],
            ['end', /^(?:FECHA|DATE|NATIONAL|PERSONAL|CODIGO)\b/]
        ];
        for (const entry of patterns) {
            match = text.match(entry[1]);
            if (match) { kind = entry[0]; break; }
        }
        if (!match) {
            const word = text.match(/^[A-Z]{6,9}\b/);
            if (word) {
                const headers = [['names', 'NOMBRE'], ['names', 'NOMBRES'], ['surnames', 'APELLIDO'], ['surnames', 'APELLIDOS']];
                const found = headers.find(header => distance(word[0], header[1]) <= (header[1].length >= 8 ? 2 : 1));
                if (found) { kind = found[0]; match = word; }
            }
        }
        if (!match) return null;
        let tail = original.slice(match[0].length).replace(/^[\s:/|.\-]+/, '');
        tail = tail.replace(/^(?:\/?\s*(?:GIVEN\s+NAMES?|SURNAMES?|DATE\s+OF\s+BIRTH|CUI))\b[\s:/|.\-]*/i, '');
        return { kind, tail };
    }

    function section(value, kind, unlabelled) {
        const result = [];
        const lines = String(value || '').split(/\r?\n/);
        const hasHeader = lines.some(line => { const header = label(line); return header && header.kind === kind; });
        let active = !!unlabelled && !hasHeader;
        for (const line of lines) {
            const header = label(line);
            if (header) {
                if (header.kind === kind) {
                    active = true;
                    if (header.tail) result.push(header.tail);
                } else if (active) {
                    break;
                }
            } else if (active && line.trim()) result.push(line.trim());
        }
        return result.join('\n');
    }

    const datePattern = /\b([0-9OI]{1,2})[\s/.-]*(ENE(?:RO)?|FEB(?:RERO)?|MAR(?:ZO)?|ABR(?:IL)?|MAY(?:O)?|JUN(?:IO)?|JUL(?:IO)?|Y?AG[O0](?:STO)?|SEP(?:TIEMBRE)?|SET(?:IEMBRE)?|OCT(?:UBRE)?|NOV(?:IEMBRE)?|DIC(?:IEMBRE)?|0?[1-9]|1[0-2])[\s/.-]*([0-9OI]{4})\b/;

    function date(value) {
        const months = { ENE: '01', FEB: '02', MAR: '03', ABR: '04', MAY: '05', JUN: '06',
            JUL: '07', AGO: '08', SEP: '09', SET: '09', OCT: '10', NOV: '11', DIC: '12' };
        const match = plain(value).match(datePattern);
        if (!match) return '';
        const digits = token => token.replace(/O/g, '0').replace(/I/g, '1');
        const monthToken = match[2].replace(/^Y(?=AG)/, '').replace(/0/g, 'O');
        const month = months[monthToken.slice(0, 3)] || match[2].padStart(2, '0');
        const iso = digits(match[3]) + '-' + month + '-' + digits(match[1]).padStart(2, '0');
        const parsed = new Date(iso + 'T00:00:00Z');
        return !isNaN(parsed.getTime()) && parsed.toISOString().slice(0, 10) === iso ? iso : '';
    }

    function cui(value) {
        // Correct letter/digit confusions only inside an otherwise numeric, single-line CUI.
        const candidates = plain(value).match(/[0-9OIL][0-9OIL \t-]*[0-9OIL]/g) || [];
        for (const candidate of candidates) {
            const compact = candidate.replace(/[ \t-]/g, '');
            if (compact.length === 13 && (compact.match(/\d/g) || []).length >= 10) {
                return compact.replace(/O/g, '0').replace(/[IL]/g, '1');
            }
        }
        return '';
    }

    function nameLines(value) {
        return String(value || '').split(/\r?\n/).map(line => line.trim().replace(/^[^\p{L}]+|[^\p{L}.]+$/gu, ''))
            .filter(line => line && !label(line) && !/^(?:GTM|MASCULINO|FEMENINO|MALE|FEMALE|CENTROAMERICA)$/i.test(plain(line))
                && /^[\p{L}][\p{L}\s.'’\-]*$/u.test(line));
    }

    function splitNames(lines, surname) {
        if (!lines.length) return ['', ''];
        if (surname && lines.length > 1) return [lines[0], lines.slice(1).join(' ')];
        const words = lines.join(' ').split(/\s+/);
        let first = words.shift();
        if (surname) {
            while (/^(?:DE|DEL|LA|LAS|LOS|SAN|SANTA|VAN|VON|DER)$/i.test(plain(first.split(' ').slice(-1)[0])) && words.length) {
                first += ' ' + words.shift();
            }
        }
        return [first, words.join(' ')];
    }

    function extract(input) {
        input = typeof input === 'string' ? { text: input } : (input || {});
        const text = input.text || '';
        const names = splitNames(nameLines(section(input.names || text, 'names', !!input.names)), false);
        const surnames = splitNames(nameLines(section(input.surnames || text, 'surnames', !!input.surnames)), true);
        const birthText = section(input.birth || text, 'birth', !!input.birth);
        let birth = date(birthText);
        const now = new Date();
        const today = [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('-');
        if (birth > today) birth = '';
        return {
            Primer_Nombre: names[0], Segundo_Nombre: names[1],
            Primer_Apellido: surnames[0], Segundo_Apellido: surnames[1],
            cui: cui(input.cui || '') || cui(text), Fecha_nac: birth
        };
    }

    function analyze(samples) {
        if (!Array.isArray(samples)) samples = [samples];
        const values = extract({});
        const metadata = {}, groupStates = {};
        for (const [group, fields] of Object.entries(groups)) {
            const variants = new Map();
            samples.forEach((sample, index) => {
                if (!sample) return;
                const parsed = extract(sample);
                if (!parsed[fields[0]]) return;
                const parts = fields.map(field => parsed[field]);
                const key = JSON.stringify(parts.map(part => part.normalize('NFC').toUpperCase()));
                let confidence = Number(sample.confidence && typeof sample.confidence === 'object' ? sample.confidence[group] : sample.confidence) || 0;
                const clipped = typeof sample.clipped === 'object' ? sample.clipped[group] : sample.clipped;
                if (clipped || sample.unanchored) confidence = Math.min(confidence, 45);
                const variant = variants.get(key) || { parts, confidence: 0, sources: new Set(), credible: new Set() };
                if (group === 'birth') {
                    const birthText = section(sample.birth || sample.text || (typeof sample === 'string' ? sample : ''), 'birth', !!sample.birth);
                    const dates = (plain(birthText).match(new RegExp(datePattern.source, 'g')) || []).map(date).filter(Boolean);
                    if (new Set(dates).size > 1) variant.ambiguous = true;
                }
                if (confidence >= variant.confidence) variant.parts = parts;
                variant.confidence = Math.max(variant.confidence, confidence);
                variant.sources.add(sample.source || index);
                // Numeric whitelists can return zero confidence even for a correct 13-digit row.
                // They may corroborate a strong full-page candidate, but never become strong on their own.
                if (confidence >= 60 || (group === 'cui' && sample.confidenceMissing && !clipped)) variant.credible.add(sample.source || index);
                variants.set(key, variant);
            });
            const candidates = Array.from(variants.values()).sort((a, b) =>
                (b.confidence + Math.max(0, Math.min(2, b.credible.size - 1)) * 12) - (a.confidence + Math.max(0, Math.min(2, a.credible.size - 1)) * 12));
            const best = candidates[0];
            const conflict = candidates.filter(candidate => candidate.confidence >= 65).length > 1 || candidates.some(candidate => candidate.ambiguous);
            const state = !best ? 'missing' : best.confidence >= 65 && best.credible.size >= 2 && !conflict ? 'recognized' : 'review';
            groupStates[group] = { status: state, confidence: best ? best.confidence : 0, conflict, agreement: best ? best.credible.size : 0 };
            fields.forEach((field, index) => {
                values[field] = best ? best.parts[index] : '';
                metadata[field] = Object.assign({}, groupStates[group], { status: values[field] ? state : index ? 'empty' : 'missing' });
            });
        }
        return { values, fields: metadata, groups: groupStates, complete: Object.values(groupStates).every(group => group.status === 'recognized') };
    }

    return { extract, analyze, date, cui, label, groups };
}));
