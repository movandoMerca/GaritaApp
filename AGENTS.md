# agent.md - Reglas de codificación (UTF-8) + Laravel / Livewire (ES)

## Contexto
Este proyecto es una app **Laravel + Livewire** en español y debe conservar tildes/ñ correctamente.
Problema recurrente: aparición de texto corrupto tipo:
- `mÃ©dica` (debería ser `médica`)
- `aÃ±o` (debería ser `año`)
Esto ocurre cuando contenido UTF-8 se interpreta o guarda como ISO-8859-1 / Windows-1252.

## Regla principal (OBLIGATORIA)
**Todo archivo de texto del repositorio debe estar en UTF-8.**
- Al crear o editar archivos, **guardar siempre como UTF-8 sin BOM**.
- Solo conservar BOM si un archivo existente del proyecto ya lo usa deliberadamente y hay una razón técnica comprobada.
- **Nunca** "arreglar" tildes reemplazándolas por letras sin acento (ej. `medica`) si el texto real debe llevar acento.

## Regla adicional sobre BOM (OBLIGATORIA)
**Antes de dar por terminado cualquier cambio, verificar que los archivos editados no tengan BOM (`EF BB BF`).**
- Si un archivo editado tiene BOM y no hay una razón técnica explícita para mantenerlo, quitarlo.
- Prestar especial atención a `*.blade.php`, `*.php`, `*.js`, `*.ts`, `*.json`, `*.yml`, `*.yaml`, `*.md`.
- Si Livewire no dispara eventos o `wire:model.live` no genera requests, revisar como causa posible que el archivo Blade tenga BOM.

## Cómo actuar si detectas texto corrupto (mojibake)
Si ves patrones como `Ã¡ Ã© Ã­ Ã³ Ãº Ã± Ã Ã‰ Ã Ã“ Ãš` o el carácter `�`, asume UTF-8 mal interpretado.
1. Corrige el texto al español correcto (`mÃ©dica` -> `médica`).
2. Asegura que el archivo quede guardado en **UTF-8 sin BOM**.
3. Busca ocurrencias similares en el mismo archivo y en textos relacionados (labels, UI, seeds, translations, correos, PDFs, exports, etc.).

## Reglas específicas para Laravel + Livewire
### 1) Blade / Livewire Views
- Archivos `*.blade.php` y componentes Livewire deben mantenerse en **UTF-8 sin BOM**.
- Mantener caracteres Unicode reales en UI: `médica`, `año`, `mañana`, `Guatemala`.
- Evitar introducir entidades HTML innecesarias (`&aacute;`) salvo que haya un motivo real (compatibilidad muy puntual).
- Si se tocan layouts/base templates, asegurar `<meta charset="utf-8">` en el `<head>`.

### 2) Livewire (strings, propiedades, validaciones)
- Mensajes de validación, labels, placeholders y notificaciones deben conservar tildes/ñ.
- Si se agregan mensajes de validación custom, preferir archivos de `lang/` para mantener consistencia.
- Revisar que cambios en `wire:model`, `wire:click`, `dispatchBrowserEvent`, `toast`, etc. no introduzcan texto corrupto.
- Si un componente Livewire no reacciona, verificar también que el archivo Blade raíz no tenga BOM.

### 3) Traducciones (`lang/`)
- Todo en `lang/es/*` debe estar en UTF-8.
- Preferir centralizar textos en `lang/es/validation.php`, `lang/es/messages.php` o el archivo equivalente.
- Si el proyecto usa JSON translations, `lang/es.json` debe estar en UTF-8 y sin `Ã` ni `�`.

### 4) Base de datos (MySQL/MariaDB recomendado)
Cuando se toque DB, migraciones o conexión:
- Preferir `utf8mb4` + collation compatible (ej. `utf8mb4_unicode_ci` o equivalente).
- Evitar `latin1`.
- Si se revisa configuración, usar `utf8mb4` para `character_set_server` y `collation_server` cuando sea posible.

### 5) Mails, PDFs y exports
- En Mails (`Mailable`, Blade mail templates) asegurar tildes/ñ correctas.
- En generación de PDFs (Snappy/Dompdf/etc.), confirmar que la fuente soporta Unicode y que el HTML fuente está en UTF-8.
- En exports CSV/Excel, no "normalizar" quitando tildes; asegurar export en UTF-8.

## Reglas por tipo de archivo
### Código fuente (PHP/JS/TS/SQL/etc.)
- Mantener Unicode real en strings.
- Solo usar escapes `\u00e9` o similares si hay una limitación real y documentada.

### JSON / YAML
- Guardar estrictamente en UTF-8 sin BOM.
- No introducir escapes innecesarios si ya se maneja UTF-8 correctamente.

### HTML
- Asegurar `<meta charset="utf-8">` cuando aplique.

## Entorno / tooling
- No introducir conversiones a latin1 en PHP/Laravel.
- No agregar scripts que re-encodeen archivos sin intención clara.
- Evitar pipelines CLI que cambien encoding al escribir archivos.

## Validación antes de entregar cambios
Antes de dar por finalizado un cambio que toca UI/textos:
- Verificar visualmente en navegador que **tildes/ñ** se muestran bien.
- Buscar en el diff cadenas con `Ã` o `�`.
- Verificar que los archivos editados no tengan BOM.
- Revisar especialmente: Blade views, `lang/`, seeds, mails y templates PDF.

## No hacer
- No "solucionar" quitando tildes para evitar el bug.
- No mezclar encodings en el repo.
- No asumir que `mÃ©dica` es correcto.
- Nunca correr migraciones de Laravel.

## Checklist rápido
- [ ] Archivos guardados en UTF-8 sin BOM
- [ ] No hay `Ã` / `�` en strings de UI, emails o PDFs
- [ ] No hay BOM (`EF BB BF`) en archivos editados
- [ ] `<meta charset="utf-8">` presente en layout principal si aplica
- [ ] `lang/` en español consistente y en UTF-8
- [ ] DB con `utf8mb4` cuando se tocan migraciones/config
