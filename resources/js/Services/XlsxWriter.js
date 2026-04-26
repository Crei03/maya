class XlsxWriter {
  constructor() {
    // Shared strings table — maps string → index (used by cells referencing shared strings)
    this._strings = [];
    this._stringIndex = new Map();

    // Pre-computed CRC-32 table (lazy init)
    this._crcTable = null;
  }

  // ──────────────────────────────────────────────
  //  PUBLIC API
  // ──────────────────────────────────────────────

  /**
   * Generate a complete .xlsx file as a Uint8Array (ZIP archive).
   *
   * @param {Array<{key: string, label: string}>} columns - Column definitions.
   *   The `label` is used for headers, `key` to extract values from rows.
   * @param {Array<Object>} rows - Row data objects. Each key from columns
   *   is looked up on each row. null/undefined/'' → '-'.
   * @returns {Uint8Array} Complete .xlsx file as a ZIP archive.
   */
  generate(columns, rows) {
    // 1. Reset shared strings table
    this._strings = [];
    this._stringIndex = new Map();

    // 2. Pre-process: convert all cell values to display strings
    const headers = columns.map((col) => String(col.label));

    const processedRows = rows.map((row) =>
      columns.map((col) => {
        const val = row[col.key];
        if (val === null || val === undefined || val === '') return '-';
        if (typeof val === 'object') return JSON.stringify(val);
        return String(val);
      })
    );

    // 3. Register ALL strings in the shared strings table
    //    (must happen before building sheet XML so indices are known)
    for (const h of headers) this._registerString(h);
    for (const row of processedRows) {
      for (const val of row) this._registerString(val);
    }

    // 4. Build all XML files
    const files = {
      '[Content_Types].xml': this._buildContentTypesXml(),
      '_rels/.rels': this._buildRelsXml(),
      'xl/workbook.xml': this._buildWorkbookXml(),
      'xl/_rels/workbook.xml.rels': this._buildWorkbookRelsXml(),
      'xl/styles.xml': this._buildStylesXml(),
      'xl/worksheets/sheet1.xml': this._buildSheetXml(headers, processedRows),
      'xl/sharedStrings.xml': this._buildSharedStringsXml(),
    };

    // 5. Package as ZIP
    return this._createZip(files);
  }

  // ──────────────────────────────────────────────
  //  SHARED STRINGS
  // ──────────────────────────────────────────────

  /**
   * Register a string in the shared strings table and return its index.
   * If already registered, returns the existing index (dedup).
   */
  _registerString(str) {
    if (this._stringIndex.has(str)) {
      return this._stringIndex.get(str);
    }
    const index = this._strings.length;
    this._strings.push(str);
    this._stringIndex.set(str, index);
    return index;
  }

  // ──────────────────────────────────────────────
  //  XML BUILDERS
  // ──────────────────────────────────────────────

  _buildContentTypesXml() {
    return this._xmlPreamble(`
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
</Types>`);
  }

  _buildRelsXml() {
    return this._xmlPreamble(`
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>`);
  }

  _buildWorkbookXml() {
    return this._xmlPreamble(`
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Datos" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>`);
  }

  _buildWorkbookRelsXml() {
    return this._xmlPreamble(`
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>`);
  }

  _buildStylesXml() {
    // Styles: header row gets bold white font on blue background with border;
    // data rows get thin border
    return this._xmlPreamble(`
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="2">
    <font><sz val="11"/><color rgb="FF000000"/><name val="Calibri"/></font>
    <font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>
  </fonts>
  <fills count="3">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF4472C4"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color auto="1"/></left>
      <right style="thin"><color auto="1"/></right>
      <top style="thin"><color auto="1"/></top>
      <bottom style="thin"><color auto="1"/></bottom>
      <diagonal/>
    </border>
  </borders>
  <cellStyleXfs count="1">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
  </cellStyleXfs>
  <cellXfs count="3">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">
      <alignment horizontal="center" vertical="center" wrapText="1"/>
    </xf>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
  </cellXfs>
</styleSheet>`);
  }

  _buildSharedStringsXml() {
    const count = this._strings.length;
    let xml = this._xmlPreamble(`
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="${count}" uniqueCount="${count}">`);

    for (const str of this._strings) {
      xml += `<si><t>${this._escapeXml(str)}</t></si>`;
    }
    xml += `\n</sst>`;
    return xml;
  }

  _buildSheetXml(headers, processedRows) {
    const colCount = headers.length;
    let xml = this._xmlPreamble(`
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>`);

    // ── Header row (row 1, style 1 = bold+blue+center+border) ──
    xml += `\n    <row r="1">`;
    for (let c = 0; c < colCount; c++) {
      const ref = this._colLetter(c) + '1';
      const si = this._stringIndex.get(headers[c]);
      xml += `<c r="${ref}" t="s" s="1"><v>${si}</v></c>`;
    }
    xml += `</row>`;

    // ── Data rows (row 2+, style 2 = border only) ──
    for (let r = 0; r < processedRows.length; r++) {
      const rowNum = r + 2;
      xml += `\n    <row r="${rowNum}">`;
      for (let c = 0; c < colCount; c++) {
        const ref = this._colLetter(c) + rowNum;
        const si = this._stringIndex.get(processedRows[r][c]);
        xml += `<c r="${ref}" t="s" s="2"><v>${si}</v></c>`;
      }
      xml += `</row>`;
    }

    xml += `\n    </sheetData>
</worksheet>`;
    return xml;
  }

  // ──────────────────────────────────────────────
  //  XML HELPERS
  // ──────────────────────────────────────────────

  _xmlPreamble(body) {
    return `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>${body}`;
  }

  _escapeXml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&apos;');
  }

  /**
   * Convert zero-based column index to Excel column letter(s).
   *   0 → A, 25 → Z, 26 → AA, 16383 → XFD
   */
  _colLetter(index) {
    let letter = '';
    let n = index + 1;
    while (n > 0) {
      n--;
      letter = String.fromCharCode(65 + (n % 26)) + letter;
      n = Math.floor(n / 26);
    }
    return letter;
  }

  // ──────────────────────────────────────────────
  //  ZIP GENERATOR (deflate-free, stored method)
  // ──────────────────────────────────────────────

  /**
   * Create a valid ZIP archive as a Uint8Array.
   *
   * All files are stored without compression (method 0),
   * which is valid per the ZIP specification and supported
   * by all ZIP/xlsx readers.
   */
  _createZip(files) {
    const encoder = new TextEncoder();
    const fileEntries = Object.entries(files);

    // ── First pass: encode data, compute CRC-32, size headers ──
    const prepared = fileEntries.map(([filename, content]) => {
      const data = encoder.encode(content);
      const crc = this._crc32(data);
      const nameBytes = encoder.encode(filename);
      const localHeaderSize = 30 + nameBytes.length;
      return { filename, data, crc, nameBytes, localHeaderSize };
    });

    // ── Calculate offsets and total size ──
    let dataOffset = 0;
    const entries = prepared.map((entry) => {
      const offset = dataOffset;
      dataOffset += entry.localHeaderSize + entry.data.length;
      return { ...entry, offset };
    });

    // Central directory size
    const centralDirSize = entries.reduce(
      (sum, e) => sum + 46 + e.nameBytes.length,
      0
    );
    const eocdSize = 22;
    const totalSize = dataOffset + centralDirSize + eocdSize;

    const zip = new Uint8Array(totalSize);
    let pos = 0;

    // ── Write local file headers + data ──
    for (const entry of entries) {
      // Local file header (30 bytes + filename)
      const header = new Uint8Array(entry.localHeaderSize);
      const dv = new DataView(header.buffer, header.byteOffset, header.byteLength);

      dv.setUint32(0, 0x04034b50, true);    // Signature
      dv.setUint16(4, 20, true);             // Version needed
      dv.setUint16(6, 0, true);              // General purpose bit flag
      dv.setUint16(8, 0, true);              // Compression: stored
      dv.setUint16(10, 0, true);             // Last mod time (unused)
      dv.setUint16(12, 0, true);             // Last mod date (unused)
      dv.setUint32(14, entry.crc, true);     // CRC-32
      dv.setUint32(18, entry.data.length, true);  // Compressed size
      dv.setUint32(22, entry.data.length, true);  // Uncompressed size
      dv.setUint16(26, entry.nameBytes.length, true); // Filename length
      dv.setUint16(28, 0, true);             // Extra field length
      header.set(entry.nameBytes, 30);       // Filename

      zip.set(header, pos);
      pos += header.length;
      zip.set(entry.data, pos);
      pos += entry.data.length;
    }

    const centralDirOffset = dataOffset;

    // ── Write central directory entries ──
    for (const entry of entries) {
      const ce = new Uint8Array(46 + entry.nameBytes.length);
      const dv = new DataView(ce.buffer, ce.byteOffset, ce.byteLength);

      dv.setUint32(0, 0x02014b50, true);     // Signature
      dv.setUint16(4, 20, true);              // Version made by
      dv.setUint16(6, 20, true);              // Version needed
      dv.setUint16(8, 0, true);               // Bit flag
      dv.setUint16(10, 0, true);              // Compression
      dv.setUint16(12, 0, true);              // Mod time
      dv.setUint16(14, 0, true);              // Mod date
      dv.setUint32(16, entry.crc, true);      // CRC-32
      dv.setUint32(20, entry.data.length, true);   // Compressed size
      dv.setUint32(24, entry.data.length, true);   // Uncompressed size
      dv.setUint16(28, entry.nameBytes.length, true); // Filename length
      dv.setUint16(30, 0, true);              // Extra field length
      dv.setUint16(32, 0, true);              // File comment length
      dv.setUint16(34, 0, true);              // Disk number start
      dv.setUint16(36, 0, true);              // Internal file attributes
      dv.setUint32(38, 0, true);              // External file attributes
      dv.setUint32(42, entry.offset, true);   // Relative offset
      ce.set(entry.nameBytes, 46);            // Filename

      zip.set(ce, pos);
      pos += ce.length;
    }

    // ── Write End of Central Directory Record ──
    const eocd = new Uint8Array(22);
    const dv = new DataView(eocd.buffer, eocd.byteOffset, eocd.byteLength);
    dv.setUint32(0, 0x06054b50, true);        // Signature
    dv.setUint16(4, 0, true);                  // Disk number
    dv.setUint16(6, 0, true);                  // Disk with central dir
    dv.setUint16(8, entries.length, true);     // Entries on this disk
    dv.setUint16(10, entries.length, true);    // Total entries
    dv.setUint32(12, centralDirSize, true);    // Central directory size
    dv.setUint32(16, centralDirOffset, true);  // Central directory offset
    dv.setUint16(20, 0, true);                 // Comment length
    zip.set(eocd, pos);

    return zip;
  }

  // ──────────────────────────────────────────────
  //  CRC-32
  // ──────────────────────────────────────────────

  _crc32(data) {
    const table = this._getCrcTable();
    let crc = 0xffffffff;
    for (let i = 0; i < data.length; i++) {
      crc = table[(crc ^ data[i]) & 0xff] ^ (crc >>> 8);
    }
    return (crc ^ 0xffffffff) >>> 0;
  }

  _getCrcTable() {
    if (this._crcTable) return this._crcTable;
    const table = new Uint32Array(256);
    for (let i = 0; i < 256; i++) {
      let c = i;
      for (let j = 0; j < 8; j++) {
        c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1;
      }
      table[i] = c;
    }
    this._crcTable = table;
    return table;
  }
}

export default XlsxWriter;
