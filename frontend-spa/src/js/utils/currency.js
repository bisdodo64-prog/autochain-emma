/**
 * Format monétaire en FCFA (Franc CFA).
 */
export function formatFcfa(amount) {
  const n = Math.round(Number(amount) || 0)
  return `${n.toLocaleString('fr-FR')} FCFA`
}

/**
 * Convertit un montant € éventuel vers FCFA (approx. démo).
 * Si la valeur est déjà élevée (> 1000), on la traite comme FCFA.
 */
export function toFcfa(amount, { assumeEuroBelow = 1000 } = {}) {
  const n = Number(amount) || 0
  if (n === 0) return 0
  if (Math.abs(n) >= assumeEuroBelow) return Math.round(n)
  return Math.round(n * 655)
}
