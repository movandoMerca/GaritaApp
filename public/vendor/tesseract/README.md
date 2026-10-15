# OCR local del DPI

Recursos distribuidos sin recompilar los bundles de la aplicación:

- `tesseract.js` **7.0.0**: `https://registry.npmjs.org/tesseract.js/-/tesseract.js-7.0.0.tgz`
- `tesseract.js-core` **7.0.0** (incluye variantes WebAssembly): `https://registry.npmjs.org/tesseract.js-core/-/tesseract.js-core-7.0.0.tgz`
- Modelo español `@tesseract.js-data/spa` **1.0.0**, variante `4.0.0_best_int`: `https://registry.npmjs.org/@tesseract.js-data/spa/-/spa-1.0.0.tgz`

Se incluyen las licencias Apache 2.0 de Tesseract.js, su núcleo y el repositorio
de datos `naptha/tessdata`. El paquete npm del idioma declara MIT en sus metadatos.
`SHA256SUMS` permite verificar los archivos distribuidos. Los recursos
se cargan desde esta aplicación mediante `workerPath`, `corePath` y `langPath`;
las imágenes no se envían a un servicio OCR. La foto se envía al servidor propio
únicamente al guardar la visita.
