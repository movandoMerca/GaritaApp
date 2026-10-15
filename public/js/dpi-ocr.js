(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory(require('./dpi-parser.js'));
    else root.DpiOcr = factory(root.DpiParser);
}(typeof self !== 'undefined' ? self : this, function (parser) {
    'use strict';

    const fallback = {
        cui: [0.015, 0.19, 0.38, 0.20], names: [0.28, 0.19, 0.48, 0.22],
        surnames: [0.28, 0.34, 0.48, 0.25], birth: [0.28, 0.65, 0.48, 0.25]
    };
    const titles = { names: 'nombres', surnames: 'apellidos', cui: 'CUI', birth: 'nacimiento' };

    function scale(source, width, upscale) {
        const sourceWidth = source.naturalWidth || source.width;
        const sourceHeight = source.naturalHeight || source.height;
        const ratio = upscale ? width / sourceWidth : Math.min(1, width / sourceWidth);
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(sourceWidth * ratio));
        canvas.height = Math.max(1, Math.round(sourceHeight * ratio));
        const ctx = canvas.getContext('2d', { alpha: false });
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.imageSmoothingEnabled = true;
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(source, 0, 0, canvas.width, canvas.height);
        return canvas;
    }

    function quality(source) {
        const probe = scale(source, 700, false);
        const ctx = probe.getContext('2d', { willReadFrequently: true });
        // Measure text columns rather than the large portrait or the edge of the card.
        const pixels = ctx.getImageData(Math.round(probe.width * 0.30), Math.round(probe.height * 0.19),
            Math.max(3, Math.round(probe.width * 0.39)), Math.max(3, Math.round(probe.height * 0.65)));
        const w = pixels.width, h = pixels.height;
        const gray = new Float32Array(w * h);
        let sum = 0, squares = 0, edges = 0, edgeSquares = 0, count = 0;
        for (let i = 0; i < gray.length; i++) {
            gray[i] = pixels.data[i * 4] * 0.299 + pixels.data[i * 4 + 1] * 0.587 + pixels.data[i * 4 + 2] * 0.114;
            sum += gray[i]; squares += gray[i] * gray[i];
        }
        for (let y = 1; y < h - 1; y++) {
            for (let x = 1; x < w - 1; x++) {
                const i = y * w + x;
                const edge = 4 * gray[i] - gray[i - 1] - gray[i + 1] - gray[i - w] - gray[i + w];
                edges += edge; edgeSquares += edge * edge; count++;
            }
        }
        const sharpness = count ? Math.max(0, edgeSquares / count - Math.pow(edges / count, 2)) : 0;
        const contrast = Math.sqrt(Math.max(0, squares / gray.length - Math.pow(sum / gray.length, 2)));
        const warnings = [];
        if (source.width < 900) warnings.push('El DPI tiene poca resolución. Acerque la tarjeta o aumente la resolución de DroidCam.');
        if (sharpness < 12 || contrast < 10) warnings.push('Se detectó poco detalle. Mantenga la tarjeta quieta, enfoque y evite reflejos.');
        return { width: source.width, height: source.height, sharpness, contrast, warning: warnings.join(' ') };
    }

    function wordRows(data) {
        const output = [];
        (data.blocks || []).forEach(block => (block.paragraphs || []).forEach(paragraph => (paragraph.lines || []).forEach(line => {
            const words = (line.words || []).map((word, sourceOrder) => ({ word, sourceOrder }))
                .filter(item => item.word.bbox && item.word.text && item.word.text.trim())
                .map(item => ({ text: item.word.text.trim(), bbox: item.word.bbox,
                    confidence: Number(item.word.confidence) || 0, sourceOrder: item.sourceOrder }))
                .sort((a, b) => a.bbox.x0 - b.bbox.x0);
            if (!words.length) return;
            output.push({ words, bbox: {
                x0: Math.min(...words.map(word => word.bbox.x0)), y0: Math.min(...words.map(word => word.bbox.y0)),
                x1: Math.max(...words.map(word => word.bbox.x1)), y1: Math.max(...words.map(word => word.bbox.y1))
            } });
        })));
        return output.sort((a, b) => a.bbox.y0 - b.bbox.y0 || a.bbox.x0 - b.bbox.x0);
    }

    function bounds(words) {
        return {
            x0: Math.min(...words.map(word => word.bbox.x0)), y0: Math.min(...words.map(word => word.bbox.y0)),
            x1: Math.max(...words.map(word => word.bbox.x1)), y1: Math.max(...words.map(word => word.bbox.y1))
        };
    }

    function labels(rows) {
        const found = [];
        rows.forEach((row, rowIndex) => {
            for (let start = 0; start < row.words.length; start++) {
                let best = null;
                const limit = Math.min(row.words.length, start + 10);
                for (let end = start; end < limit; end++) {
                    const words = row.words.slice(start, end + 1);
                    const text = words.map(word => word.text).join(' ');
                    const parsed = parser.label(text);
                    if (!parsed) continue;
                    const labelLength = text.length - parsed.tail.length;
                    let offset = 0, count = 0;
                    words.forEach(word => {
                        const endOffset = offset + word.text.length;
                        if (endOffset <= labelLength) count++;
                        offset = endOffset + 1;
                    });
                    if (count && (!best || count > best.count)) best = {
                        kind: parsed.kind, rowIndex, start, end: start + count - 1, count,
                        words: row.words.slice(start, start + count)
                    };
                }
                if (best) found.push(Object.assign(best, { bbox: bounds(best.words) }));
            }
        });
        const selected = [];
        found.sort((a, b) => b.count - a.count || a.bbox.y0 - b.bbox.y0 || a.bbox.x0 - b.bbox.x0).forEach(header => {
            const overlaps = selected.some(other => other.rowIndex === header.rowIndex && other.kind === header.kind &&
                header.start <= other.end && other.start <= header.end);
            if (!overlaps) selected.push(header);
        });
        return selected.sort((a, b) => a.bbox.y0 - b.bbox.y0 || a.bbox.x0 - b.bbox.x0);
    }

    function median(values) {
        if (!values.length) return 0;
        const sorted = values.slice().sort((a, b) => a - b);
        const middle = Math.floor(sorted.length / 2);
        return sorted.length % 2 ? sorted[middle] : (sorted[middle - 1] + sorted[middle]) / 2;
    }

    function upperQuartile(values) {
        if (!values.length) return 0;
        const sorted = values.slice().sort((a, b) => a - b);
        return sorted[Math.max(0, Math.ceil(sorted.length * 0.75) - 1)];
    }

    function validNameWord(word) {
        return !!parser.extract({ names: word.text }).Primer_Nombre;
    }

    function numericWords(rows) {
        const candidates = [];
        rows.forEach((row, rowIndex) => {
            const sequences = [row.words, row.words.slice().sort((a, b) => a.sourceOrder - b.sourceOrder)];
            sequences.forEach(sequence => {
                for (let start = 0; start < sequence.length; start++) {
                    for (let end = start; end < sequence.length; end++) {
                        const words = sequence.slice(start, end + 1);
                        const value = parser.cui(words.map(word => word.text).join(' '));
                        if (value) {
                            candidates.push({ rowIndex, words, bbox: bounds(words), value });
                            break;
                        }
                    }
                }
            });
        });
        return candidates.sort((a, b) => a.words.length - b.words.length || a.bbox.y0 - b.bbox.y0 || a.bbox.x0 - b.bbox.x0);
    }

    function dateWords(rows) {
        const candidates = [];
        rows.forEach((row, rowIndex) => {
            for (let start = 0; start < row.words.length; start++) {
                for (let end = start; end < row.words.length; end++) {
                    const words = row.words.slice(start, end + 1);
                    const value = parser.date(words.map(word => word.text).join(' '));
                    if (value) candidates.push({ rowIndex, words, bbox: bounds(words), value });
                }
            }
        });
        return candidates.sort((a, b) => a.words.length - b.words.length || a.bbox.y0 - b.bbox.y0 || a.bbox.x0 - b.bbox.x0);
    }

    function textForWords(rows, selected, group) {
        const selectedRows = new Map();
        selected.slice().sort((a, b) => a.bbox.y0 - b.bbox.y0 || a.bbox.x0 - b.bbox.x0).forEach(word => {
            const row = rows.find(item => item.words.includes(word));
            if (!row) return;
            if (!selectedRows.has(row)) selectedRows.set(row, []);
            selectedRows.get(row).push(word);
        });
        return Array.from(selectedRows.values()).map(words => {
            const ordered = group === 'names' || group === 'surnames' ? words.slice().sort((a, b) => a.sourceOrder - b.sourceOrder) : words;
            return ordered.map(word => word.text).join(' ');
        }).join('\n');
    }

    function selectCrop(data, group) {
        const rows = wordRows(data);
        const headers = labels(rows);
        const headerWords = new Set(headers.flatMap(header => header.words));
        let selected = [], text = '';
        if (group === 'cui') {
            const target = parser.cui(data.text || '');
            const candidates = numericWords(rows);
            const candidate = candidates.find(item => item.value === target) || candidates[0];
            if (candidate) { selected = candidate.words; text = candidate.value; }
        } else if (group === 'birth') {
            const candidate = dateWords(rows)[0];
            if (candidate) selected = candidate.words;
        } else {
            selected = rows.flatMap(row => row.words).filter(word => !headerWords.has(word) && validNameWord(word));
            const typicalHeight = upperQuartile(selected.map(word => word.bbox.y1 - word.bbox.y0));
            if (typicalHeight) selected = selected.filter(word => word.bbox.y1 - word.bbox.y0 >= typicalHeight * 0.55);
        }
        if (!text) text = textForWords(rows, selected, group);
        return { text, words: selected };
    }

    function regions(data, width, height) {
        const rows = wordRows(data);
        const headers = labels(rows);
        const headerWords = new Set(headers.flatMap(header => header.words));
        const cuiCandidates = numericWords(rows);
        const pageCui = parser.cui(data.text || '');
        const birthCandidates = dateWords(rows);
        const output = {};
        for (const [group, box] of Object.entries(fallback)) {
            const anchor = headers.find(line => line.kind === group);
            let left = box[0] * width, top = box[1] * height;
            let right = left + box[2] * width, bottom = top + box[3] * height;
            let next = null;
            if (anchor) {
                const letterHeight = anchor.bbox.y1 - anchor.bbox.y0;
                left = Math.max(0, anchor.bbox.x0 - width * 0.018);
                right = Math.min(width, Math.max(right, anchor.bbox.x1 + width * (group === 'cui' ? 0.01 : 0.13)));
                next = headers.filter(line => line.rowIndex !== anchor.rowIndex && line.bbox.y0 > anchor.bbox.y1 + letterHeight * 0.3 &&
                    Math.abs(line.bbox.x0 - anchor.bbox.x0) < width * 0.17)
                    .sort((a, b) => a.bbox.y0 - b.bbox.y0)[0];
                top = anchor.bbox.y0 - letterHeight * 0.25;
                bottom = next ? next.bbox.y0 - letterHeight * 0.2 :
                    Math.min(height, Math.max(bottom, top + height * (group === 'cui' || group === 'birth' ? 0.09 : 0.14)));
            }
            let selected = [];
            let selectedText = '';
            if (group === 'cui') {
                const candidates = cuiCandidates.filter(item => item.bbox.y0 < height * 0.5 &&
                    (!anchor || (item.bbox.y1 >= anchor.bbox.y0 - (anchor.bbox.y1 - anchor.bbox.y0) &&
                        (!next || item.bbox.y0 < next.bbox.y0))));
                const candidate = candidates.find(item => item.value === pageCui) || candidates[0];
                if (candidate) { selected = candidate.words; selectedText = candidate.value; }
            } else if (group === 'birth') {
                const candidate = birthCandidates.find(item => item.bbox.y1 >= (anchor ? anchor.bbox.y0 : top) && item.bbox.y0 < bottom &&
                    item.bbox.x0 >= left && item.bbox.x0 <= right);
                if (candidate) selected = candidate.words;
            } else {
                const candidateRows = rows.map((row, rowIndex) => ({ row, rowIndex })).filter(({ row }) =>
                    row.bbox.y1 >= top && row.bbox.y0 < bottom);
                candidateRows.forEach(({ row, rowIndex }) => {
                    row.words.forEach(word => {
                        const x = (word.bbox.x0 + word.bbox.x1) / 2;
                        const y = (word.bbox.y0 + word.bbox.y1) / 2;
                        if (headerWords.has(word) || x < left || x > right || y < top || y >= bottom || !validNameWord(word)) return;
                        if (anchor && rowIndex === anchor.rowIndex && x <= anchor.bbox.x1) return;
                        selected.push(word);
                    });
                });
                if (anchor && selected.length) {
                    const anchorHeight = anchor.bbox.y1 - anchor.bbox.y0;
                    const below = selected.filter(word => word.bbox.y0 > anchor.bbox.y1 + anchorHeight * 0.12);
                    const belowHeight = upperQuartile(below.map(word => word.bbox.y1 - word.bbox.y0));
                    const adjacentLimit = Math.max(width * 0.025, anchorHeight * 1.5);
                    selected = selected.filter(word => {
                        const inline = word.bbox.y0 <= anchor.bbox.y1 + anchorHeight * 0.12 &&
                            word.bbox.x0 - anchor.bbox.x1 <= adjacentLimit;
                        const wordHeight = word.bbox.y1 - word.bbox.y0;
                        return !(inline && belowHeight > anchorHeight * 1.25 &&
                            wordHeight < anchorHeight * 0.82 && wordHeight < belowHeight * 0.65);
                    });
                    const valueHeight = upperQuartile(selected.map(word => word.bbox.y1 - word.bbox.y0));
                    if (valueHeight) selected = selected.filter(word => {
                        const wordHeight = word.bbox.y1 - word.bbox.y0;
                        return wordHeight >= Math.max(anchorHeight * 0.55, valueHeight * 0.55) &&
                            wordHeight <= Math.max(anchorHeight * 3.5, valueHeight * 2.2);
                    });
                }
            }
            selected.sort((a, b) => a.bbox.y0 - b.bbox.y0 || a.bbox.x0 - b.bbox.x0);
            const text = selectedText || textForWords(rows, selected, group);
            const valueWords = selected;
            if (valueWords.length) {
                const valueBox = bounds(valueWords);
                const pad = Math.max(8, Math.round((median(valueWords.map(word => word.bbox.y1 - word.bbox.y0)) || 16) * 0.45));
                left = Math.max(0, valueBox.x0 - pad);
                right = Math.min(width, valueBox.x1 + pad);
                top = Math.max(0, valueBox.y0 - pad);
                bottom = Math.min(height, valueBox.y1 + pad);
            }
            top = Math.max(0, top); bottom = Math.min(height, Math.max(top + 12, bottom));
            output[group] = {
                left: Math.round(left), top: Math.round(top), width: Math.max(1, Math.round(right - left)),
                height: Math.max(1, Math.round(bottom - top)),
                text, confidence: valueWords.length ? valueWords.reduce((sum, word) => sum + word.confidence, 0) / valueWords.length : 0,
                letterHeight: valueWords.length ? median(valueWords.map(word => word.bbox.y1 - word.bbox.y0)) : 0,
                clipped: valueWords.some(word => word.bbox.x0 <= 3 || word.bbox.x1 >= width - 3),
                anchored: !!anchor
            };
        }
        return output;
    }

    function crop(source, region) {
        const canvas = document.createElement('canvas');
        canvas.width = region.width + 24; canvas.height = region.height + 24;
        const ctx = canvas.getContext('2d', { alpha: false });
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(source, region.left, region.top, region.width, region.height, 12, 12, region.width, region.height);
        // Aim for readable capitals rather than stretching a short date to a 1000px-wide line.
        const target = region.letterHeight ? canvas.width * Math.max(0.6, Math.min(3, 36 / region.letterHeight)) : Math.max(1000, canvas.width);
        return scale(canvas, Math.min(1600, Math.round(target)), true);
    }

    function preprocess(source, binary) {
        const canvas = scale(source, source.width, false);
        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        const image = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const histogram = new Uint32Array(256);
        const gray = new Uint8Array(canvas.width * canvas.height);
        for (let i = 0; i < gray.length; i++) {
            gray[i] = Math.round(image.data[i * 4] * 0.299 + image.data[i * 4 + 1] * 0.587 + image.data[i * 4 + 2] * 0.114);
            histogram[gray[i]]++;
        }
        let count = 0, low = 0, high = 255;
        for (let i = 0; i < 256; i++) { count += histogram[i]; if (count >= gray.length * 0.02) { low = i; break; } }
        count = 0;
        for (let i = 255; i >= 0; i--) { count += histogram[i]; if (count >= gray.length * 0.02) { high = i; break; } }
        let integral;
        if (binary) {
            integral = new Float64Array((canvas.width + 1) * (canvas.height + 1));
            for (let y = 1; y <= canvas.height; y++) {
                let row = 0;
                for (let x = 1; x <= canvas.width; x++) {
                    row += gray[(y - 1) * canvas.width + x - 1];
                    integral[y * (canvas.width + 1) + x] = integral[(y - 1) * (canvas.width + 1) + x] + row;
                }
            }
        }
        const stride = canvas.width + 1, radius = 20;
        for (let y = 0; y < canvas.height; y++) {
            for (let x = 0; x < canvas.width; x++) {
                const i = y * canvas.width + x;
                let value = Math.max(0, Math.min(255, (gray[i] - low) * 255 / Math.max(30, high - low)));
                if (binary) {
                    const x0 = Math.max(0, x - radius), x1 = Math.min(canvas.width, x + radius + 1);
                    const y0 = Math.max(0, y - radius), y1 = Math.min(canvas.height, y + radius + 1);
                    const mean = (integral[y1 * stride + x1] - integral[y0 * stride + x1] -
                        integral[y1 * stride + x0] + integral[y0 * stride + x0]) / ((x1 - x0) * (y1 - y0));
                    value = gray[i] < mean - 20 ? 0 : 255;
                }
                image.data[i * 4] = image.data[i * 4 + 1] = image.data[i * 4 + 2] = value;
            }
        }
        ctx.putImageData(image, 0, 0);
        return canvas;
    }

    async function read(source, worker, options) {
        options = options || {};
        const deadline = Date.now() + Math.min(30000, options.timeoutMs === undefined ? 30000 : options.timeoutMs);
        const maxPasses = Math.min(9, options.maxPasses === undefined ? 9 : options.maxPasses);
        const samples = [];
        let passes = 0, timedOut = false, error = null;
        let analysis = parser.analyze(samples);
        const input = scale(source, Math.min(2400, Math.max(1800, source.width)), true);

        async function recognize(image, mode, group, whitelist) {
            if (passes >= maxPasses) return null;
            const remaining = deadline - Date.now();
            if (remaining <= 0) throw new Error('DPI_OCR_TIMEOUT');
            passes++;
            if (options.onProgress) options.onProgress(passes, group ? titles[group] : 'documento');
            let timer;
            try {
                return await Promise.race([
                    (async () => {
                        await worker.setParameters({
                            tessedit_pageseg_mode: String(mode), tessedit_char_whitelist: whitelist || '',
                            preserve_interword_spaces: '1', user_defined_dpi: '300'
                        });
                        return worker.recognize(image, {}, { text: true, blocks: true });
                    })(),
                    new Promise((_, reject) => { timer = setTimeout(() => reject(new Error('DPI_OCR_TIMEOUT')), remaining); })
                ]);
            } finally { clearTimeout(timer); }
        }

        try {
            const full = await recognize(input, 11);
            if (!full) return Object.assign(analysis, { passes, timedOut, error });
            const boxes = regions(full.data, input.width, input.height);
            const initial = { source: 'document', confidence: {}, clipped: {} };
            for (const group of Object.keys(fallback)) {
                // A full-page date without its birth label is never trusted as a birth date.
                if (boxes[group].anchored) {
                    initial[group] = boxes[group].text;
                    initial.confidence[group] = boxes[group].confidence;
                    initial.clipped[group] = boxes[group].clipped;
                }
            }
            // CUI is uniquely recognizable even when its small header was missed.
            if (!initial.cui) {
                initial.cui = boxes.cui.text || parser.cui(full.data.text);
                initial.confidence.cui = boxes.cui.text ? boxes.cui.confidence : 0;
                initial.clipped.cui = boxes.cui.clipped;
            }
            samples.push(initial);
            if (options.onSample) options.onSample(initial, boxes);
            analysis = parser.analyze(samples);

            for (let attempt = 0; attempt < 2; attempt++) {
                for (const group of Object.keys(fallback)) {
                    if (analysis.groups[group].status === 'recognized' || passes >= maxPasses) continue;
                    const image = preprocess(crop(input, boxes[group]), attempt === 1);
                    const single = group === 'cui' || group === 'birth';
                    const whitelist = group === 'cui' ? '0123456789' : group === 'birth' ? '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ/.- ' : '';
                    const result = await recognize(image, single ? 7 : 6, group, whitelist);
                    if (!result) continue;
                    const selected = selectCrop(result.data, group);
                    const confidence = selected.words.length ? selected.words.reduce((sum, word) => sum + word.confidence, 0) / selected.words.length : 0;
                    const clipped = selected.words.some(word => word.bbox.x0 <= 3 || word.bbox.x1 >= image.width - 3);
                    const sample = { [group]: selected.text, source: group + '-' + attempt, confidence, clipped,
                        confidenceMissing: group === 'cui' && confidence === 0,
                        unanchored: group === 'birth' && !boxes[group].anchored &&
                            !result.data.text.split(/\r?\n/).some(line => { const header = parser.label(line); return header && header.kind === 'birth'; }) };
                    samples.push(sample);
                    if (options.onSample) options.onSample(sample);
                    analysis = parser.analyze(samples);
                }
            }
        } catch (failure) {
            timedOut = failure.message === 'DPI_OCR_TIMEOUT';
            if (!timedOut) error = failure.message || 'No se pudo leer el documento.';
            // Terminating settles the deadline without allowing late results into the form.
            if (options.onStop) options.onStop();
        }
        return Object.assign(analysis, { passes, timedOut, error });
    }

    return { read, scale, quality, regions };
}));
