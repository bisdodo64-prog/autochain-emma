/** Génère et télécharge / ouvre un PDF simple côté client */
export function buildPdfBlob(doc) {
  const title = String(doc.name || 'Document').replace(/[()\\]/g, '')
  const lines = [
    title,
    `Vehicule: ${doc.vehicle || 'N/A'}`,
    `Type: ${doc.type || 'Document'}`,
    `Date: ${doc.date || doc.expiry || new Date().toLocaleDateString('fr-FR')}`,
    doc.blockchainVerified ? 'Statut: Certifie Blockchain' : 'Statut: Non certifie'
  ]
  const textOps = lines
    .map((line, i) => `BT /F1 12 Tf 40 ${220 - i * 22} Td (${line}) Tj ET`)
    .join('\n')
  const stream = textOps
  const content = `%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 400 280] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>
endobj
4 0 obj
<< /Length ${stream.length} >>
stream
${stream}
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000010 00000 n 
0000000062 00000 n 
0000000119 00000 n 
0000000278 00000 n 
0000000000 00000 n 
trailer
<< /Root 1 0 R /Size 6 >>
startxref
0
%%EOF`
  return new Blob([content], { type: 'application/pdf' })
}

export function downloadPdf(doc, filename) {
  const blob = buildPdfBlob(doc)
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename || `${doc.name || 'document'}.pdf`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  setTimeout(() => URL.revokeObjectURL(url), 2000)
}

export function openPdf(doc) {
  const blob = buildPdfBlob(doc)
  const url = URL.createObjectURL(blob)
  window.open(url, '_blank', 'noopener,noreferrer')
  setTimeout(() => URL.revokeObjectURL(url), 5000)
}
